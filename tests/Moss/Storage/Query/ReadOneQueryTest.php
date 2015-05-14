<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class ReadOneQueryTest extends QueryMocks
{
    /**
     * @var \Doctrine\DBAL\Query\QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $builder;

    /**
     * @var \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dbal;

    /**
     * @var \Moss\Storage\Query\Relation\RelationFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    /**
     * @var \Moss\Storage\Query\Accessor\AccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $accessor;

    public function setUp()
    {
        $this->builder = $this->mockQueryBuilder();
        $this->dbal = $this->mockDBAL($this->builder);
        $this->factory = $this->mockRelFactory();
        $this->accessor = $this->mockAccessor();
    }

    public function testConnection()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);

        $this->assertSame($this->dbal, $query->connection());
    }

    public function testFields()
    {
        $this->builder->expects($this->exactly(3))->method('select')->with([]);
        $this->builder->expects($this->once())->method('from')->with('`table`');
        $this->builder->expects($this->exactly(4))->method('addSelect')->withConsecutive(
            ['`foo`'],
            ['`bar`'],
            ['`foo`'],
            ['`bar`']
        );

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->fields(['foo', 'bar']);
    }

    public function testField()
    {
        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->once())->method('from')->with('`table`');
        $this->builder->expects($this->exactly(3))->method('addSelect')->withConsecutive(
            ['`foo`'],
            ['`bar`'],
            ['`foo`']
        );

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->field('foo');
    }

    public function testFieldWithMapping()
    {
        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->once())->method('from')->with('`table`');
        $this->builder->expects($this->exactly(2))->method('addSelect')->withConsecutive(
            ['`foo_foo` AS `foo`'],
            ['`bar`']
        );

        $model = $this->mockModel('\\stdClass', 'table', [['foo', 'string', [], 'foo_foo'], 'bar'], ['foo']);

        new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
    }

    public function testWhereSimple()
    {
        $this->builder->expects($this->once())->method('andWhere')->with('`bar` = :condition_0_bar');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $this->builder->expects($this->once())->method('andWhere')->with('`bar` IS NULL');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(`foo` = :condition_\d_foo and `bar` = :condition_\d_bar\)$/'));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(`bar` = :condition_\d_bar or `bar` = :condition_\d_bar\)$/'));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(\(`foo` = :condition_\d_foo or `foo` = :condition_\d_foo\) and \(`bar` = :condition_\d_bar or `bar` = :condition_\d_bar\)\)$/'));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $this->builder->expects($this->once())->method('andWhere')->with($expected);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where('bar', 'barbar', $operator, 'and');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testWhereUnsupportedConditionalOperator()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
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
        switch ($operator) {
            case 'or':
                $this->builder->expects($this->once())->method('orWhere')->with('`bar` = :condition_0_bar');
                break;
            case 'and':
            default:
                $this->builder->expects($this->once())->method('andWhere')->with('`bar` = :condition_0_bar');
        }

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where('bar', 'barbar', '=', $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testWhereUnsupportedLogicalOperator()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
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
        $this->builder->expects($this->once())->method('addOrderBy')->with('`foo`', $order);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
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
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->order('foo', 'xyz');
    }

    public function testLimitWithOffset()
    {
        $limit = 10;
        $offset = 20;

        $this->builder->expects($this->once())->method('setFirstResult')->with($offset);
        $this->builder->expects($this->exactly(2))->method('setMaxResults')->withConsecutive(
            [1],
            [$limit]
        );

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->limit($limit, $offset);
    }

    public function testLimitWithoutOffset()
    {
        $limit = 10;

        $this->builder->expects($this->never())->method('setFirstResult');
        $this->builder->expects($this->exactly(2))->method('setMaxResults')->withConsecutive(
            [1],
            [$limit]
        );

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->limit($limit);
    }

    public function testWith()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->once())->method('build')->willReturn($model, 'relation')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc'], 1, 2);
    }

    public function testRelation()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $this->factory->expects($this->once())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $result = $query->with('relation')->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');
        $relation->expects($this->any())->method('relation')->willReturnSelf(); // hack so we can have nested relation

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $result = $query->with('relation.relation')->relation('relation.relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unable to retrieve relation
     */
    public function testRelationWithoutPriorCreation()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);

        $this->assertEquals(10, $query->count());
    }

    public function testExecuteEntitiesAsArray()
    {
        $result = [['foo' => 'foo']];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('read')->with($result)->willReturn($result);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntitiesAsObjectsWithPublicProperties()
    {
        $result = [(object) ['foo' => 'foo']];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('read')->with($result)->willReturn($result);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntitiesAsObjectsWithProtectedProperties()
    {
        $result = [new TestEntity('foo')];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $this->dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('read')->with($result)->willReturn($result);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteReturnsOneEntity()
    {
        $result = [['foo' => 'foo']];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $this->builder->expects($this->any())->method('execute')->willReturn($stmt);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $entity = $query->execute();

        $this->assertSame($result[0], $entity);
    }

    public function testQueryString()
    {
        $this->builder->expects($this->once())->method('getSQL');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->getSQL();
    }

    public function testBinds()
    {
        $this->builder->expects($this->any())->method('getParameters')->willReturn([':condition_0_foo' => 'foo']);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->where('foo', 'foo');
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $this->builder->expects($this->once())->method('resetQueryParts');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->reset();
    }

    public function testCustomQueryCount()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $this->dbal->expects($this->once())->method('executeQuery')->with('CUSTOM SQL QUERY', ['param' => 'val'])->willReturn($stmt);
        $this->builder->expects($this->never())->method('execute');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->query('CUSTOM SQL QUERY', ['param' => 'val'])->count();
    }

    public function testCustomQueryExecution()
    {
        $result = [new TestEntity('foo')];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('fetchAll')->willReturn($result);

        $this->dbal->expects($this->once())->method('executeQuery')->with('CUSTOM SQL QUERY', ['param' => 'val'])->willReturn($stmt);
        $this->builder->expects($this->never())->method('execute');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadOneQuery($this->dbal, $model, $this->factory, $this->accessor);
        $query->query('CUSTOM SQL QUERY', ['param' => 'val'])->execute();
    }
}
