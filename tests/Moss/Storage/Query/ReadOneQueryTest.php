<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class ReadOneQueryTest extends QueryMocks
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->exactly(3))->method('select')->with([]);
        $builder->expects($this->once())->method('from')->with('`table`');
        $builder->expects($this->exactly(4))->method('addSelect')->withConsecutive(
            ['`foo`'],
            ['`bar`'],
            ['`foo`'],
            ['`bar`']
        );

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->fields(['foo', 'bar']);
    }

    public function testField()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->exactly(2))->method('select')->with([]);
        $builder->expects($this->once())->method('from')->with('`table`');
        $builder->expects($this->exactly(3))->method('addSelect')->withConsecutive(
            ['`foo`'],
            ['`bar`'],
            ['`foo`']
        );

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->field('foo');
    }

    public function testFieldWithMapping()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->exactly(2))->method('select')->with([]);
        $builder->expects($this->once())->method('from')->with('`table`');
        $builder->expects($this->exactly(2))->method('addSelect')->withConsecutive(
            ['`foo_foo` AS `foo`'],
            ['`bar`']
        );

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', [['foo', 'string', [], 'foo_foo'], 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
    }

    public function testWhereSimple()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with('`bar` = :condition_0_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $builder = $this->mockQueryBuilder();

        $builder->expects($this->once())->method('andWhere')->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(`foo` = :condition_\d_foo and `bar` = :condition_\d_bar\)$/'));

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(`bar` = :condition_\d_bar or `bar` = :condition_\d_bar\)$/'));

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(\(`foo` = :condition_\d_foo or `foo` = :condition_\d_foo\) and \(`bar` = :condition_\d_bar or `bar` = :condition_\d_bar\)\)$/'));

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($expected);


        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
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

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where('bar', 'barbar', 'xyz', 'and');
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

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testWhereLogicalOperators($operator)
    {
        $builder = $this->mockQueryBuilder();

        switch ($operator) {
            case 'or':
                $builder->expects($this->once())->method('orWhere')->with('`bar` = :condition_0_bar');
                break;
            case 'and':
            default:
                $builder->expects($this->once())->method('andWhere')->with('`bar` = :condition_0_bar');
        }


        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
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

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where('bar', 'barbar', '=', 'xyz');
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
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
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
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->order('foo', 'xyz');
    }

    public function testLimitWithOffset()
    {
        $limit = 10;
        $offset = 20;

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('setFirstResult')->with($offset);
        $builder->expects($this->exactly(2))->method('setMaxResults')->withConsecutive(
            [1],
            [$limit]
        );

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->limit($limit, $offset);
    }

    public function testLimitWithoutOffset()
    {
        $limit = 10;

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->never())->method('setFirstResult');
        $builder->expects($this->exactly(2))->method('setMaxResults')->withConsecutive(
            [1],
            [$limit]
        );

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->limit($limit);
    }

    public function testWith()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $factory = $this->mockRelFactory();
        $factory->expects($this->at(0))->method('reset')->with()->willReturnSelf();
        $factory->expects($this->at(1))->method('relation')->with($model, 'relation')->willReturnSelf();
        $factory->expects($this->at(2))->method('where')->with('foo', 'bar', '=', 'and')->willReturnSelf();
        $factory->expects($this->at(3))->method('order')->with('foo', 'asc')->willReturnSelf();
        $factory->expects($this->at(4))->method('limit')->with(1, 2)->willReturnSelf();
        $factory->expects($this->at(5))->method('build')->with()->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc'], 1, 2);
    }

    public function testRelation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $factory = $this->mockRelFactory();
        $factory->expects($this->once())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);
        $result = $query->with('relation')->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $relation->expects($this->any())->method('relation')->willReturnSelf(); // hack so we can have nested relation

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);
        $result = $query->with('relation.relation')->relation('relation.relation');

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

        $query = new ReadOneQuery($dbal, $model, $factory);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $dbal = $this->mockDBAL($builder);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);

        $this->assertEquals(10, $query->count());
    }

    public function testExecuteEntitiesAsArray()
    {
        $result = [['foo' => 'foo']];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $dbal = $this->mockDBAL($builder);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('read')->with($result)->willReturn($result);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntitiesAsObjectsWithPublicProperties()
    {
        $result = [(object) ['foo' => 'foo']];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $dbal = $this->mockDBAL($builder);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('read')->with($result)->willReturn($result);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntitiesAsObjectsWithProtectedProperties()
    {
        $result = [new TestEntity('foo')];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('read')->with($result)->willReturn($result);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteReturnsOneEntity()
    {
        $result = [['foo' => 'foo']];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('execute')->willReturn($stmt);

        $dbal = $this->mockDBAL($builder);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $entity = $query->execute();

        $this->assertSame($result[0], $entity);
    }

    public function testQueryString()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->getSQL();
    }

    public function testBinds()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('getParameters')->willReturn([':condition_0_foo' => 'foo']);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->where('foo', 'foo');
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ReadOneQuery($dbal, $model, $factory);
        $query->reset();
    }
}
