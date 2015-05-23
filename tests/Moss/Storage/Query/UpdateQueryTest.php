<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class UpdateQueryTest extends QueryMocks
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

        new UpdateQuery($this->dbal, null, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);

        new UpdateQuery($this->dbal, new \stdClass(), $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testEntityWithPublicProperties()
    {
        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testEntityWithProtectedProperties()
    {
        $entity = new TestEntity('foo', 'bar');
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testEntityIsArray()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
    }

    public function testConnection()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);

        $this->assertSame($this->dbal, $query->connection());
    }

    public function testValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->once())->method('update')->with('`table`');
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^`foo` = :condition_\d_foo$/'));
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('set')->withConsecutive(
            ['`foo`', $this->matchesRegularExpression('/^:value_\d_foo$/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_\d_bar$/')],
            ['`foo`', $this->matchesRegularExpression('/^:value_\d_foo$/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_\d_bar$/')]
        );

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo', 'bar']);
    }

    public function testValue()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->once())->method('update')->with('`table`');
        $this->builder->expects($this->once())->method('andWhere')->with($this->matchesRegularExpression('/^`foo` = :condition_\d_foo$/'));
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(3))->method('set')->withConsecutive(
            ['`foo`', $this->matchesRegularExpression('/^:value_\d_foo$/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_\d_bar$/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_\d_bar$/')]
        );

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->value('bar');
    }

    public function testValueWithoutRelationalEntity()
    {
        $reference = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $reference->expects($this->any())->method('container')->willReturn('yada');

        $entity = ['foo' => null, 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo'], [], ['yada' => $reference]);

        $this->builder->expects($this->any())->method('getParameters')->willReturn([':value_0_foo' => null, ':value_1_bar' => 'bar']);

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $this->assertEquals([':value_0_foo' => null, ':value_1_bar' => 'bar'], $query->binds());
    }

    public function testValueFromRelationalEntity()
    {
        $reference = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $reference->expects($this->any())->method('container')->willReturn('yada');

        $entity = ['foo' => null, 'bar' => 'bar', 'yada' => ['yada' => 'yada']];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo'], [], ['yada' => $reference]);

        $this->builder->expects($this->any())->method('getParameters')->willReturn([':value_0_foo' => 'yada', ':value_1_bar' => 'bar']);

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $this->assertEquals([':value_0_foo' => 'yada', ':value_1_bar' => 'bar'], $query->binds());
    }

    public function testWith()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();

        $this->factory->expects($this->once())->method('build')->willReturn($model, 'relation')->willReturn($relation);

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation');
    }

    public function testRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $this->factory->expects($this->once())->method('build')->willReturn($relation);

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
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

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $entity = ['foo' => 'foo'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');

        $this->dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('write')->with($entity)->willReturn($entity);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $this->dispatcher->expects($this->exactly(2))->method('fire')->withConsecutive(
            [UpdateQuery::EVENT_BEFORE, $entity],
            [UpdateQuery::EVENT_AFTER, $entity]
        );

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->with('relation');
        $query->execute();
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->once())->method('getSQL');

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->getSQL();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->any())->method('getParameters')->willReturn([':condition_2_foo' => 'foo', ':value_1_foo' => 'foo']);

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->values(['foo']);
        $this->assertEquals([':condition_2_foo' => 'foo', ':value_1_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $builder = $this->mockQueryBuilder();
        $this->builder->expects($this->any())->method('resetQueryParts');

        $query = new UpdateQuery($this->dbal, $entity, $model, $this->factory, $this->accessor, $this->dispatcher);
        $query->reset();
    }
}
