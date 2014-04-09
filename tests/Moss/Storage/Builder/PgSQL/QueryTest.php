<?php
namespace Moss\Storage\Builder\PgSQL;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testMissingTable()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->reset()
            ->operation('select')
            ->fields(array('foo', 'bar'))
            ->build();
    }

    public function testTable()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->reset()
            ->table('foobar', 'fb')
            ->operation('select')
            ->fields(array('foo', 'bar'))
            ->build();

        $this->assertEquals('SELECT "fb"."foo", "fb"."bar" FROM "foobar" AS "fb"', $query->build());
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testTableWithEmptyString()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->reset()
            ->table('', 'fb');
    }

    public function operationProvider()
    {
        return array(
            array('SELECT "table"."foo", "table"."bar" FROM "table"', 'select'),
            array('INSERT INTO "table" ("foo", "bar") VALUES (:bind1, :bind2)', 'insert'),
            array('UPDATE "table" SET "foo" = :bind1, "bar" = :bind2', 'update'),
            array('DELETE FROM "table"', 'delete'),
            array('TRUNCATE TABLE "table"', 'clear'),
        );
    }

    /**
     * @dataProvider operationProvider
     */
    public function testOperation($expected, $operation)
    {
        $query = new QueryBuilder();
        $query->table('table')
            ->operation($operation)
            ->fields(array('foo', 'bar'))
            ->value('foo', ':bind1')
            ->value('bar', ':bind2')
            ->build();

        $this->assertEquals($expected, $query->build());
    }

    /**
     * @dataProvider operationProvider
     */
    public function testOperationAliases($expected, $operation)
    {
        $query = new QueryBuilder();

        call_user_func(array($query, $operation), 'table');

        $query->fields(array('foo', 'bar'))
            ->value('foo', ':bind1')
            ->value('bar', ':bind2')
            ->build();

        $this->assertEquals($expected, $query->build());
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->reset()
            ->table('table')
            ->operation('foo')
            ->fields(array('foo', 'bar'))
            ->build();
    }

    // SELECT

    public function testSelectWithoutFields()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $this->assertEquals('SELECT * FROM "table" AS "t"', $query->build());
    }

    public function testSelect()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo', 'bar', 'yada_yada' => 'yada'));
        $this->assertEquals('SELECT "t"."foo", "t"."bar", "t"."yada_yada" AS "yada" FROM "table" AS "t"', $query->build());
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports comparison operator
     */
    public function testSelectWithInvalidComparison()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'))
            ->where('foo', ':bind', '!!');
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports logical operator
     */
    public function testSelectWithInvalidLogical()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'))
            ->where('foo', ':bind', '=', 'BOO');
    }

    public function testSelectWithSubQuery()
    {
        $sub = new QueryBuilder('bar', 'b');
        $sub->fields(array('bar'));

        $query = new QueryBuilder('foo', 'f', 'select');
        $query->fields(array('foo'))
            ->sub($sub, 'b');

        $this->assertEquals('SELECT "f"."foo", ( SELECT "b"."bar" FROM "bar" AS "b" ) AS "b" FROM "foo" AS "f"', $query->build());
    }

    /**
     * @dataProvider aliasedConditionProvider
     */
    public function testSelectWithWhere($conditions, $expected)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'));

        foreach ($conditions as $condition) {
            $query->where(
                $condition[0],
                $condition[1],
                isset($condition[2]) ? $condition[2] : '=',
                isset($condition[3]) ? $condition[3] : 'and'
            );
        }

        $this->assertEquals('SELECT "t"."foo" FROM "table" AS "t" WHERE ' . $expected, $query->build());
    }

    /**
     * @dataProvider aliasedConditionProvider
     */
    public function testSelectWithHaving($conditions, $expected)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'));

        foreach ($conditions as $condition) {
            $query->having(
                $condition[0],
                $condition[1],
                isset($condition[2]) ? $condition[2] : '=',
                isset($condition[3]) ? $condition[3] : 'and'
            );
        }

        $this->assertEquals('SELECT "t"."foo" FROM "table" AS "t" HAVING ' . $expected, $query->build());
    }

    public function aliasedConditionProvider()
    {
        return array(
            array(
                array(
                    array('foo', ':bind', '=')
                ),
                '"t"."foo" = :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '!=')
                ),
                '"t"."foo" != :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '<')
                ),
                '"t"."foo" < :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '<=')
                ),
                '"t"."foo" <= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '>')
                ),
                '"t"."foo" > :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '>=')
                ),
                '"t"."foo" >= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', 'like')
                ),
                '"t"."foo" LIKE :bind'
            ),
            array(
                array(
                    array('foo', ':bind', 'regex')
                ),
                'LOWER("t"."foo") ~ LOWER(:bind)'
            ),
            array(
                array(
                    array('foo', array(':bind1', ':bind2'))
                ),
                '("t"."foo" = :bind1 OR "t"."foo" = :bind2)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), ':bind')
                ),
                '("t"."foo" = :bind OR "t"."bar" = :bind)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), array(':bind1', ':bind2'))
                ),
                '("t"."foo" = :bind1 OR "t"."bar" = :bind2)'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, 'and'),
                    array('bar', ':bindBar', null, 'and')
                ),
                '"t"."foo" = :bindFoo AND "t"."bar" = :bindBar'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, 'or'),
                    array('bar', ':bindBar', null, 'or')
                ),
                '"t"."foo" = :bindFoo OR "t"."bar" = :bindBar'
            )
        );
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports aggregation method
     */
    public function testSelectWithInvalidAggregate()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->aggregate('foo', 'bar');
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testSelectWithAggregate($expected, $method)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'))
            ->aggregate($method, 'bar');

        $this->assertEquals('SELECT ' . $expected . ', "t"."foo" FROM "table" AS "t"', $query->build());
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testSelectWithAggregateAliases($expected, $method)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'));

        call_user_func(array($query, $method), 'bar');

        $this->assertEquals('SELECT ' . $expected . ', "t"."foo" FROM "table" AS "t"', $query->build());
    }

    public function aggregateProvider()
    {
        return array(
            array('DISTINCT("t"."bar") AS "distinct"', 'distinct'),
            array('COUNT("t"."bar") AS "count"', 'count'),
            array('AVERAGE("t"."bar") AS "average"', 'average'),
            array('MIN("t"."bar") AS "min"', 'min'),
            array('MAX("t"."bar") AS "max"', 'max'),
            array('SUM("t"."bar") AS "sum"', 'sum')
        );
    }

    public function testSelectWithGroup()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'))
            ->group('bar');

        $this->assertEquals('SELECT "t"."foo" FROM "table" AS "t" GROUP BY "t"."bar"', $query->build());
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports order method
     */
    public function testSelectWithInvalidOrder()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->order('foo', 'bar');
    }

    /**
     * @dataProvider orderProvider
     */
    public function testSelectWithOrder($field, $order, $expected)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'));
        $query->order($field, $order);
        $this->assertEquals('SELECT "t"."foo" FROM "table" AS "t" ' . $expected, $query->build());
    }

    public function orderProvider()
    {
        return array(
            array('foo', 'asc', 'ORDER BY "t"."foo" ASC'),
            array('foo', 'desc', 'ORDER BY "t"."foo" DESC'),
            array('foo', array('one', 'two', 'three'), 'ORDER BY "t"."foo" = one DESC, "t"."foo" = two DESC, "t"."foo" = three DESC'),
        );
    }

    /**
     * @dataProvider limitProvider()
     */
    public function testSelectWithLimit($limit, $offset, $expected)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo'));
        $query->limit($limit, $offset);
        $this->assertEquals('SELECT "t"."foo" FROM "table" AS "t" ' . $expected, $query->build());
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
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Query builder does not supports join type
     */
    public function testSelectWithInvalidJoin()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        $query->join('foo', 'bar', array('f' => 'b'));
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Empty join array for join type
     */
    public function testSelectWithInvalidJoinsArray()
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        $query->join('inner', 'bar', array());
    }

    /**
     * @dataProvider joinProvider
     */
    public function testSelectWithJoin($join, $table, $joins, $alias, $expected)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        $query->join($join, $table, $joins, $alias);
        $this->assertEquals($expected, $query->build());
    }

    /**
     * @dataProvider joinProvider
     */
    public function testSelectWithJoinAliases($join, $table, $joins, $alias, $expected)
    {
        $query = new QueryBuilder('table', 't', 'select');
        $query->fields(array('foo', 'bar.bar' => 'barbar', 'b.bar' => 'bbar'));

        call_user_func(array($query, $join . 'Join'), $table, $joins, $alias);

        $this->assertEquals($expected, $query->build());
    }

    public function joinProvider()
    {
        return array(
            array(
                'inner',
                'bar',
                array('foo' => 'bar'),
                null,
                'SELECT "t"."foo", "bar"."bar" AS "barbar", "b"."bar" AS "bbar" FROM "table" AS "t" INNER JOIN "bar" ON "t"."foo" = "bar"."bar"'
            ),
            array(
                'inner',
                'bar',
                array('foo' => 'bar'),
                'b',
                'SELECT "t"."foo", "bar"."bar" AS "barbar", "b"."bar" AS "bbar" FROM "table" AS "t" INNER JOIN "bar" AS "b" ON "t"."foo" = "b"."bar"'
            ),
            array(
                'inner',
                'bar',
                array('foo' => 'bar'),
                'b',
                'SELECT "t"."foo", "bar"."bar" AS "barbar", "b"."bar" AS "bbar" FROM "table" AS "t" INNER JOIN "bar" AS "b" ON "t"."foo" = "b"."bar"'
            ),
            array(
                'left',
                'bar',
                array('foo' => 'bar'),
                null,
                'SELECT "t"."foo", "bar"."bar" AS "barbar", "b"."bar" AS "bbar" FROM "table" AS "t" LEFT OUTER JOIN "bar" ON "t"."foo" = "bar"."bar"'
            ),
            array(
                'right',
                'bar',
                array('foo' => 'bar'),
                null,
                'SELECT "t"."foo", "bar"."bar" AS "barbar", "b"."bar" AS "bbar" FROM "table" AS "t" RIGHT OUTER JOIN "bar" ON "t"."foo" = "bar"."bar"'
            ),
        );
    }

    // INSERT
    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage No values to insert
     */
    public function testInsertWithoutValues()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->build();
    }

    public function testInsertSingleValue()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->value('foo', ':bind');
        $this->assertEquals('INSERT INTO "table" ("foo") VALUE (:bind)', $query->build());
    }

    public function testInsertMultipleValues()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->value('foo', ':bind1');
        $query->value('bar', ':bind2');
        $this->assertEquals('INSERT INTO "table" ("foo", "bar") VALUES (:bind1, :bind2)', $query->build());
    }

    public function testInsertValuesArray()
    {
        $query = new QueryBuilder('table', 't', 'insert');
        $query->values(
            array(
                'foo' => ':bind1',
                'bar' => ':bind2'
            )
        );
        $this->assertEquals('INSERT INTO "table" ("foo", "bar") VALUES (:bind1, :bind2)', $query->build());
    }

    // UPDATE

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage No values to update
     */
    public function testUpdateWithoutValues()
    {
        $query = new QueryBuilder('table', 't', 'update');
        $query->build();
    }

    public function testUpdate()
    {
        $query = new QueryBuilder('table', 't', 'update');
        $query->value('foo', ':bind');
        $this->assertEquals('UPDATE "table" SET "foo" = :bind', $query->build());
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testUpdateWithConditions($conditions, $expected)
    {
        $query = new QueryBuilder('table', 't', 'update');
        $query->value('foo', ':bind');

        foreach ($conditions as $condition) {
            $query->where(
                $condition[0],
                $condition[1],
                isset($condition[2]) ? $condition[2] : '=',
                isset($condition[3]) ? $condition[3] : 'and'
            );
        }

        $this->assertEquals('UPDATE "table" SET "foo" = :bind ' . $expected, $query->build());
    }

    /**
     * @dataProvider limitProvider
     */
    public function testUpdateWithLimit($limit, $offset, $expected)
    {
        $query = new QueryBuilder('table', 't', 'update');
        $query->value('foo', ':bind');
        $query->limit($limit, $offset);
        $this->assertEquals('UPDATE "table" SET "foo" = :bind ' . $expected, $query->build());
    }

    // DELETE
    public function testDelete()
    {
        $query = new QueryBuilder('table', 't', 'delete');
        $this->assertEquals('DELETE FROM "table"', $query->build());
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testDeleteWithConditions($conditions, $expected)
    {
        $query = new QueryBuilder('table', 't', 'delete');

        foreach ($conditions as $condition) {
            $query->where(
                $condition[0],
                $condition[1],
                isset($condition[2]) ? $condition[2] : '=',
                isset($condition[3]) ? $condition[3] : 'and'
            );
        }

        $this->assertEquals('DELETE FROM "table" ' . $expected, $query->build());
    }

    /**
     * @dataProvider limitProvider
     */
    public function testDeleteWithLimit($limit, $offset, $expected)
    {
        $query = new QueryBuilder('table', 't', 'delete');
        $query->fields(array('foo'));
        $query->limit($limit, $offset);
        $this->assertEquals('DELETE FROM "table" ' . $expected, $query->build());
    }

    // TRUNCATE
    public function testTruncate()
    {
        $query = new QueryBuilder('table', 't', 'clear');
        $this->assertEquals('TRUNCATE TABLE "table"', $query->build());
    }

    public function conditionProvider()
    {
        return array(
            array(
                array(
                    array('foo', ':bind', '=')
                ),
                'WHERE "foo" = :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '!=')
                ),
                'WHERE "foo" != :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '<')
                ),
                'WHERE "foo" < :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '<=')
                ),
                'WHERE "foo" <= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '>')
                ),
                'WHERE "foo" > :bind'
            ),
            array(
                array(
                    array('foo', ':bind', '>=')
                ),
                'WHERE "foo" >= :bind'
            ),
            array(
                array(
                    array('foo', ':bind', 'like')
                ),
                'WHERE "foo" LIKE :bind'
            ),
            array(
                array(
                    array('foo', ':bind', 'regex')
                ),
                'WHERE LOWER("foo") ~ LOWER(:bind)'
            ),
            array(
                array(
                    array('foo', array(':bind1', ':bind2'))
                ),
                'WHERE ("foo" = :bind1 OR "foo" = :bind2)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), ':bind')
                ),
                'WHERE ("foo" = :bind OR "bar" = :bind)'
            ),
            array(
                array(
                    array(array('foo', 'bar'), array(':bind1', ':bind2'))
                ),
                'WHERE ("foo" = :bind1 OR "bar" = :bind2)'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, 'and'),
                    array('bar', ':bindBar', null, 'and')
                ),
                'WHERE "foo" = :bindFoo AND "bar" = :bindBar'
            ),
            array(
                array(
                    array('foo', ':bindFoo', null, 'or'),
                    array('bar', ':bindBar', null, 'or')
                ),
                'WHERE "foo" = :bindFoo OR "bar" = :bindBar'
            )
        );
    }
}