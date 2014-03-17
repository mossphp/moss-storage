<?php
namespace Moss\Storage\Query;

use Moss\Storage\Builder\MySQL\Query as Builder;
use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\One;
use Moss\Storage\Model\Definition\Relation\Many;
use Moss\Storage\Model\Definition\Relation\OneTrough;
use Moss\Storage\Model\Definition\Relation\ManyTrough;
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
            'SELECT `test_table`.`id` FROM `test_table`',
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
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table`',
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
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table` LIMIT 1',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function testReadMapping()
    {
        $bag = new ModelBag(
            array(
                'table' => new Model(
                        '\stdClass',
                        'test_table',
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
            'SELECT `test_table`.`id`, `test_table`.`text` AS `mapping` FROM `test_table`',
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
            'SELECT ' . $queryPart . ' FROM `test_table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function fieldsProvider()
    {
        return array(
            array(array('id', 'text'), '`test_table`.`id`, `test_table`.`text`'),
            array(array('id'), '`test_table`.`id`'),
            array(array('text'), '`test_table`.`text`'),

            array(array('table.id', 'table.text'), '`test_table`.`id`, `test_table`.`text`'),
            array(array('table.id'), '`test_table`.`id`'),
            array(array('table.text'), '`test_table`.`text`'),

            array(array('other.id', 'other.text'), '`test_other`.`id`, `test_other`.`text`'),
            array(array('other.id'), '`test_other`.`id`'),
            array(array('other.text'), '`test_other`.`text`'),
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
            'SELECT ' . $queryPart . ', `test_table`.`id`, `test_table`.`text` FROM `test_table`',
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
            'SELECT ' . $queryPart . ', `test_table`.`id`, `test_table`.`text` FROM `test_table`',
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function aggregateProvider()
    {
        return array(
            array('distinct', 'DISTINCT(`test_table`.`id`) AS `distinct`'),
            array('count', 'COUNT(`test_table`.`id`) AS `count`'),
            array('average', 'AVERAGE(`test_table`.`id`) AS `average`'),
            array('max', 'MAX(`test_table`.`id`) AS `max`'),
            array('min', 'MIN(`test_table`.`id`) AS `min`'),
            array('sum', 'SUM(`test_table`.`id`) AS `sum`'),
        );
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testInvalidWhereComparison()
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
    public function testInvalidWhereLogical()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where('id', 1, '=', 'foo');
    }

    /**
     * @dataProvider whereFieldValueProvider
     */
    public function testReadWhereFieldValues($field, $value, $queryPart, $binds)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->where($field, $value);

        $expected = array(
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table` WHERE ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function whereFieldValueProvider()
    {
        return array(
            array('id', 1, '(`test_table`.`id` = :condition_0_id)', array(':condition_0_id' => 1)),
            array(array('id', 'text'), 1, '(`test_table`.`id` = :condition_0_id OR `test_table`.`text` = :condition_1_text)', array(':condition_0_id' => 1, ':condition_1_text' => 1)),
            array('id', array(1, 2), '(`test_table`.`id` = :condition_0_id OR `test_table`.`id` = :condition_1_id)', array(':condition_0_id' => 1, ':condition_1_id' => 2)),
            array(array('id', 'text'), array(1, 2), '(`test_table`.`id` = :condition_0_id OR `test_table`.`text` = :condition_1_text)', array(':condition_0_id' => 1, ':condition_1_text' => 2)),
            array(array('id', 'text'), array(array(1, 2), array(3, 4)), '((`test_table`.`id` = :condition_0_id OR `test_table`.`id` = :condition_1_id) OR (`test_table`.`text` = :condition_2_text OR `test_table`.`text` = :condition_3_text))', array(':condition_0_id' => 1, ':condition_1_id' => 2, ':condition_2_text' => 3, ':condition_3_text' => 4)),
        );
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testInvalidHavingComparison()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->having('id', 1, '!!');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testInvalidHavingLogical()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->having('id', 1, '=', 'foo');
    }

    /**
     * @dataProvider havingFieldValueProvider
     */
    public function testReadHavingFieldValues($field, $value, $queryPart, $binds)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->count('id', 'count')
            ->having($field, $value);

        $expected = array(
            'SELECT COUNT(`test_table`.`id`) AS `count`, `test_table`.`id`, `test_table`.`text` FROM `test_table` HAVING ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function havingFieldValueProvider()
    {
        return array(
            array('id', 1, '(`test_table`.`id` = :condition_0_id)', array(':condition_0_id' => 1)),
            array(array('id', 'text'), 1, '(`test_table`.`id` = :condition_0_id OR `test_table`.`text` = :condition_1_text)', array(':condition_0_id' => 1, ':condition_1_text' => 1)),
            array('id', array(1, 2), '(`test_table`.`id` = :condition_0_id OR `test_table`.`id` = :condition_1_id)', array(':condition_0_id' => 1, ':condition_1_id' => 2)),
            array(array('id', 'text'), array(1, 2), '(`test_table`.`id` = :condition_0_id OR `test_table`.`text` = :condition_1_text)', array(':condition_0_id' => 1, ':condition_1_text' => 2)),
            array(array('id', 'text'), array(array(1, 2), array(3, 4)), '((`test_table`.`id` = :condition_0_id OR `test_table`.`id` = :condition_1_id) OR (`test_table`.`text` = :condition_2_text OR `test_table`.`text` = :condition_3_text))', array(':condition_0_id' => 1, ':condition_1_id' => 2, ':condition_2_text' => 3, ':condition_3_text' => 4)),
            array('count', 2, '(`count` = :condition_0_count)', array(':condition_0_count' => 2)),
        );
    }

    /**
     * @dataProvider joinProvider
     */
    public function testJoin($type, $join, $queryString)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag($type));
        $query->reset()
            ->read('table')
            ->join($join, 'other')
            ->field('table.id')
            ->field('other.id');

        $queryString = array(
            $queryString,
            array()
        );

        $this->assertEquals($queryString, $query->queryString());
    }

    /**
     * @dataProvider joinProvider
     */
    public function testJoinAliases($type, $join, $queryString)
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag($type));
        $query->reset()
            ->read('table')
            ->field('table.id')
            ->field('other.id');

        call_user_func(array($query, $join . 'Join'), 'other');

        $expected = array(
            $queryString,
            array()
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function joinProvider()
    {
        return array(
            array('one', 'inner', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` INNER JOIN `test_other` ON `test_table`.`id` = `test_other`.`id`'),
            array('many', 'inner', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` INNER JOIN `test_other` ON `test_table`.`id` = `test_other`.`id`'),
            array('oneTrough', 'inner', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` INNER JOIN `test_mediator` ON `test_table`.`id` = `test_mediator`.`in_id` INNER JOIN `test_other` ON `test_mediator`.`out_id` = `test_other`.`id`'),
            array('manyTrough', 'inner', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` INNER JOIN `test_mediator` ON `test_table`.`id` = `test_mediator`.`in_id` INNER JOIN `test_other` ON `test_mediator`.`out_id` = `test_other`.`id`'),

            array('one', 'left', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` LEFT OUTER JOIN `test_other` ON `test_table`.`id` = `test_other`.`id`'),
            array('many', 'left', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` LEFT OUTER JOIN `test_other` ON `test_table`.`id` = `test_other`.`id`'),
            array('oneTrough', 'left', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` LEFT OUTER JOIN `test_mediator` ON `test_table`.`id` = `test_mediator`.`in_id` LEFT OUTER JOIN `test_other` ON `test_mediator`.`out_id` = `test_other`.`id`'),
            array('manyTrough', 'left', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` LEFT OUTER JOIN `test_mediator` ON `test_table`.`id` = `test_mediator`.`in_id` LEFT OUTER JOIN `test_other` ON `test_mediator`.`out_id` = `test_other`.`id`'),

            array('one', 'right', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` RIGHT OUTER JOIN `test_other` ON `test_table`.`id` = `test_other`.`id`'),
            array('many', 'right', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` RIGHT OUTER JOIN `test_other` ON `test_table`.`id` = `test_other`.`id`'),
            array('oneTrough', 'right', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` RIGHT OUTER JOIN `test_mediator` ON `test_table`.`id` = `test_mediator`.`in_id` RIGHT OUTER JOIN `test_other` ON `test_mediator`.`out_id` = `test_other`.`id`'),
            array('manyTrough', 'right', 'SELECT `test_table`.`id`, `test_table`.`text`, `test_table`.`id`, `test_other`.`id` FROM `test_table` RIGHT OUTER JOIN `test_mediator` ON `test_table`.`id` = `test_mediator`.`in_id` RIGHT OUTER JOIN `test_other` ON `test_mediator`.`out_id` = `test_other`.`id`'),
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
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table` WHERE ' . $queryPart,
            array(':condition_0_id' => 1)
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function conditionComparisonProvider()
    {
        return array(
            array('=', '(`test_table`.`id` = :condition_0_id)'),
            array('!=', '(`test_table`.`id` != :condition_0_id)'),
            array('>', '(`test_table`.`id` > :condition_0_id)'),
            array('>=', '(`test_table`.`id` >= :condition_0_id)'),
            array('<', '(`test_table`.`id` < :condition_0_id)'),
            array('<=', '(`test_table`.`id` <= :condition_0_id)'),
            array('like', '(`test_table`.`id` LIKE :condition_0_id)'),
            array('regex', '(LOWER(`test_table`.`id`) REGEXP LOWER(:condition_0_id))'),
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
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table` WHERE ' . $queryPart,
            array(':condition_0_id' => 1, ':condition_1_id' => 2)
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function conditionLogicalProvider()
    {
        return array(
            array('and', '(`test_table`.`id` = :condition_0_id) AND (`test_table`.`id` = :condition_1_id)'),
            array('or', '(`test_table`.`id` = :condition_0_id) OR (`test_table`.`id` = :condition_1_id)'),
        );
    }

    public function testReadGroup()
    {
        $query = new Query($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $query->reset()
            ->read('table')
            ->group('id');

        $expected = array(
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table` GROUP BY `test_table`.`id`',
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
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table` ORDER BY ' . $queryPart,
            $binds
        );

        $this->assertEquals($expected, $query->queryString());
    }

    public function orderProvider()
    {
        return array(
            array('asc', '`test_table`.`id` ASC'),
            array('desc', '`test_table`.`id` DESC'),
            array(array(1, 2), '`test_table`.`id` = :order_0_id DESC, `test_table`.`id` = :order_1_id DESC', array(':order_0_id' => 1, ':order_1_id' => 2))
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
            'SELECT `test_table`.`id`, `test_table`.`text` FROM `test_table`' . $queryPart,
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
            'INSERT INTO `test_table` (`id`, `text`) VALUES (:value_0_id, :value_1_text)',
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
            'UPDATE `test_table` SET `id` = :value_0_id, `text` = :value_1_text WHERE `id` = :condition_2_id',
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
            'INSERT INTO `test_table` (`id`, `text`) VALUES (:value_0_id, :value_1_text)',
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
            'UPDATE `test_table` SET `id` = :value_0_id, `text` = :value_1_text WHERE `id` = :condition_2_id',
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
            'DELETE FROM `test_table` WHERE `id` = :condition_0_id',
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
            'TRUNCATE TABLE `test_table`',
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

    protected function mockModelBag($relType = 'one')
    {
        $table = new Model(
            '\stdClass',
            'test_table',
            array(
                new Integer('id', array('unsigned', 'auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                $this->mockRelation($relType)
            )
        );

        $other = new Model(
            '\altClass',
            'test_other',
            array(
                new Integer('id', array('unsigned', 'auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            )
        );

        $mediator = new Model(
            null,
            'test_mediator',
            array(
                new Integer('in', array('unsigned')),
                new Integer('out', array('unsigned')),
            ),
            array(
                new Primary(array('in', 'out')),
            )
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');
        $bag->set($mediator, 'mediator');

        return $bag;
    }

    protected function mockRelation($relType)
    {
        switch ($relType) {
            case 'one':
            default:
                return new One('\altClass', array('id' => 'id'), 'other');
            case 'many':
                return new Many('\altClass', array('id' => 'id'), 'other');
            case 'oneTrough':
                return new OneTrough('\altClass', array('id' => 'in_id'), array('out_id' => 'id'), 'mediator', 'other');
            case 'manyTrough':
                return new ManyTrough('\altClass', array('id' => 'in_id'), array('out_id' => 'id'), 'mediator', 'other');
        }
    }
}
 