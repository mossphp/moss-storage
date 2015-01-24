<?php
namespace Moss\Storage\Query;

use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;
use tests\Moss\Storage\Query\QueryMocks;

class TestEntity extends \stdClass
{
    protected $foo;
    protected $bar;

    public function __construct($foo = null, $bar = null)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}

class DeleteQueryTest extends QueryMocks
{
    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity for deleting class
     */
    public function testEntityIsNull()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, null, $model, $converter, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity for deleting must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, new \stdClass(), $model, $converter, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testEntityWithPublicProperties()
    {
        $query = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testEntityWithProtectedProperties()
    {
        $query = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $entity = new TestEntity('foo', 'bar');
        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testEntityIsArray()
    {
        $query = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, ['foo' => 'foo'], $model, $converter, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testWhereSimple()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $query->expects($this->at(2))
            ->method('andWhere')
            ->with('`bar` = :condition_1_bar');

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $entity = ['foo' => 'foo', 'bar' => null];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $query->expects($this->at(2))
            ->method('andWhere')
            ->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $query->expects($this->at(2))
            ->method('andWhere')
            ->with('(`foo` = :condition_1_foo and `bar` = :condition_2_bar)');

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $query->expects($this->at(2))
            ->method('andWhere')
            ->with('(`bar` = :condition_1_bar or `bar` = :condition_2_bar)');

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $query->expects($this->at(2))
            ->method('andWhere')
            ->with('((`foo` = :condition_1_foo or `foo` = :condition_2_foo) and (`bar` = :condition_3_bar or `bar` = :condition_4_bar))');

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        $query->expects($this->at(2))
            ->method('andWhere')
            ->with($expected);

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', $operator, 'and');
    }

    public function comparisonOperatorsProvider()
    {
        return [
            ['=', '`bar` = :condition_1_bar'],
            ['!=', '`bar` != :condition_1_bar'],
            ['>', '`bar` > :condition_1_bar'],
            ['>=', '`bar` >= :condition_1_bar'],
            ['<', '`bar` < :condition_1_bar'],
            ['<=', '`bar` <= :condition_1_bar'],
            ['like', '`bar` like :condition_1_bar'],
            ['regexp', '`bar` regexp :condition_1_bar'],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testUnsupportedConditionalOperator()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', 'xyz', 'and');
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testWhereLogicalOperators($operator)
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $query->expects($this->at(0))
            ->method('delete')
            ->with('`table`');
        $query->expects($this->at(1))
            ->method('andWhere')
            ->with('`foo` = :condition_0_foo');
        switch ($operator) {
            case 'or':
                $query->expects($this->at(2))
                    ->method('orWhere')
                    ->with('`bar` = :condition_1_bar');
                break;
            case 'and':
            default:
                $query->expects($this->at(2))
                    ->method('andWhere')
                    ->with('`bar` = :condition_1_bar');
        }


        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', $operator);
    }

    public function logicalOperatorsProvider()
    {
        return [
            ['and'],
            ['or']
        ];
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testUnsupportedLogicalOperator()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $query = $this->mockQueryBuilder();
        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', 'xyz');
    }

    public function testLimitWithOffset()
    {
        $entity = ['foo' => 'foo'];
        $limit = 10;
        $offset = 20;

        $query = $this->mockQueryBuilder();
        $query->expects($this->once())
            ->method('setFirstResult')
            ->with($offset);
        $query->expects($this->once())
            ->method('setMaxResults')
            ->with($limit);

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->limit($limit, $offset);
    }

    public function testLimitWithoutOffset()
    {
        $entity = ['foo' => 'foo'];
        $limit = 10;

        $query = $this->mockQueryBuilder();
        $query->expects($this->never())
            ->method('setFirstResult');
        $query->expects($this->once())
            ->method('setMaxResults')
            ->with($limit);

        $dbal = $this->mockDBAL($query);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->limit($limit);
    }

    public function testWith()
    {
        $entity = ['foo' => 'foo'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();

        $factory = $this->mockRelFactory();
        $factory->expects($this->once())
            ->method('create')
            ->with($model, 'relation', [['foo', 'bar', '=']], ['foo', 'asc'])
            ->willReturn($relation);

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc']);
    }

    public function testRelation()
    {
        $entity = ['foo' => 'foo'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->any())
            ->method('name')
            ->willReturn('relation');

        $factory = $this->mockRelFactory();
        $factory->expects($this->once())
            ->method('create')
            ->willReturn($relation);

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $result = $query->with('relation')
            ->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $entity = ['foo' => 'foo'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->any())
            ->method('name')
            ->willReturn('relation');

        $relation->expects($this->any())
            ->method('relation')
            ->willReturnSelf(); // hack so we can have nested relation

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('create')
            ->willReturn($relation);

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
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
        $entity = ['foo' => 'foo'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
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
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();
        $relation->expects($this->once())
            ->method('delete')
            ->with($entity)
            ->willReturn($entity); // because interface always passes by reference

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('create')
            ->willReturn($relation);

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecutionDoesNothingWhenMultiplePrimaryKeys()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())
            ->method('execute')
            ->with();

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo', 'bar']);
        $converter = $this->mockConverter();

        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals($entity, $query->execute());
    }

    public function testExecutionRemovesIdFromPublicProperties()
    {
        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];

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
        $converter = $this->mockConverter();

        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->execute();

        $this->assertEmpty($entity->foo);
    }

    public function testExecutionRemovesIdFromProtectedProperties()
    {
        $entity = new TestEntity('foo', 'bar');

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
        $converter = $this->mockConverter();

        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->execute();

        $this->assertEmpty($entity->getFoo());
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo'];

        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals([':condition_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new DeleteQuery($dbal, $entity, $model, $converter, $factory);
        $query->reset();
    }
}
