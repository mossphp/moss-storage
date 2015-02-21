<?php
namespace Moss\Storage\Query;


use Moss\Storage\TestEntity;

class InsertQueryTest extends QueryMocks
{
    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity for inserting class
     */
    public function testEntityIsNull()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        new InsertQuery($dbal, null, $model, $factory);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity for inserting must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        new InsertQuery($dbal, new \stdClass(), $model, $factory);
    }

    public function testEntityWithPublicProperties()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        new InsertQuery($dbal, $entity, $model, $factory);
    }

    public function testEntityWithProtectedProperties()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $entity = new TestEntity('foo', 'bar');
        new InsertQuery($dbal, $entity, $model, $factory);
    }

    public function testEntityIsArray()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        new InsertQuery($dbal, $entity, $model, $factory);
    }

    public function testConnection()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('insert')
            ->with('`table`');
        $builder->expects($this->at(1))
            ->method('resetQueryPart')
            ->with('values');
        $builder->expects($this->at(2))
            ->method('setValue')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))
            ->method('setValue')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->values(['foo', 'bar']);
    }

    public function testValue()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('insert')
            ->with('`table`');
        $builder->expects($this->at(1))
            ->method('resetQueryPart')
            ->with('values');
        $builder->expects($this->at(2))
            ->method('setValue')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))
            ->method('setValue')
            ->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(1))
            ->method('resetQueryPart')
            ->with('values');
        $builder->expects($this->at(5))
            ->method('setValue')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(6))
            ->method('setValue')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->values(['foo']);
        $query->value('bar');
    }

    public function testValueWithoutRelationalEntity()
    {
        $entity = ['foo' => null, 'bar' => 'bar'];

        $reference = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $reference->expects($this->any())
            ->method('container')
            ->willReturn('yada');

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo'], [], ['yada' => $reference]);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $this->assertEquals([':value_0_foo' => ['string', null], ':value_1_bar' => ['string', 'bar']], $query->binds());
    }

    public function testValueFromRelationalEntity()
    {
        $entity = ['foo' => null, 'bar' => 'bar', 'yada' => ['yada' => 'yada']];

        $reference = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $reference->expects($this->any())
            ->method('container')
            ->willReturn('yada');

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo'], [], ['yada' => $reference]);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $this->assertEquals([':value_0_foo' => ['string', 'yada'], ':value_1_bar' => ['string', 'bar']], $query->binds());
    }

    public function testWith()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

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

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->with('relation');
    }

    public function testRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

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

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $result = $query->with('relation')
            ->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

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

        $query = new InsertQuery($dbal, $entity, $model, $factory);
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
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
        $entity = ['foo' => 'foo'];

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute')
            ->with();

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $relation = $this->mockRelation();
        $relation->expects($this->once())
            ->method('write')
            ->with($entity)
            ->willReturn($entity);

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntityAsObjectWithPublicProperties()
    {
        $entity = (object) ['foo' => null, 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));
        $dbal->expects($this->any())
            ->method('lastInsertId')
            ->willReturn('id');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->execute();
        $this->assertEquals('id', $entity->foo);
    }

    public function testExecuteEntityAsObjectWithProtectedProperties()
    {
        $entity = new TestEntity(null, 'bar');

        $builder = $this->mockQueryBuilder();
        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));
        $dbal->expects($this->any())
            ->method('lastInsertId')
            ->willReturn('id');

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->execute();
        $this->assertEquals('id', $entity->getFoo());
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->values(['foo']);
        $this->assertEquals([':value_0_foo' => ['string', 'foo']], $query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new InsertQuery($dbal, $entity, $model, $factory);
        $query->reset();
    }
}
