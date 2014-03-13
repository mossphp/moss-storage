<?php
namespace Moss\Storage\Query;

use Moss\Storage\Builder\MySQL\Query as Builder;
use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\One;
use Moss\Storage\Model\Definition\Relation\Relation;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDriver()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $this->assertInstanceOf('\Moss\Storage\Driver\DriverInterface', $query->driver());
    }

    public function testGetBuilder()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $this->assertInstanceOf('\Moss\Storage\Builder\QueryInterface', $query->builder());
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->operation('foo', 'table');
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testInstances($instance)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->operation('read', 'table', $instance);
    }

    public function instanceProvider()
    {
        return array(
            array(new \stdClass()),
            array(array())
        );
    }

    public function testNumber()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->num('table');

        $expected = array(
            'SELECT `table`.`id` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testRead()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testReadOne()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->readOne('table');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` LIMIT 1',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testReadMapping()
    {
        $bag = new ModelBag(
            array(
                new Model(
                    '\stdClass',
                    'table',
                    array(
                        new Integer('id', array('unsigned', 'auto_increment')),
                        new String('text', array('length' => '128', 'null'), 'mapping'),
                    ),
                    array(
                        new Primary(array('id')),
                    )
                )
            )
        );

        $query = new Query($this->mockDriver(), $this->mockBuilder(), $bag);
        $query->reset()
            ->read('table');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` AS `mapping` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unable to access field
     */
    public function testInvalidField()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->fields(array('foobar'));
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testReadFields($fields, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->fields($fields);

        $expected = array(
            'SELECT ' . $queryPart . ' FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function fieldsProvider()
    {
        return array(
            array(array('id', 'text'), '`table`.`id`, `table`.`text`'),
            array(array('id'), '`table`.`id`'),
            array(array('text'), '`table`.`text`'),

            array(array('table.id', 'table.text'), '`table`.`id`, `table`.`text`'),
            array(array('table.id'), '`table`.`id`'),
            array(array('table.text'), '`table`.`text`'),

            array(array('other.id', 'other.text'), '`other`.`id`, `other`.`text`'),
            array(array('other.id'), '`other`.`id`'),
            array(array('other.text'), '`other`.`text`'),
        );
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Invalid aggregation method
     */
    public function testInvalidAggregateMethod()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->aggregate('foo', 'id');
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testReadAggregate($method, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->aggregate($method, 'id');

        $expected = array(
            'SELECT ' . $queryPart . ', `table`.`id`, `table`.`text` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testReadAggregateAliases($method, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table');

        call_user_func(array($query, $method), 'id');

        $expected = array(
            'SELECT ' . $queryPart . ', `table`.`id`, `table`.`text` FROM `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function aggregateProvider()
    {
        return array(
            array('distinct', 'DISTINCT(`table`.`id`) AS `distinct`'),
            array('count', 'COUNT(`table`.`id`) AS `count`'),
            array('average', 'AVERAGE(`table`.`id`) AS `average`'),
            array('max', 'MAX(`table`.`id`) AS `max`'),
            array('min', 'MIN(`table`.`id`) AS `min`'),
            array('sum', 'SUM(`table`.`id`) AS `sum`'),
        );
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testInvalidConditionComparison()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where('id', 1, '!!');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testInvalidConditionLogical()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where('id', 1, '=', 'foo');
    }

    /**
     * @dataProvider conditionFieldValueProvider
     */
    public function testReadConditionFieldValues($field, $value, $queryPart, $binds)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where($field, $value);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` WHERE ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $query->queryString());
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
     * @dataProvider joinProvider
     */
    public function testJoin($join, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->join($join, 'other')
            ->field('table.id')
            ->field('other.id');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text`, `table`.`id`, `other`.`id` FROM `table` ' . $queryPart . ' `other` ON `table`.`id` = `other`.`id`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    /**
     * @dataProvider joinProvider
     */
    public function testJoinAliases($join, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->field('table.id')
            ->field('other.id');

        call_user_func(array($query, $join . 'Join'), 'other');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text`, `table`.`id`, `other`.`id` FROM `table` ' . $queryPart . ' `other` ON `table`.`id` = `other`.`id`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function joinProvider()
    {
        return array(
            array('inner', 'INNER JOIN'),
            array('left', 'LEFT OUTER JOIN'),
            array('right', 'RIGHT OUTER JOIN')
        );
    }

    /**
     * @dataProvider conditionComparisonProvider
     */
    public function testReadConditionComparison($operator, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where('id', 1, $operator);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` WHERE ' . $queryPart,
            array(':condition_0_id' => 1)
        );

        $this->assertEquals($expected, $query->queryString());
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
            array('regex', '(LOWER(`table`.`id`) REGEX LOWER(:condition_0_id))'),
        );
    }

    /**
     * @dataProvider conditionLogicalProvider
     */
    public function testReadConditionLogical($operator, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where('id', 1, '=', $operator)
            ->where('id', 2, '=', $operator);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` WHERE ' . $queryPart,
            array(':condition_0_id' => 1, ':condition_1_id' => 2)
        );

        $this->assertEquals($expected, $query->queryString());
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
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->group('id');

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` GROUP BY `table`.`id`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unsupported sorting method
     */
    public function testInvalidOrder()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->order('foo', 'bar');
    }

    /**
     * @dataProvider orderProvider
     */
    public function testReadOrder($order, $queryPart, $binds = array())
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->order('id', $order);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table` ORDER BY ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function orderProvider()
    {
        return array(
            array('asc', '`table`.`id` ASC'),
            array('desc', '`table`.`id` DESC'),
            array(array(1, 2), '`table`.`id` = :order_0_id DESC, `table`.`id` = :order_1_id DESC', array(':order_0_id' => 1, ':order_1_id' => 2))
        );
    }

    /**
     * @dataProvider limitProvider
     */
    public function testReadLimit($limit, $offset, $queryPart)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->limit($limit, $offset);

        $expected = array(
            'SELECT `table`.`id`, `table`.`text` FROM `table`' . $queryPart,
            array()
        );

        $this->assertEquals($expected, $query->queryString());
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

    public function testWriteInsert()
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->text = 'foo bar';

        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->write('table', $entity);

        $expected = array(
            'INSERT INTO `table` (`id`, `text`) VALUES (:value_0_id, :value_1_text)',
            array(':value_0_id' => 1, ':value_1_text' => 'foo bar')
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testWriteUpdate()
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->text = 'foo bar';

        $query = new Query($this->mockDriver(1), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->write('table', $entity);

        $expected = array(
            'UPDATE `table` SET `id` = :value_0_id, `text` = :value_1_text WHERE `id` = :condition_2_id',
            array(':value_0_id' => 1, ':value_1_text' => 'foo bar', ':condition_2_id' => 1)
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testInsert()
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->text = 'foo bar';

        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->insert('table', $entity);

        $expected = array(
            'INSERT INTO `table` (`id`, `text`) VALUES (:value_0_id, :value_1_text)',
            array(':value_0_id' => 1, ':value_1_text' => 'foo bar')
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testUpdate()
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->text = 'foo bar';

        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->update('table', $entity);

        $expected = array(
            'UPDATE `table` SET `id` = :value_0_id, `text` = :value_1_text WHERE `id` = :condition_2_id',
            array(':value_0_id' => 1, ':value_1_text' => 'foo bar', ':condition_2_id' => 1)
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testDelete()
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $entity->text = 'foo bar';

        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->delete('table', $entity);

        $expected = array(
            'DELETE FROM `table` WHERE `id` = :condition_0_id',
            array(':condition_0_id' => 1)
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testClear()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->clear('table');

        $expected = array(
            'TRUNCATE TABLE `table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    protected function mockDriver($affectedRows = 0)
    {
        $driver = $this->getMock('\Moss\Storage\Driver\DriverInterface');

        $driver->expects($this->any())
            ->method('prepare')
            ->will($this->returnSelf());

        $driver->expects($this->any())
            ->method('execute')
            ->will($this->returnSelf());

        $driver->expects($this->any())
            ->method('store')
            ->will($this->returnArgument(0));

        $driver->expects($this->any())
            ->method('cast')
            ->will($this->returnArgument(0));

        $driver->expects($this->any())
            ->method('affectedRows')
            ->will($this->returnValue($affectedRows));

        return $driver;
    }

    protected function mockBuilder()
    {
        $builder = new Builder();

        return $builder;
    }

    protected function mockModelBag()
    {
        $table = new Model(
            '\stdClass',
            'table',
            array(
                new Integer('id', array('unsigned', 'auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                new One('\altClass', array('id' => 'id'), 'other')
            )
        );

        $other = new Model(
            '\altClass',
            'other',
            array(
                new Integer('id', array('unsigned', 'auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            )
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');

        return $bag;
    }
}
 