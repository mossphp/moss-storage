<?php
namespace Moss\Storage\Query;

use Moss\Storage\TestEntity;

class UpdateQueryTest extends QueryMocks
{
    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity for updating class
     */
    public function testEntityIsNull()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        new UpdateQuery($dbal, null, $model, $converter, $factory);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity for updating must be an instance of
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\Foo', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        new UpdateQuery($dbal, new \stdClass(), $model, $converter, $factory);
    }

    public function testEntityWithPublicProperties()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];
        new UpdateQuery($dbal, $entity, $model, $converter, $factory);
    }

    public function testEntityWithProtectedProperties()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $entity = new TestEntity('foo', 'bar');
        new UpdateQuery($dbal, $entity, $model, $converter, $factory);
    }

    public function testEntityIsArray()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $entity = ['foo' => 'foo', 'bar' => 'bar'];
        new UpdateQuery($dbal, $entity, $model, $converter, $factory);
    }

    public function testConnection()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);

        $this->assertSame($dbal, $query->connection());
    }

    public function testValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->values(['foo', 'bar']);
    }

    public function testValue()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('set')->with('`bar`', ':value_3_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
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
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals([':value_0_foo' => null, ':value_1_bar' => 'bar'], $query->binds());
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
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $this->assertEquals([':value_0_foo' => 'yada', ':value_1_bar' => 'bar'], $query->binds());
    }

    public function testWhereSimple()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('andWhere')->with('`bar` = :condition_3_bar');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', 'and');
    }

    public function testWhereWithNullValue()
    {
        $entity = ['foo' => 'foo', 'bar' => null];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('andWhere')->with('`bar` IS NULL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', null, '=', 'and');
    }

    public function testWhereWithMultipleFields()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('andWhere')->with('(`foo` = :condition_3_foo and `bar` = :condition_4_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where(['foo', 'bar'], 'barbar', '=', 'and');
    }

    public function testWhereWithMultipleValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('andWhere')->with('(`bar` = :condition_3_bar or `bar` = :condition_4_bar)');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', ['foofoo', 'barbar'], '=', 'and');
    }

    public function testWhereWithMultipleFieldsAndValues()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('andWhere')->with('((`foo` = :condition_3_foo or `foo` = :condition_4_foo) and (`bar` = :condition_5_bar or `bar` = :condition_6_bar))');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where(['foo', 'bar'], ['foofoo', 'barbar'], '=', 'and');
    }

    /**
     * @dataProvider comparisonOperatorsProvider
     */
    public function testWhereComparisonOperators($operator, $expected)
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('andWhere')->with($expected);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', $operator, 'and');
    }

    public function comparisonOperatorsProvider()
    {
        return [
            ['=', '`bar` = :condition_3_bar'],
            ['!=', '`bar` != :condition_3_bar'],
            ['>', '`bar` > :condition_3_bar'],
            ['>=', '`bar` >= :condition_3_bar'],
            ['<', '`bar` < :condition_3_bar'],
            ['<=', '`bar` <= :condition_3_bar'],
            ['like', '`bar` like :condition_3_bar'],
            ['regexp', '`bar` regexp :condition_3_bar'],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testUnsupportedConditionalOperator()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', 'xyz', 'and');
    }

    /**
     * @dataProvider logicalOperatorsProvider
     */
    public function testWhereLogicalOperators($operator)
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');

        switch ($operator) {
            case 'or':
                $builder->expects($this->at(5))->method('orWhere')->with('`bar` = :condition_3_bar');
                break;
            case 'and':
            default:
                $builder->expects($this->at(5))->method('andWhere')->with('`bar` = :condition_3_bar');
        }


        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
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

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->where('bar', 'barbar', '=', 'xyz');
    }

    public function testLimitWithOffset()
    {
        $entity = ['foo' => 'foo'];
        $limit = 10;
        $offset = 20;

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('setFirstResult')->with($offset);
        $builder->expects($this->once())->method('setMaxResults')->with($limit);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->limit($limit, $offset);
    }

    public function testLimitWithoutOffset()
    {
        $entity = ['foo' => 'foo'];
        $limit = 10;

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->never())->method('setFirstResult');
        $builder->expects($this->once())->method('setMaxResults')->with($limit);

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->limit($limit);
    }

    public function testWith()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();

        $relation = $this->mockRelation();

        $factory = $this->mockRelFactory();
        $factory->expects($this->once())
            ->method('create')
            ->with($model, 'relation', [['foo', 'bar', '=']], ['foo', 'asc'])
            ->willReturn($relation);

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->with('relation', [['foo', 'bar', '=']], ['foo', 'asc']);
    }

    public function testRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

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

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $result = $query->with('relation')
            ->relation('relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $result);
    }

    public function testRelationWithFurtherRelation()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

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

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
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
        $converter = $this->mockConverter();

        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
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
            ->method('write')
            ->with($entity)
            ->willReturn($entity); // because interface always passes by reference

        $factory = $this->mockRelFactory();
        $factory->expects($this->any())
            ->method('create')
            ->willReturn($relation);

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->with('relation');
        $query->execute();
    }

    public function testExecuteEntityAsObjectWithPublicProperties()
    {
        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];

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
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->execute();
        $this->assertEquals('foo', $entity->foo);
    }

    public function testExecuteEntityAsObjectWithProtectedProperties()
    {
        $entity = new TestEntity('foo', 'bar');

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
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->execute();
        $this->assertEquals('foo', $entity->getFoo());
    }

    public function testQueryString()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->queryString();
    }

    public function testBinds()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->values(['foo']);
        $this->assertEquals([':value_0_foo' => 'foo'], $query->binds());
    }

    public function testReset()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())
            ->method('resetQueryParts');

        $dbal = $this->mockDBAL($builder);
        $model = $this->mockModel('\\stdClass', 'table', ['foo', 'bar'], ['foo']);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new UpdateQuery($dbal, $entity, $model, $converter, $factory);
        $query->reset();
    }
}
