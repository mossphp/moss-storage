<?php
namespace Moss\Storage\Query;


class ClearQueryTest extends QueryMocks
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ClearQuery($dbal, $model, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testClear()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $builder->expects($this->at(1))
            ->method('getSQL')
            ->will($this->returnValue('generatedSQL'));

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))
            ->method('execute')
            ->with();

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())
            ->method('prepare')
            ->with('generatedSQL')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);

        $factory = $this->mockRelFactory();

        $query = new ClearQuery($dbal, $model, $factory);
        $query->execute();
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

        $query = new ClearQuery($dbal, $model, $factory);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc']);
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
        $factory->expects($this->any())
            ->method('relation')
            ->willReturnSelf();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new ClearQuery($dbal, $model, $factory);
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

        $query = new ClearQuery($dbal, $model, $factory);
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

        $query = new ClearQuery($dbal, $model, $factory);
        $result = $query->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testExecute()
    {
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
            ->method('clear')
            ->with();

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('build')
            ->willReturn($relation);

        $query = new ClearQuery($dbal, $model, $factory);
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
        $factory = $this->mockRelFactory();

        $query = new ClearQuery($dbal, $model, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ClearQuery($dbal, $model, $factory);
        $this->assertEmpty($query->binds());
    }

    public function testReset()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $factory = $this->mockRelFactory();

        $query = new ClearQuery($dbal, $model, $factory);
        $query->reset();
    }
}
