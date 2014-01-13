<?php
namespace moss\storage\query;

use moss\storage\builder\mysql\Query as Builder;
use moss\storage\model\definition\field\Field;
use moss\storage\model\definition\index\Primary;
use moss\storage\model\definition\relation\Relation;
use moss\storage\model\Model;
use moss\storage\model\ModelBag;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Query */
    protected $query;

    /** @var \stdClass */
    protected $entity;

    public function setUp()
    {
        $driver = $this->getMock('\moss\storage\driver\DriverInterface');
        $driver->expects($this->any())
               ->method('store')
               ->will($this->returnArgument(0));

        $builder = new Builder();

        $table = new Model(
            '\stdClass',
            'table',
            array(
                 new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                 new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                 new Primary(array('id')),
            ),
            array(
                 new Relation('\stdClass', 'one', array('id' => 'id'), 'other')
            )
        );

        $other = new Model(
            '\altClass',
            'other',
            array(
                 new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                 new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                 new Primary(array('id')),
            )
        );


        $modelbag = new ModelBag();
        $modelbag->set($table, 'table');
        $modelbag->set($other, 'other');

        $this->query = new Query($driver, $builder, $modelbag);

        $this->entity = new \stdClass();
        $this->entity->id = 1;
        $this->entity->text = 'foo bar';
    }

    /**
     * @expectedException \moss\storage\query\QueryException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        $this->query->reset()
                    ->operation('foo', 'table');
    }

    public function testCount()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_COUNT, 'table');

        $expected = array(
            'SELECT `table`.`id` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function testRead()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function testReadOne()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ_ONE, 'table');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` LIMIT 1',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function testReadMapping()
    {
        $driver = $this->getMock('\moss\storage\driver\DriverInterface');
        $driver->expects($this->any())
               ->method('cast')
               ->will($this->returnArgument(0));

        $builder = new Builder();

        $model = new Model(
            '\stdClass',
            'table',
            array(
                 new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                 new Field('text', Model::FIELD_STRING, array('length' => '128', 'null'), 'mapping'),
            ),
            array(
                 new Primary(array('id')),
            )
        );

        $query = new Query($driver, $builder, new ModelBag(array('table' => $model)));
        $query->reset()
              ->operation(Query::OPERATION_READ, 'table');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` AS `mapping` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    /**
     * @expectedException \moss\storage\query\QueryException
     * @expectedExceptionMessage Unable to access field
     */
    public function testInvalidField()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->fields(array('foobar'));
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testReadFields($fields, $queryPart)
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->fields($fields);

        $expected = array(
            'SELECT ' . $queryPart . ' FROM `table`',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function fieldsProvider()
    {
        return array(
            array(array('id', 'text'), '`table`.`id`, `table`.`text`'),
            array(array('id'), '`table`.`id`'),
            array(array('text'), '`table`.`text`'),

            array(array('other.id', 'other.text'), '`other`.`id`, `other`.`text`'),
            array(array('other.id'), '`other`.`id`'),
            array(array('other.text'), '`other`.`text`'),
        );
    }

    /**
     * @expectedException \moss\storage\query\QueryException
     * @expectedExceptionMessage Invalid aggregation method
     */
    public function testInvalidAggregateMethod()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->aggregate('foo', 'id');
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testReadAggregate($method, $queryPart)
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->aggregate($method, 'id');

        $expected = array(
            'SELECT ' . $queryPart . ', `table`.`id`, `table`.`text` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function aggregateProvider()
    {
        return array(
            array(Builder::AGGREGATE_DISTINCT, 'DISTINCT(`table`.`id`) AS `distinct`'),
            array(Builder::AGGREGATE_COUNT, 'COUNT(`table`.`id`) AS `count`'),
            array(Builder::AGGREGATE_AVERAGE, 'AVERAGE(`table`.`id`) AS `average`'),
            array(Builder::AGGREGATE_MAX, 'MAX(`table`.`id`) AS `max`'),
            array(Builder::AGGREGATE_MIN, 'MIN(`table`.`id`) AS `min`'),
            array(Builder::AGGREGATE_SUM, 'SUM(`table`.`id`) AS `sum`'),
        );
    }

    /**
     * @expectedException \moss\storage\query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testInvalidConditionComparison()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->where('id', 1, '!!');
    }

    /**
     * @expectedException \moss\storage\query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testInvalidConditionLogical()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->where('id', 1, '=', 'foo');
    }

    /**
     * @dataProvider conditionFieldValueProvider
     */
    public function testReadConditionFieldValues($field, $value, $queryPart, $binds)
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->where($field, $value);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` WHERE ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function conditionFieldValueProvider()
    {
        return array(
            array('id', 1, '(`table`.`id` = :condition_0_id)', array(':condition_0_id' => 1)),
            array(array('id', 'text'), 1, '(`table`.`id` = :condition_0_id OR `table`.`text` = :condition_1_text)', array(':condition_0_id' => 1, ':condition_1_text' => 1)),
            array('id', array(1, 2), '(`table`.`id` = :condition_0_id OR `table`.`id` = :condition_1_id)', array(':condition_0_id' => 1, ':condition_1_id' => 2)),
            array(array('id', 'text'), array(1, 2), '(`table`.`id` = :condition_0_id OR `table`.`text` = :condition_1_text)', array(':condition_0_id' => 1, ':condition_1_text' => 2)),
            array(array('id', 'text'), array(array(1, 2), array(3, 4)), '((`table`.`id` = :condition_0_id OR `table`.`id` = :condition_1_id) OR (`table`.`text` = :condition_2_text OR `table`.`text` = :condition_3_text))', array(':condition_0_id' => 1, ':condition_1_id' => 2, ':condition_2_text' => 3, ':condition_3_text' => 4)),
        );
    }

    /**
     * @dataProvider conditionComparisonProvider
     */
    public function testReadConditionComparison($operator, $queryPart)
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->where('id', 1, $operator);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` WHERE ' . $queryPart,
            array(':condition_0_id' => 1)
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function conditionComparisonProvider()
    {
        return array(
            array('=', '(`table`.`id` = :condition_0_id)'),
            array('!=', '(`table`.`id` != :condition_0_id)'),
            array('>', '(`table`.`id` > :condition_0_id)'),
            array('>=', '(`table`.`id` >= :condition_0_id)'),
            array('<', '(`table`.`id` < :condition_0_id)'),
            array('<=', '(`table`.`id` <= :condition_0_id)'),
            array('like', '(`table`.`id` LIKE :condition_0_id)'),
            array('regex', '(`table`.`id` REGEX :condition_0_id)'),
        );
    }

    /**
     * @dataProvider conditionLogicalProvider
     */
    public function testReadConditionLogical($operator, $queryPart)
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->where('id', 1, '=', $operator)
                    ->where('id', 2, '=', $operator);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` WHERE ' . $queryPart,
            array(':condition_0_id' => 1, ':condition_1_id' => 2)
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function conditionLogicalProvider()
    {
        return array(
            array('and', '(`table`.`id` = :condition_0_id) AND (`table`.`id` = :condition_1_id)'),
            array('or', '(`table`.`id` = :condition_0_id) OR (`table`.`id` = :condition_1_id)'),
        );
    }

    public function testReadGroup()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->group('id');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` GROUP BY `table`.`id`',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    /**
     * @expectedException \moss\storage\query\QueryException
     * @expectedExceptionMessage Unsupported sorting method
     */
    public function testInvalidOrder()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->order('foo', 'bar');
    }

    /**
     * @dataProvider orderProvider
     */
    public function testReadOrder($order, $queryPart, $binds = array())
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->order('id', $order);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` ORDER BY ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function orderProvider()
    {
        return array(
            array(Builder::ORDER_ASC, '`table`.`id` ASC'),
            array(Builder::ORDER_DESC, '`table`.`id` DESC'),
            array(array(1, 2), '`table`.`id` = :order_0_id DESC, `table`.`id` = :order_1_id DESC', array(':order_0_id' => 1, ':order_1_id' => 2))
        );
    }

    /**
     * @dataProvider limitProvider
     */
    public function testReadLimit($limit, $offset, $queryPart)
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_READ, 'table')
                    ->limit($limit, $offset);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table`' . $queryPart,
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function limitProvider()
    {
        return array(
            array(null, null, ''),
            array(0, 0, ''),
            array(0, 1, ''),
            array(1, 0, ' LIMIT 1'),
            array(10, 1, ' LIMIT 1, 10'),
        );
    }

    public function testInsert()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_INSERT, $this->entity);

        $expected = array(
            'INSERT INTO `table` (`id`, `text`) VALUES (:value_0_id, :value_1_text)',
            array(':value_0_id' => 1, ':value_1_text' => 'foo bar')
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function testUpdate()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_UPDATE, $this->entity);

        $expected = array(
            'UPDATE `table` SET `id` = :value_0_id, `text` = :value_1_text WHERE `id` = :condition_2_id',
            array(':value_0_id' => 1, ':value_1_text' => 'foo bar', ':condition_2_id' => 1)
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function testDelete()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_DELETE, $this->entity);

        $expected = array(
            'DELETE FROM `table` WHERE `id` = :condition_0_id',
            array(':condition_0_id' => 1)
        );

        $this->assertEquals($expected, $this->query->queryString());
    }

    public function testClear()
    {
        $this->query->reset()
                    ->operation(Query::OPERATION_CLEAR, 'table');

        $expected = array(
            'TRUNCATE TABLE `table`',
            array()
        );

        $this->assertEquals($expected, $this->query->queryString());
    }
}
 