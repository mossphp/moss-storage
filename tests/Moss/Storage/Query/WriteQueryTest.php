<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class WriteQueryTest extends QueryMocks
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

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity of class
     */
    public function testEntityIsNull()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new WriteQuery($this->dbal, null, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);

        new WriteQuery($this->dbal, new \stdClass(), $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testEntityWithPublicProperties()
    {
        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testEntityWithProtectedProperties()
    {
        $entity = new TestEntity('foo', 'bar');
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testEntityIsArray()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testConnection()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);

        $this->assertSame($this->dbal, $query->connection());
    }

    public function testValuesForInsert()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('rowCount')->willReturn(0);

        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->at(1))->method('from')->with('`table`');
        $this->builder->expects($this->exactly(2))->method('addSelect')->withConsecutive(['`foo`'], ['`bar`']);
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^`foo` = :dcValue\d+$/'));
        $this->builder->expects($this->once())->method('getSQL')->with();
        $this->builder->expects($this->once())->method('insert')->with('`table`');
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('setValue')->withConsecutive(
            // values from insert
            ['`foo`', $this->matchesRegularExpression('/^:dcValue\d+$/')],
            ['`bar`', $this->matchesRegularExpression('/^:dcValue\d+/')],

            // forced values
            ['`foo`', $this->matchesRegularExpression('/^:dcValue\d+$/')],
            ['`bar`', $this->matchesRegularExpression('/^:dcValue\d+/')]
        );

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $this->accessor->expects($this->any())->method('getPropertyValue')->willReturnArgument(1);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo', 'bar']);
        $query->getSQL();
    }

    public function testValuesForUpdate()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->at(1))->method('from')->with('`table`');
        $this->builder->expects($this->exactly(2))->method('addSelect')->withConsecutive(['`foo`'], ['`bar`']);
        $this->builder->expects($this->exactly(2))->method('andWhere')->with($this->matchesRegularExpression('/^`foo` = :dcValue\d+$/'));
        $this->builder->expects($this->once())->method('getSQL')->with();
        $this->builder->expects($this->once())->method('update')->with('`table`');
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('set')->withConsecutive(
            // values from insert
            ['`foo`', $this->matchesRegularExpression('/^:dcValue\d+$/')],
            ['`bar`', $this->matchesRegularExpression('/^:dcValue\d+/')],

            // forced values
            ['`foo`', $this->matchesRegularExpression('/^:dcValue\d+$/')],
            ['`bar`', $this->matchesRegularExpression('/^:dcValue\d+/')]
        );


        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $this->accessor->expects($this->any())->method('getPropertyValue')->willReturnArgument(1);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo', 'bar']);
        $query->getSQL();
    }

    public function testValueForInsert()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('rowCount')->willReturn(0);

        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->at(1))->method('from')->with('`table`');
        $this->builder->expects($this->exactly(2))->method('addSelect')->withConsecutive(['`foo`']);
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^`foo` = :dcValue\d+$/'));
        $this->builder->expects($this->once())->method('getSQL')->with();
        $this->builder->expects($this->once())->method('insert')->with('`table`');
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('setValue')->withConsecutive(
            // values from insert
            ['`foo`', $this->matchesRegularExpression('/^:dcValue\d+$/')],

            // forced values
            ['`bar`', $this->matchesRegularExpression('/^:dcValue\d+/')]
        );

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $this->accessor->expects($this->any())->method('getPropertyValue')->willReturnArgument(1);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo']);
        $query->value('bar');
        $query->getSQL();
    }

    public function testValueForUpdate()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('rowCount')->willReturn(10);

        $this->builder->expects($this->exactly(2))->method('select')->with([]);
        $this->builder->expects($this->at(1))->method('from')->with('`table`');
        $this->builder->expects($this->exactly(2))->method('addSelect')->withConsecutive(['`foo`']);
        $this->builder->expects($this->exactly(2))->method('andWhere')->with($this->matchesRegularExpression('/^`foo` = :dcValue\d+$/'));
        $this->builder->expects($this->once())->method('getSQL')->with();
        $this->builder->expects($this->once())->method('update')->with('`table`');
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('set')->withConsecutive(
        // values from insert
            ['`foo`', $this->matchesRegularExpression('/^:dcValue\d+$/')],

            // forced values
            ['`bar`', $this->matchesRegularExpression('/^:dcValue\d+/')]
        );

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $this->accessor->expects($this->any())->method('getPropertyValue')->willReturnArgument(1);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo']);
        $query->value('bar');
        $query->getSQL();
    }

    public function testWith()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->once())->method('build')->willReturn($model, 'relation')->willReturn($relation);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation');
    }

    public function testRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $this->factory->expects($this->once())->method('build')->willReturn($relation);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $result = $query->with('relation')->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');
        $relation->expects($this->any())->method('relation')->willReturnSelf(); // hack so we can have nested relation

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $result = $query->with('relation.relation')->relation('relation.relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unable to retrieve relation
     */
    public function testRelationWithoutPriorCreation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecuteWithInsert()
    {
        $entity = ['foo' => 'foo'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('write')->with($entity)->willReturn($entity);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $this->dispatcher->expects($this->exactly(4))->method('fire')->withConsecutive(
            [WriteQuery::EVENT_BEFORE, $entity],
            [InsertQuery::EVENT_BEFORE, $entity],
            [InsertQuery::EVENT_AFTER, $entity],
            [WriteQuery::EVENT_AFTER, $entity]
        );

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteWithUpdate()
    {
        $entity = ['foo' => 'foo'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('rowCount')->willReturn(1);

        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('write')->with($entity)->willReturn($entity);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $this->accessor->expects($this->any())->method('getPropertyValue')->willReturnArgument(1);

        $this->dispatcher->expects($this->exactly(4))->method('fire')->withConsecutive(
            [WriteQuery::EVENT_BEFORE, $entity],
            [UpdateQuery::EVENT_BEFORE, $entity],
            [UpdateQuery::EVENT_AFTER, $entity],
            [WriteQuery::EVENT_AFTER, $entity]
        );

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation');
        $query->execute();
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute');
        $stmt->expects($this->any())->method('rowCount')->willReturn(0);

        $this->builder->expects($this->once())->method('getSQL')->with();
        $this->builder->expects($this->any())->method('execute')->will($this->returnValue($stmt));

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->getSQL();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo']);

        $this->assertEmpty($query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new WriteQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->reset();
    }
}
