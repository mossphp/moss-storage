<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class ReadQueryTest extends QueryMocks
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

    /**
     * @var \Moss\Storage\Query\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    public function setUp()
    {
        $this->builder = $this->mockQueryBuilder();
        $this->dbal = $this->mockDBAL($this->builder);
        $this->factory = $this->mockRelFactory();
        $this->accessor = $this->mockAccessor();
        $this->dispatcher = $this->mockEventDispatcher();
    }
    
    public function testConnection()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);

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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->field('foo');
    }

    public function testFieldWithMapping()
    {
        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->once())->method('from')->with('`table`');
        $this->builder->expects($this->exactly(3))->method('addSelect')->withConsecutive(
            ['`foo_foo` AS `foo`'],
            ['`bar`']
        );

        $model = $this->mockModel('\\stdClass', 'table', [['foo', 'string', [], 'foo_foo'], 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->field('foo');
    }

    public function testWhereSimple()
    {
        $this->builder->expects($this->once())->method('andWhere')->with('`bar` = :dcValue1');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $this->builder->expects($this->once())->method('andWhere')->with('`bar` IS NULL');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(`foo` = :dcValue\d+ and `bar` = :dcValue\d+\)$/'));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(`bar` = :dcValue\d+ or `bar` = :dcValue\d+\)$/'));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^\(\(`foo` = :dcValue\d+ or `foo` = :dcValue\d+\) and \(`bar` = :dcValue\d+ or `bar` = :dcValue\d+\)\)$/'));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $this->builder->expects($this->once())->method('andWhere')->with($expected);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('bar', 'barbar', $operator, 'and');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testWhereUnsupportedConditionalOperator()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('bar', 'barbar', 'xyz', 'and');
    }

    public function comparisonOperatorsProvider()
    {
        return [
            ['=', '`bar` = :dcValue1'],
            ['!=', '`bar` != :dcValue1'],
            ['>', '`bar` > :dcValue1'],
            ['>=', '`bar` >= :dcValue1'],
            ['<', '`bar` < :dcValue1'],
            ['<=', '`bar` <= :dcValue1'],
            ['like', '`bar` like :dcValue1'],
            ['regexp', '`bar` regexp :dcValue1'],
        ];
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testWhereLogicalOperators($operator)
    {
        switch ($operator) {
            case 'or':
                $this->builder->expects($this->once())->method('orWhere')->with('`bar` = :dcValue1');
                break;
            case 'and':
            default:
                $this->builder->expects($this->once())->method('andWhere')->with('`bar` = :dcValue1');
        }

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('bar', 'barbar', '=', $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testWhereUnsupportedLogicalOperator()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->order('foo', 'xyz');
    }

    public function testLimitWithOffset()
    {
        $limit = 10;
        $offset = 20;

        $this->builder->expects($this->once())->method('setFirstResult')->with($offset);
        $this->builder->expects($this->once())->method('setMaxResults')->with($limit);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->limit($limit, $offset);
    }

    public function testLimitWithoutOffset()
    {
        $limit = 10;

        $this->builder->expects($this->never())->method('setFirstResult');
        $this->builder->expects($this->once())->method('setMaxResults')->with($limit);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->limit($limit);
    }

    public function testWith()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->once())->method('build')->willReturn($model, 'relation')->willReturn($relation);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc'], 1, 2);
    }

    public function testRelation()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $this->factory->expects($this->once())->method('build')->willReturn($relation);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->willReturn([]);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $this->dispatcher->expects($this->exactly(2))->method('fire')->withConsecutive(
            [ReadQuery::EVENT_BEFORE, null],
            [ReadQuery::EVENT_AFTER, []]
        );

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);

        $query->execute();
    }

    public function testCustomQueryExecution()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->willReturn([]);

        $this->dbal->expects($this->once())->method('executeQuery')->with('CUSTOM SQL QUERY', ['param' => 'val'])->willReturn($stmt);
        $this->builder->expects($this->never())->method('execute');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->dispatcher->expects($this->exactly(2))->method('fire')->withConsecutive(
            [ReadQuery::EVENT_BEFORE, null],
            [ReadQuery::EVENT_AFTER, []]
        );

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->query('CUSTOM SQL QUERY', ['param' => 'val'])->execute();
    }

    public function testCount()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);

        $this->assertEquals(10, $query->count());
    }

    public function testCustomQueryCount()
    {
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $this->dbal->expects($this->once())->method('executeQuery')->with('CUSTOM SQL QUERY', ['param' => 'val'])->willReturn($stmt);
        $this->builder->expects($this->never())->method('execute');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->query('CUSTOM SQL QUERY', ['param' => 'val'])->count();
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation');
        $query->execute();
    }

    public function testQueryString()
    {
        $this->builder->expects($this->once())->method('getSQL');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->getSQL();
    }

    public function testBinds()
    {
        $this->builder->expects($this->any())->method('getParameters')->willReturn([':condition_0_foo' => 'foo']);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->where('foo', 'foo');
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $this->builder->expects($this->once())->method('resetQueryParts');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new ReadQuery($this->dbal, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->reset();
    }
}
