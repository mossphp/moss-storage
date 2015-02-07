<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class ReadQueryTest extends QueryMocks
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]);
        $builder->expects($this->at(6))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(7))->method('addSelect')->with('`bar`');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->fields(['foo', 'bar']);
    }

    public function testField()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]);
        $builder->expects($this->at(6))->method('addSelect')->with('`foo`');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->fields([]);
        $query->field('foo');
    }

    public function testFieldWithMapping()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo_foo` AS `foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]);
        $builder->expects($this->at(6))->method('addSelect')->with('`foo_foo` AS `foo`');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', [['foo', 'string', [], 'foo_foo'], 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->fields([]);
        $query->field('foo');
    }

    /**
     * @dataProvider aggregateProvider
     */
    public function testAggregate($method)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with(sprintf('%s(`foo`) AS `alias`', $method));

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        call_user_func([$query, $method], 'foo', 'alias');
    }

    public function aggregateProvider()
    {
        return [
            ['distinct'],
            ['count'],
            ['average'],
            ['min'],
            ['max'],
            ['sum']
        ];
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Invalid aggregation method
     */
    public function testAggregateUnsupportedMethod()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->aggregate('foo', 'foo');
    }

    public function testGroup()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('addGroupBy')->with('`foo`');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->group('foo');
    }

    public function testWhereSimple()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andWhere')->with('`bar` = :condition_0_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andWhere')->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andWhere')->with('(`foo` = :condition_0_foo and `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andWhere')->with('(`bar` = :condition_0_bar or `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andWhere')->with('((`foo` = :condition_0_foo or `foo` = :condition_1_foo) and (`bar` = :condition_2_bar or `bar` = :condition_3_bar))');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andWhere')->with($expected);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', 'barbar', $operator, 'and');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testWhereUnsupportedConditionalOperator()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', 'barbar', 'xyz', 'and');
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testWhereLogicalOperators($operator)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');

        switch ($operator) {
            case 'or':
                $builder->expects($this->at(5))->method('orWhere')->with('`bar` = :condition_0_bar');
                break;
            case 'and':
            default:
                $builder->expects($this->at(5))->method('andWhere')->with('`bar` = :condition_0_bar');
        }


        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testWhereUnsupportedLogicalOperator()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', 'xyz');
    }

    public function testHavingSimple()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andHaving')->with('`bar` = :condition_0_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', 'barbar', '=', 'and');
    }

    public function testHavingWithNullValue()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andHaving')->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', null, '=', 'and');
    }

    public function testHavingWithMultipleFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andHaving')->with('(`foo` = :condition_0_foo and `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testHavingWithMultipleValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andHaving')->with('(`bar` = :condition_0_bar or `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testHavingWithMultipleFieldsAndValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andHaving')->with('((`foo` = :condition_0_foo or `foo` = :condition_1_foo) and (`bar` = :condition_2_bar or `bar` = :condition_3_bar))');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testHavingComparisonOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('andHaving')->with($expected);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', 'barbar', $operator, 'and');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testHavingUnsupportedConditionalOperator()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', 'barbar', 'xyz', 'and');
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testHavingLogicalOperators($operator)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with(null);
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]);
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');

        switch ($operator) {
            case 'or':
                $builder->expects($this->at(5))->method('orHaving')->with('`bar` = :condition_0_bar');
                break;
            case 'and':
            default:
                $builder->expects($this->at(5))->method('andHaving')->with('`bar` = :condition_0_bar');
        }

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', 'barbar', '=', $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testHavingUnsupportedLogicalOperator()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->having('bar', 'barbar', '=', 'xyz');
    }

    public function comparisonOperatorsProvider()
    {
        return [
            ['=', '`bar` = :condition_0_bar'],
            ['!=', '`bar` != :condition_0_bar'],
            ['>', '`bar` > :condition_0_bar'],
            ['>=', '`bar` >= :condition_0_bar'],
            ['<', '`bar` < :condition_0_bar'],
            ['<=', '`bar` <= :condition_0_bar'],
            ['like', '`bar` like :condition_0_bar'],
            ['regexp', '`bar` regexp :condition_0_bar'],
        ];
    }

    public function logicalOperatorsProvider()
    {
        return [
            ['and'],
            ['or']
        ];
    }

    /**
     * @dataProvider orderProvider
     */
    public function testOrder($order)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('addOrderBy')->with('`foo`', $order);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->order('foo', $order);
    }

    public function orderProvider()
    {
        return [
            ['asc'],
            ['desc']
        ];
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unsupported sorting method
     */
    public function testOrderUnsupportedSorting()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->order('foo', 'xyz');
    }

    public function testLimitWithOffset()
    {
        $limit = 10;
        $offset = 20;

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('setFirstResult')->with($offset);
        $builder->expects($this->once())->method('setMaxResults')->with($limit);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->limit($limit, $offset);
    }

    public function testLimitWithoutOffset()
    {
        $limit = 10;

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->never())->method('setFirstResult');
        $builder->expects($this->once())->method('setMaxResults')->with($limit);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->limit($limit);
    }

    public function testWith()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();

        $factory = $this->mockRelFactory();
        $factory->expects($this->at(0))
            ->method('reset')
            ->with()
            ->willReturnSelf();
        $factory->expects($this->at(1))
            ->method('relation')
            ->with($model, 'relation')
            ->willReturnSelf();
        $factory->expects($this->at(2))
            ->method('where')
            ->with('foo', 'bar', '=', 'and')
            ->willReturnSelf();
        $factory->expects($this->at(3))
            ->method('order')
            ->with('foo', 'asc')
            ->willReturnSelf();
        $factory->expects($this->at(4))
            ->method('limit')
            ->with(1, 2)
            ->willReturnSelf();
        $factory->expects($this->at(5))
            ->method('build')
            ->with()
            ->willReturn($relation);

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc'], 1, 2);
    }

    public function testRelation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->any())
            ->method('name')
            ->willReturn('relation');

        $factory = $this->mockRelFactory();
        $factory->expects($this->once())
            ->method('build')
            ->willReturn($relation);

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $result = $query->with('relation')
            ->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->any())
            ->method('name')
            ->willReturn('relation');

        $relation->expects($this->any())
            ->method('relation')
            ->willReturnSelf(); // hack so we can have nested relation

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $result = $query->with('relation.relation')
            ->relation('relation.relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unable to retrieve relation
     */
    public function testRelationWithoutPriorCreation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecuteEntitiesAsArray()
    {
        $result = [['foo' => 'foo']];

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('fetchAll')
            ->willReturn($result);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->once())
            ->method('read')
            ->with($result)
            ->willReturn($result);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntitiesAsObjectsWithPublicProperties()
    {
        $result = [ (object) ['foo' => 'foo']];

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('fetchAll')
            ->willReturn($result);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->once())
            ->method('read')
            ->with($result)
            ->willReturn($result);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntitiesAsObjectsWithProtectedProperties()
    {
        $result = [ new TestEntity('foo') ];

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('fetchAll')
            ->willReturn($result);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->once())
            ->method('read')
            ->with($result)
            ->willReturn($result);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testQueryString()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->where('foo', 'foo');
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new ReadQuery($dbal, $model, $converter, $factory);
        $query->reset();
    }
}
