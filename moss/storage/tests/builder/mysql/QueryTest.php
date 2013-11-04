<?php
namespace moss\storage\builder\mysql;

class QueryTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo');
        $this->assertEquals('SELECT `foo` FROM `foo`', $builder->build());
    }

    /**
     * @dataProvider containerProvider
     */
    public function testContainer($container)
    {
        $builder = new Query('select');
        $builder->container($container)
                ->field('foo');
        $this->assertEquals('SELECT `foo` FROM `' . $container . '`', $builder->build());
    }

    public function containerProvider()
    {
        return array(
            array('foo'),
            array('bar')
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Missing container name
     */
    public function testWithoutContainer()
    {
        $builder = new Query('select');
        $builder->field('foo')
                ->build();
    }

    /**
     * @dataProvider operationProvider
     */
    public function testOperation($op, $expected) {
        $builder = new Query($op);
        $builder->container('foo');

        switch($op) {
            case 'select':
                $builder->fields(array('foo'));
                break;
            case 'insert':
            case 'update':
                $builder->value('foo', 1);
                break;
        }

        $this->assertEquals($expected, $builder->build());
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation() {
        new Query('foo');
    }

    public function operationProvider() {
        return array(
            array('select', 'SELECT `foo` FROM `foo`'),
            array('insert', 'INSERT INTO `foo` (`foo`) VALUE (1)'),
            array('update', 'UPDATE `foo` SET `foo` = 1'),
            array('delete', 'DELETE FROM `foo`'),
            array('clear', 'TRUNCATE TABLE `foo`')
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage No fields selected for reading in query
     */
    public function testWithoutFields()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->build();
    }

    public function testFields()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->fields(array('foo', 'bar' => 'barbar'));
        $this->assertEquals('SELECT `foo`, `bar` AS `barbar` FROM `foo`', $builder->build());
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testField($values, $expected)
    {
        $builder = new Query('select');
        $builder->container('foo');
        foreach ($values as $key => $val) {
            if (is_numeric($key)) {
                $builder->field($val);
                continue;
            }

            $builder->field($key, $val);
        }
        $this->assertEquals($expected, $builder->build());
    }

    public function fieldProvider()
    {
        return array(
            array(array('foo'), 'SELECT `foo` FROM `foo`'),
            array(array('foo', 'bar'), 'SELECT `foo`, `bar` FROM `foo`'),
            array(array('foo', 'bar' => 'barbar'), 'SELECT `foo`, `bar` AS `barbar` FROM `foo`'),
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage No values to insert
     */
    public function testWithoutInsertValues()
    {
        $builder = new Query('insert');
        $builder->container('foo')
                ->build();
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage No values to update
     */
    public function testWithoutUpdateValues()
    {
        $builder = new Query('update');
        $builder->container('foo')
                ->build();
    }

    /**
     * @dataProvider insertValueProvider
     */
    public function testInsertValue($field, $value, $expected)
    {
        $builder = new Query('insert');
        $builder->container('foo')
                ->value($field, $value);
        $this->assertEquals($expected, $builder->build());
    }

    public function insertValueProvider()
    {
        return array(
            array('foo', 1, 'INSERT INTO `foo` (`foo`) VALUE (1)'),
            array('foo', null, 'INSERT INTO `foo` (`foo`) VALUE (NULL)')
        );
    }

    /**
     * @dataProvider updateValueProvider
     */
    public function testUpdateValue($field, $value, $expected)
    {
        $builder = new Query('update');
        $builder->container('foo')
                ->value($field, $value);
        $this->assertEquals($expected, $builder->build());
    }

    public function updateValueProvider()
    {
        return array(
            array('foo', 1, 'UPDATE `foo` SET `foo` = 1'),
            array('foo', null, 'UPDATE `foo` SET `foo` = NULL')
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports aggregation method
     */
    public function testInvalidAggregate()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->aggregate('foo', 'bar');
    }

    public function aggregateProvider()
    {
        return array(
            array('distinct', 'SELECT DISTINCT(`bar`) AS `distinct`, `foo` FROM `foo`'),
            array('count', 'SELECT COUNT(`bar`) AS `count`, `foo` FROM `foo`'),
            array('average', 'SELECT AVERAGE(`bar`) AS `average`, `foo` FROM `foo`'),
            array('max', 'SELECT MAX(`bar`) AS `max`, `foo` FROM `foo`'),
            array('min', 'SELECT MIN(`bar`) AS `min`, `foo` FROM `foo`'),
            array('sum', 'SELECT SUM(`bar`) AS `sum`, `foo` FROM `foo`')
        );
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testAggregate($method, $expected)
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->aggregate($method, 'bar');
        $this->assertEquals($expected, $builder->build());
    }

    public function testSub()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo');

        $Sub = new Query('select');
        $Sub->container('bar')
            ->field('bar');

        $builder->sub($Sub, 'bar');

        $this->assertEquals('SELECT `foo`, ( SELECT `bar` FROM `bar` ) AS `bar` FROM `foo`', $builder->build());
    }

    public function testGroup()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->group('bar');
        $this->assertEquals('SELECT `foo` FROM `foo` GROUP BY `bar`', $builder->build());
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testCondition($field, $value, $comparison, $expected)
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->condition($field, $value, $comparison);
        $this->assertEquals($expected, $builder->build());
    }

    public function conditionProvider()
    {
        return array(
            array('foo', ':bar', '=', 'SELECT `foo` FROM `foo` WHERE `foo` = :bar'),
            array('foo', ':bar', '!=', 'SELECT `foo` FROM `foo` WHERE `foo` != :bar'),
            array('foo', ':bar', '>', 'SELECT `foo` FROM `foo` WHERE `foo` > :bar'),
            array('foo', ':bar', '<', 'SELECT `foo` FROM `foo` WHERE `foo` < :bar'),
            array('foo', ':bar', '>=', 'SELECT `foo` FROM `foo` WHERE `foo` >= :bar'),
            array('foo', ':bar', '<=', 'SELECT `foo` FROM `foo` WHERE `foo` <= :bar'),
            array('foo', null, '=', 'SELECT `foo` FROM `foo` WHERE `foo` IS NULL'),
            array('foo', null, '!=', 'SELECT `foo` FROM `foo` WHERE `foo` IS NOT NULL'),
            array('foo', array(':bar1', ':bar2'), '=', 'SELECT `foo` FROM `foo` WHERE (`foo` = :bar1 OR `foo` = :bar2)'),
            array(array('foo', 'bar'), ':bar', '=', 'SELECT `foo` FROM `foo` WHERE (`foo` = :bar OR `bar` = :bar)'),
            array(array('foo', 'bar'), array(':bar1', ':bar2'), '=', 'SELECT `foo` FROM `foo` WHERE (`foo` = :bar1 OR `bar` = :bar2)'),
            array(array('foo', 'bar'), array(array(':foo1', ':foo2'), array(':bar1', ':bar2')), '=', 'SELECT `foo` FROM `foo` WHERE ((`foo` = :foo1 OR `foo` = :foo2) OR (`bar` = :bar1 OR `bar` = :bar2))')
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports comparison operator
     */
    public function testInvalidComparisonCondition()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
            ->condition('foo', 1, 'foo', 'and');
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports logical operator
     */
    public function testInvalidLogicalCondition()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->condition('foo', 1, '=', 'foo');
    }

    /**
     * @dataProvider orderProvider
     */
    public function testOrder($order, $expected)
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo');
        foreach ($order as $o) {
            $builder->order($o[0], $o[1]);
        }
        $this->assertEquals($expected, $builder->build());
    }

    public function orderProvider()
    {
        return array(
            array(array(array('foo', 'asc')), 'SELECT `foo` FROM `foo` ORDER BY `foo` ASC'),
            array(array(array('foo', 'desc')), 'SELECT `foo` FROM `foo` ORDER BY `foo` DESC'),
            array(array(array('foo', array(1, 2, 3))), 'SELECT `foo` FROM `foo` ORDER BY `foo` = 1 DESC, `foo` = 2 DESC, `foo` = 3 DESC'),
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports order method
     */
    public function testInvalidOrder()
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->order('foo', 'foo');
    }

    /**
     * @dataProvider limitProvider
     */
    public function testLimit($limit, $offset, $expected)
    {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo')
                ->limit($limit, $offset);
        $this->assertEquals($expected, $builder->build());
    }

    public function limitProvider()
    {
        return array(
            array(null, null, 'SELECT `foo` FROM `foo`'),
            array(null, 1, 'SELECT `foo` FROM `foo`'),
            array(1, null, 'SELECT `foo` FROM `foo` LIMIT 1'),
            array(-1, null, 'SELECT `foo` FROM `foo`'),
            array(1.2, null, 'SELECT `foo` FROM `foo` LIMIT 1'),
            array(12, null, 'SELECT `foo` FROM `foo` LIMIT 12'),
            array(1, 1, 'SELECT `foo` FROM `foo` LIMIT 1, 1'),
            array(1, -1, 'SELECT `foo` FROM `foo` LIMIT 1'),
            array(1, 1.2, 'SELECT `foo` FROM `foo` LIMIT 1, 1'),
            array(1, 12, 'SELECT `foo` FROM `foo` LIMIT 12, 1'),
            array(12, 34, 'SELECT `foo` FROM `foo` LIMIT 34, 12'),
        );
    }

    public function testReset()
    {
        $builder = new Query('insert');
        $builder->container('foo')
                ->reset();

        $builder->operation('select')
                ->container('bar')
                ->field('foo');

        $this->assertEquals('SELECT `foo` FROM `bar`', $builder->build());
    }

    public function testToString() {
        $builder = new Query('select');
        $builder->container('foo')
                ->field('foo');
        $this->assertEquals('SELECT `foo` FROM `foo`', (string) $builder);
    }

    public function testToStringWithException() {
        $builder = new Query('select');
        $builder->container('foo');
        $this->assertEquals('No fields selected for reading in query', (string) $builder);
    }
} 