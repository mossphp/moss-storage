<?php
namespace Moss\Storage\Query;


use Moss\Storage\TestEntity;

class InsertQueryTest extends QueryMocks
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

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity of class
     */
    public function testEntityIsNull()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new InsertQuery($this->dbal, null, $model, $this->factory, $this->accessor);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);

        new InsertQuery($this->dbal, new \stdClass(), $model, $this->factory, $this->accessor);
    }

    public function testEntityWithPublicProperties()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
    }

    public function testEntityWithProtectedProperties()
    {
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $entity = new TestEntity('foo', 'bar');
        new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
    }

    public function testEntityIsArray()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
    }

    public function testConnection()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);

        $this->assertSame($this->dbal, $query->connection());
    }

    public function testValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->once())->method('insert')->with('`table`');
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('setValue')->withConsecutive(
            ['`foo`', $this->matchesRegularExpression('/^:value_.*/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_.*/')],
            ['`foo`', $this->matchesRegularExpression('/^:value_.*/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_.*/')]
        );

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->values(['foo', 'bar']);
    }

    public function testValue()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $builder = $this->mockQueryBuilder();

        $this->builder->expects($this->once())->method('insert')->with('`table`');
        $this->builder->expects($this->any())->method('getQueryParts')->willReturn(['set' => 'set', 'value' => 'value']);
        $this->builder->expects($this->atLeastOnce())->method('resetQueryPart')->withAnyParameters();
        $this->builder->expects($this->exactly(4))->method('setValue')->withConsecutive(
            ['`foo`', $this->matchesRegularExpression('/^:value_.*/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_.*/')],
            ['`foo`', $this->matchesRegularExpression('/^:value_.*/')],
            ['`bar`', $this->matchesRegularExpression('/^:value_.*/')]
        );

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->values(['foo']);
        $query->value('bar');
    }

    public function testValueWithoutRelationalEntity()
    {
        $reference = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $reference->expects($this->any())->method('container')->willReturn('yada');

        $entity = ['foo' => null, 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo'], [], ['yada' => $reference]);

        $this->builder->expects($this->any())->method('getParameters')->willReturn([':value_0_foo' => null, ':value_1_bar' => 'bar']);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $this->assertEquals([':value_0_foo' => null, ':value_1_bar' => 'bar'], $query->binds());
    }

    public function testValueFromRelationalEntity()
    {
        $entity = ['foo' => null, 'bar' => 'bar', 'yada' => ['yada' => 'yada']];

        $reference = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $reference->expects($this->any())->method('container')->willReturn('yada');

        $this->builder->expects($this->any())->method('getParameters')->willReturn([':value_0_foo' => 'yada', ':value_1_bar' => 'bar']);

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo'], [], ['yada' => $reference]);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $this->assertEquals([':value_0_foo' => 'yada', ':value_1_bar' => 'bar'], $query->binds());
    }

    public function testWith()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);


        $relation = $this->mockRelation();

        $this->factory->expects($this->once())->method('build')->willReturn($model, 'relation')->willReturn($relation);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->with('relation');
    }

    public function testRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);


        $relation = $this->mockRelation();
        $relation->expects($this->any())->method('name')->willReturn('relation');

        $factory = $this->mockRelFactory();
        $this->factory->expects($this->once())->method('build')->willReturn($relation);

        $accessor = $this->mockAccessor();

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
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

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
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

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $entity = ['foo' => 'foo'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('execute')->with();

        $this->dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));

        $relation = $this->mockRelation();
        $relation->expects($this->once())->method('write')->with($entity)->willReturn($entity);

        $this->factory->expects($this->any())->method('build')->willReturn($relation);

        $this->accessor->expects($this->any())->method('setPropertyValue')->willReturnArgument(0);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->with('relation');
        $query->execute();
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->once())->method('getSQL');

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->getSQL();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->any())->method('getParameters')->willReturn([':value_0_foo' => 'foo']);

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->values(['foo']);
        $this->assertEquals([':value_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $this->builder->expects($this->any())->method('resetQueryParts');

        $query = new InsertQuery($this->dbal, $entity, $model, $this->factory, $this->accessor);
        $query->reset();
    }
}
