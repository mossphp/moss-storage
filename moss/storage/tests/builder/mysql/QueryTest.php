<?php
namespace moss\storage\builder\mysql;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Missing container name
     */
    public function testMissingContainer()
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->reset()
              ->operation(Query::OPERATION_SELECT)
              ->fields(array('foo', 'bar'))
              ->build();
    }

    public function testContainer()
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->reset()
              ->container('foobar', 'fb')
              ->operation(Query::OPERATION_SELECT)
              ->fields(array('foo', 'bar'))
              ->build();

        $this->assertEquals('SELECT `fb`.`foo`, `fb`.`bar` FROM `foobar` AS `fb`', $query->build());
    }

    public function operationProvider()
    {
        return array(
            array('SELECT `table`.`foo`, `table`.`bar` FROM `table`', Query::OPERATION_SELECT),
            array('INSERT INTO `table` (`foo`, `bar`) VALUES (:bind1, :bind2)', Query::OPERATION_INSERT),
            array('UPDATE `table` SET `foo` = :bind1, `bar` = :bind2', Query::OPERATION_UPDATE),
            array('DELETE FROM `table`', Query::OPERATION_DELETE),
            array('TRUNCATE TABLE `table`', Query::OPERATION_CLEAR),
        );
    }

    /**
     * @dataProvider operationProvider
     */
    public function testOperation($expected, $operation)
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->reset()
              ->container('table')
              ->operation($operation)
              ->fields(array('foo', 'bar'))
              ->value('foo', ':bind1')
              ->value('bar', ':bind2')
              ->build();

        $this->assertEquals($expected, $query->build());
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->reset()
              ->container('table')
              ->operation('foo')
              ->fields(array('foo', 'bar'))
              ->build();
    }

    // SELECT

    public function testSelectWithoutFields()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $this->assertEquals('SELECT * FROM `table` AS `t`', $query->build());
    }

    public function testSelect()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo', 'bar', 'yada_yada' => 'yada'));
        $this->assertEquals('SELECT `t`.`foo`, `t`.`bar`, `t`.`yada_yada` AS `yada` FROM `table` AS `t`', $query->build());
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports comparison operator
     */
    public function testSelectWithInvalidComparison()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'))
              ->where('foo', ':bind', '!!');
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports logical operator
     */
    public function testSelectWithInvalidLogical()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'))
              ->where('foo', ':bind', '=', 'BOO');
    }

    public function testSelectWithSubQuery()
    {
        $sub = new Query('bar', 'b');
        $sub->fields(array('bar'));

        $query = new Query('foo', 'f', Query::OPERATION_SELECT);
        $query->fields(array('foo'))->sub($sub, 'b');

        $this->assertEquals('SELECT `f`.`foo`, ( SELECT `b`.`bar` FROM `bar` AS `b` ) AS `b` FROM `foo` AS `f`', $query->build());
    }

    /**
     * @dataProvider aliasedConditionProvider
     */
    public function testSelectWithWhere($conditions, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'));

        foreach ($conditions as $condition) {
            $query->where(
                  $condition[0],
                  $condition[1],
                  isset($condition[2]) ? $condition[2] : Query::COMPARISON_EQUAL,
                  isset($condition[3]) ? $condition[3] : Query::LOGICAL_AND
            );
        }

        $this->assertEquals('SELECT `t`.`foo` FROM `table` AS `t` WHERE ' . $expected, $query->build());
    }

    /**
     * @dataProvider aliasedConditionProvider
     */
    public function testSelectWithHaving($conditions, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'));

        foreach ($conditions as $condition) {
            $query->having(
                  $condition[0],
                  $condition[1],
                  isset($condition[2]) ? $condition[2] : Query::COMPARISON_EQUAL,
                  isset($condition[3]) ? $condition[3] : Query::LOGICAL_AND
            );
        }

        $this->assertEquals('SELECT `t`.`foo` FROM `table` AS `t` HAVING ' . $expected, $query->build());
    }

    public function aliasedConditionProvider()
    {
        return array(
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_EQUAL)
                ),
                '`t`.`foo` = :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_NOT_EQUAL)
                ),
                '`t`.`foo` != :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_LESS)
                ),
                '`t`.`foo` < :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_LESS_EQUAL)
                ),
                '`t`.`foo` <= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_GREATER)
                ),
                '`t`.`foo` > :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_GREATER_EQUAL)
                ),
                '`t`.`foo` >= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_LIKE)
                ),
                '`t`.`foo` LIKE :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_REGEX)
                ),
                '`t`.`foo` REGEX :bind'
            ),
            array(
                array(
                    array('foo', array(':bind1', ':bind2'))
                ),
                '(`t`.`foo` = :bind1 OR `t`.`foo` = :bind2)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), ':bind')
                ),
                '(`t`.`foo` = :bind OR `t`.`bar` = :bind)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), array(':bind1', ':bind2'))
                ),
                '(`t`.`foo` = :bind1 OR `t`.`bar` = :bind2)'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, Query::LOGICAL_AND),
                    array('bar', ':bindBar', null, Query::LOGICAL_AND)
                ),
                '`t`.`foo` = :bindFoo AND `t`.`bar` = :bindBar'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, Query::LOGICAL_OR),
                    array('bar', ':bindBar', null, Query::LOGICAL_OR)
                ),
                '`t`.`foo` = :bindFoo OR `t`.`bar` = :bindBar'
            )
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports aggregation method
     */
    public function testSelectWithInvalidAggregate()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->aggregate('foo', 'bar');
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testSelectWithAggregate($expected, $method)
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'))
              ->aggregate($method, 'bar');

        $this->assertEquals('SELECT ' . $expected . ', `t`.`foo` FROM `table` AS `t`', $query->build());
    }

    public function aggregateProvider()
    {
        return array(
            array('DISTINCT(`t`.`bar`) AS `distinct`', Query::AGGREGATE_DISTINCT),
            array('COUNT(`t`.`bar`) AS `count`', Query::AGGREGATE_COUNT),
            array('AVERAGE(`t`.`bar`) AS `average`', Query::AGGREGATE_AVERAGE),
            array('MIN(`t`.`bar`) AS `min`', Query::AGGREGATE_MIN),
            array('MAX(`t`.`bar`) AS `max`', Query::AGGREGATE_MAX),
            array('SUM(`t`.`bar`) AS `sum`', Query::AGGREGATE_SUM)
        );
    }

    public function testSelectWithGroup() {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'))
              ->group('bar');

        $this->assertEquals('SELECT `t`.`foo` FROM `table` AS `t` GROUP BY `t`.`bar`', $query->build());
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports order method
     */
    public function testSelectWithInvalidOrder()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->order('foo', 'bar');
    }

    /**
     * @dataProvider orderProvider
     */
    public function testSelectWithOrder($field, $order, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'));
        $query->order($field, $order);
        $this->assertEquals('SELECT `t`.`foo` FROM `table` AS `t` ' . $expected, $query->build());
    }

    public function orderProvider()
    {
        return array(
            array('foo', Query::ORDER_ASC, 'ORDER BY `t`.`foo` ASC'),
            array('foo', Query::ORDER_DESC, 'ORDER BY `t`.`foo` DESC'),
            array('foo', array('one', 'two', 'three'), 'ORDER BY `t`.`foo` = one DESC, `t`.`foo` = two DESC, `t`.`foo` = three DESC'),
        );
    }

    /**
     * @dataProvider limitProvider()
     */
    public function testSelectWithLimit($limit, $offset, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo'));
        $query->limit($limit, $offset);
        $this->assertEquals('SELECT `t`.`foo` FROM `table` AS `t` ' . $expected, $query->build());
    }

    public function limitProvider()
    {
        return array(
            array(1, null, 'LIMIT 1'),
            array(1, 0, 'LIMIT 1'),
            array(1, 1, 'LIMIT 1, 1'),
            array(10, 10, 'LIMIT 10, 10'),
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports join type
     */
    public function testSelectWithInvalidJoin()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        $query->join('foo', 'bar', array('f' => 'b'));
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Empty join array for join type
     */
    public function testSelectWithInvalidJoinsArray()
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        $query->join(Query::JOIN_INNER, 'bar', array());
    }

    /**
     * @dataProvider joinProvider
     */
    public function testSelectWithJoin($join, $container, $joins, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_SELECT);
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        $query->join($join, $container, $joins);
        $this->assertEquals($expected, $query->build());
    }

    public function joinProvider()
    {
        return array(
            array(
                Query::JOIN_INNER,
                'bar',
                array('foo' => 'bar'),
                'SELECT `t`.`foo`, `bar`.`bar` AS `barbar`, `b`.`bar` AS `bbar` FROM `table` AS `t` INNER JOIN `bar` ON `t`.`foo` = `bar`.`bar`'
            ),
            array(
                Query::JOIN_INNER,
                array('bar', 'b'),
                array('foo' => 'bar'),
                'SELECT `t`.`foo`, `bar`.`bar` AS `barbar`, `b`.`bar` AS `bbar` FROM `table` AS `t` INNER JOIN `bar` AS `b` ON `t`.`foo` = `b`.`bar`'
            ),
            array(
                Query::JOIN_INNER,
                array('bar' => 'b'),
                array('foo' => 'bar'),
                'SELECT `t`.`foo`, `bar`.`bar` AS `barbar`, `b`.`bar` AS `bbar` FROM `table` AS `t` INNER JOIN `bar` AS `b` ON `t`.`foo` = `b`.`bar`'
            ),
            array(
                Query::JOIN_LEFT,
                'bar',
                array('foo' => 'bar'),
                'SELECT `t`.`foo`, `bar`.`bar` AS `barbar`, `b`.`bar` AS `bbar` FROM `table` AS `t` LEFT OUTER JOIN `bar` ON `t`.`foo` = `bar`.`bar`'
            ),
            array(
                Query::JOIN_RIGHT,
                'bar',
                array('foo' => 'bar'),
                'SELECT `t`.`foo`, `bar`.`bar` AS `barbar`, `b`.`bar` AS `bbar` FROM `table` AS `t` RIGHT OUTER JOIN `bar` ON `t`.`foo` = `bar`.`bar`'
            ),
        );
    }

    // INSERT
    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage No values to insert
     */
    public function testInsertWithoutValues()
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->build();
    }

    public function testInsertSingleValue()
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->value('foo', ':bind');
        $this->assertEquals('INSERT INTO `table` (`foo`) VALUE (:bind)', $query->build());
    }

    public function testInsertMultipleValues()
    {
        $query = new Query('table', 't', Query::OPERATION_INSERT);
        $query->value('foo', ':bind1');
        $query->value('bar', ':bind2');
        $this->assertEquals('INSERT INTO `table` (`foo`, `bar`) VALUES (:bind1, :bind2)', $query->build());
    }

    // UPDATE

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage No values to update
     */
    public function testUpdateWithoutValues()
    {
        $query = new Query('table', 't', Query::OPERATION_UPDATE);
        $query->build();
    }

    public function testUpdate()
    {
        $query = new Query('table', 't', Query::OPERATION_UPDATE);
        $query->value('foo', ':bind');
        $this->assertEquals('UPDATE `table` SET `foo` = :bind', $query->build());
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testUpdateWithConditions($conditions, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_UPDATE);
        $query->value('foo', ':bind');

        foreach ($conditions as $condition) {
            $query->where(
                  $condition[0],
                  $condition[1],
                  isset($condition[2]) ? $condition[2] : Query::COMPARISON_EQUAL,
                  isset($condition[3]) ? $condition[3] : Query::LOGICAL_AND
            );
        }

        $this->assertEquals('UPDATE `table` SET `foo` = :bind ' . $expected, $query->build());
    }

    /**
     * @dataProvider limitProvider
     */
    public function testUpdateWithLimit($limit, $offset, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_UPDATE);
        $query->value('foo', ':bind');
        $query->limit($limit, $offset);
        $this->assertEquals('UPDATE `table` SET `foo` = :bind ' . $expected, $query->build());
    }

    // DELETE
    public function testDelete()
    {
        $query = new Query('table', 't', Query::OPERATION_DELETE);
        $this->assertEquals('DELETE FROM `table`', $query->build());
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testDeleteWithConditions($conditions, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_DELETE);

        foreach ($conditions as $condition) {
            $query->where(
                  $condition[0],
                  $condition[1],
                  isset($condition[2]) ? $condition[2] : Query::COMPARISON_EQUAL,
                  isset($condition[3]) ? $condition[3] : Query::LOGICAL_AND
            );
        }

        $this->assertEquals('DELETE FROM `table` ' . $expected, $query->build());
    }

    /**
     * @dataProvider limitProvider
     */
    public function testDeleteWithLimit($limit, $offset, $expected)
    {
        $query = new Query('table', 't', Query::OPERATION_DELETE);
        $query->fields(array('foo'));
        $query->limit($limit, $offset);
        $this->assertEquals('DELETE FROM `table` ' . $expected, $query->build());
    }

    // TRUNCATE
    public function testTruncate()
    {
        $query = new Query('table', 't', Query::OPERATION_CLEAR);
        $this->assertEquals('TRUNCATE TABLE `table`', $query->build());
    }

    public function conditionProvider()
    {
        return array(
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_EQUAL)
                ),
                'WHERE `foo` = :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_NOT_EQUAL)
                ),
                'WHERE `foo` != :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_LESS)
                ),
                'WHERE `foo` < :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_LESS_EQUAL)
                ),
                'WHERE `foo` <= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_GREATER)
                ),
                'WHERE `foo` > :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_GREATER_EQUAL)
                ),
                'WHERE `foo` >= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_LIKE)
                ),
                'WHERE `foo` LIKE :bind'
            ),
            array(
                array(
                    array('foo', ':bind', Query::COMPARISON_REGEX)
                ),
                'WHERE `foo` REGEX :bind'
            ),
            array(
                array(
                    array('foo', array(':bind1', ':bind2'))
                ),
                'WHERE (`foo` = :bind1 OR `foo` = :bind2)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), ':bind')
                ),
                'WHERE (`foo` = :bind OR `bar` = :bind)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), array(':bind1', ':bind2'))
                ),
                'WHERE (`foo` = :bind1 OR `bar` = :bind2)'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, Query::LOGICAL_AND),
                    array('bar', ':bindBar', null, Query::LOGICAL_AND)
                ),
                'WHERE `foo` = :bindFoo AND `bar` = :bindBar'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, Query::LOGICAL_OR),
                    array('bar', ':bindBar', null, Query::LOGICAL_OR)
                ),
                'WHERE `foo` = :bindFoo OR `bar` = :bindBar'
            )
        );
    }
}