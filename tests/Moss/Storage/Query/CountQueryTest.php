<?php
namespace Moss\Storage\Query;


class CountQueryTest extends QueryMocks
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testWhereSimple()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andWhere')
            ->with('`bar` = :condition_0_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andWhere')
            ->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andWhere')
            ->with('(`foo` = :condition_0_foo and `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andWhere')
            ->with('(`bar` = :condition_0_bar or `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andWhere')
            ->with('((`foo` = :condition_0_foo or `foo` = :condition_1_foo) and (`bar` = :condition_2_bar or `bar` = :condition_3_bar))');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andWhere')
            ->with($expected);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
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
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where('bar', 'barbar', 'xyz', 'and');
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testWhereLogicalOperators($operator)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');

        switch ($operator) {
            case 'or':
                $builder->expects($this->at(4))
                    ->method('orWhere')
                    ->with('`bar` = :condition_0_bar');
                break;
            case 'and':
            default:
                $builder->expects($this->at(4))
                    ->method('andWhere')
                    ->with('`bar` = :condition_0_bar');
        }


        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
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
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where('bar', 'barbar', '=', 'xyz');
    }

    public function testHavingSimple()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andHaving')
            ->with('`bar` = :condition_0_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->having('bar', 'barbar', '=', 'and');
    }

    public function testHavingWithNullValue()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andHaving')
            ->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->having('bar', null, '=', 'and');
    }

    public function testHavingWithMultipleFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andHaving')
            ->with('(`foo` = :condition_0_foo and `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->having(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testHavingWithMultipleValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andHaving')
            ->with('(`bar` = :condition_0_bar or `bar` = :condition_1_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->having('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testHavingWithMultipleFieldsAndValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andHaving')
            ->with('((`foo` = :condition_0_foo or `foo` = :condition_1_foo) and (`bar` = :condition_2_bar or `bar` = :condition_3_bar))');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->having(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testHavingComparisonOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');
        $builder->expects($this->at(4))
            ->method('andHaving')
            ->with($expected);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
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
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->having('bar', 'barbar', 'xyz', 'and');
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testHavingLogicalOperators($operator)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('select')
            ->with(null);
        $builder->expects($this->at(1))
            ->method('from')
            ->with('`table`');
        $builder->expects($this->at(2))
            ->method('select')
            ->with([]);
        $builder->expects($this->at(3))
            ->method('addSelect')
            ->with('`foo`');

        switch ($operator) {
            case 'or':
                $builder->expects($this->at(4))
                    ->method('orHaving')
                    ->with('`bar` = :condition_0_bar');
                break;
            case 'and':
            default:
                $builder->expects($this->at(4))
                    ->method('andHaving')
                    ->with('`bar` = :condition_0_bar');
        }

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
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
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
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

    public function testWith()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

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
            ->method('build')
            ->willReturn($relation);

        $query = new CountQuery($dbal, $model, $factory);
        $query->with('relation');
    }

    public function testRelation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())
            ->method('name')
            ->willReturn('relation');

        $factory = $this->mockRelFactory();
        $factory->expects($this->once())
            ->method('build')
            ->willReturn($relation);

        $query = new CountQuery($dbal, $model, $factory);
        $result = $query->with('relation')
            ->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

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

        $query = new CountQuery($dbal, $model, $factory);
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

        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('rowCount')
            ->willReturn(10);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new CountQuery($dbal, $model, $factory);
        $query->with('relation');

        $this->assertEquals(10, $query->execute());
    }

    public function testQueryString()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->where('foo', 'foo');
        $this->assertEquals([':condition_0_foo' => ['string', 'foo']], $query->binds());
    }

    public function testReset()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new CountQuery($dbal, $model, $factory);
        $query->reset();
    }
}
