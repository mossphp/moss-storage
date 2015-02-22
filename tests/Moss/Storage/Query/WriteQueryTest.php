<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class WriteQueryTest extends QueryMocks
{
    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity of class
     */
    public function testEntityIsNull()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        new WriteQuery($dbal, null, $model, $factory);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        new WriteQuery($dbal, new \stdClass(), $model, $factory);
    }

    public function testEntityWithPublicProperties()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        new WriteQuery($dbal, $entity, $model, $factory);
    }

    public function testEntityWithProtectedProperties()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $entity = new TestEntity('foo', 'bar');
        new WriteQuery($dbal, $entity, $model, $factory);
    }

    public function testEntityIsArray()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        new WriteQuery($dbal, $entity, $model, $factory);
    }

    public function testConnection()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testValuesForInsert()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('rowCount')
            ->willReturn(0);

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
            ->method('addSelect')
            ->with('`bar`');
        $builder->expects($this->at(5))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $builder->expects($this->at(6))
            ->method('getSQL')
            ->with();
        $builder->expects($this->at(7))
            ->method('insert')
            ->with('`table`');
        $builder->expects($this->at(8))
            ->method('resetQueryPart')
            ->with('values');
        $builder->expects($this->at(9))
            ->method('setValue')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(10))
            ->method('setValue')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->values(['foo', 'bar']);
        $query->queryString();
    }

    public function testValuesForUpdate()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('rowCount')
            ->willReturn(10);

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
            ->method('addSelect')
            ->with('`bar`');
        $builder->expects($this->at(5))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $builder->expects($this->at(6))
            ->method('getSQL')
            ->with();
        $builder->expects($this->at(7))
            ->method('update')
            ->with('`table`');
        $builder->expects($this->at(8))
            ->method('resetQueryPart')
            ->with('set');
        $builder->expects($this->at(9))
            ->method('set')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(10))
            ->method('set')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->values(['foo', 'bar']);
        $query->queryString();
    }

    public function testValueForInsert()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('rowCount')
            ->willReturn(0);

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
            ->method('addSelect')
            ->with('`bar`');
        $builder->expects($this->at(5))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $builder->expects($this->at(6))
            ->method('getSQL')
            ->with();
        $builder->expects($this->at(7))
            ->method('insert')
            ->with('`table`');
        $builder->expects($this->at(8))
            ->method('resetQueryPart')
            ->with('values');
        $builder->expects($this->at(9))
            ->method('setValue')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(10))
            ->method('setValue')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->values(['foo']);
        $query->value('bar');
        $query->queryString();
    }

    public function testValueForUpdate()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('rowCount')
            ->willReturn(10);

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
            ->method('addSelect')
            ->with('`bar`');
        $builder->expects($this->at(5))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $builder->expects($this->at(6))
            ->method('getSQL')
            ->with();
        $builder->expects($this->at(7))
            ->method('update')
            ->with('`table`');
        $builder->expects($this->at(8))
            ->method('resetQueryPart')
            ->with('set');
        $builder->expects($this->at(9))
            ->method('set')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(10))
            ->method('set')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->values(['foo']);
        $query->value('bar');
        $query->queryString();
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
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

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->execute();
        $this->assertEquals('id', $entity->getFoo());
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute');
        $stmt->expects($this->any())
            ->method('rowCount')
            ->willReturn(0);

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
            ->method('addSelect')
            ->with('`bar`');
        $builder->expects($this->at(5))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $builder->expects($this->at(6))
            ->method('getSQL')
            ->with();
        $builder->expects($this->at(7))
            ->method('insert')
            ->with('`table`');
        $builder->expects($this->at(8))
            ->method('resetQueryPart')
            ->with('values');
        $builder->expects($this->at(9))
            ->method('setValue')
            ->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(10))
            ->method('setValue')
            ->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->values(['foo']);
        $this->assertEmpty($query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new WriteQuery($dbal, $entity, $model, $factory);
        $query->reset();
    }
}
