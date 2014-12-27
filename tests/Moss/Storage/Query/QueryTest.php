<?php
namespace Moss\Storage\Query;

use Doctrine\DBAL\Connection;
use Moss\Storage\Model\Definition\Field\DateTime;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Mutator\MutatorInterface;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;

class StubClass {
    protected $foo;
    protected $bar;

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}

class MockQuery extends Query
{
    private $exists;

    public function __construct(Connection $connection, ModelBag $models, MutatorInterface $mutator, $exists)
    {
        $this->exists = (bool) $exists;
        parent::__construct($connection, $models, $mutator);
    }

    protected function checkIfEntityExists($entity, $instance)
    {
        return $this->exists;
    }
}

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $bag = $this->mockModelBag();
        $mutator = $this->mockMutator();

        $query = new Query($dbal, $bag, $mutator);

        $this->assertSame($dbal, $query->connection());
    }

    public function testNum()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(3))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with([]);
        $stmt->expects($this->at(1))->method('rowCount')->will($this->returnValue(1));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->num('entity')->execute();
    }

    public function testRead()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with([]);
        $stmt->expects($this->at(1))->method('fetchAll')->will($this->returnValue([]));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));


        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->execute();
    }

    public function testReadOne()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('setMaxResults')->with(1);
        $builder->expects($this->at(6))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $obj = new \stdClass();
        $obj->foo = 'foo';
        $obj->bar = 'bar';

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with([]);
        $stmt->expects($this->at(1))->method('fetchAll')->will($this->returnValue([$obj]));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->readOne('entity')->execute();
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Result out of range or does not exists for
     */
    public function testReadOneThrowsExceptionIfNoResultsFound()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->any())->method('getSQL')->will($this->returnValue('generatedSQL'));

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with([]);
        $stmt->expects($this->at(1))->method('fetchAll')->will($this->returnValue([]));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->readOne('entity')->execute();
    }

    public function testInsert()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('insert')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('setValue')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('setValue')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $binds = [
            ':value_0_foo' => 1,
            ':value_1_bar' => 2,
        ];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with($binds);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));
        $dbal->expects($this->once())->method('lastInsertId')->will($this->returnValue(1));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->insert('entity', $entity)->execute();
    }

    public function testInsertAllFieldsEvenIfAutoIncrement()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('insert')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('setValue')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('setValue')->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'string', ['autoincrement' => true]], 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->insert('entity', $entity);
    }

    public function testInsertSkipAutoIncrementFieldWhenWithoutValue()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('insert')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('setValue')->with('`bar`', ':value_0_bar');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'string', ['autoincrement' => true]], 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = null;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->insert('entity', $entity);
    }

    public function testInsertUpdatesPrimaryKey()
    {
        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->will($this->returnValue([]));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));
        $dbal->expects($this->any())->method('lastInsertId')->will($this->returnValue(1));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = null;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->insert('entity', $entity)->execute();

        $this->assertEquals(1, $entity->foo);
    }

    public function testUpdate()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $binds = [
            ':value_0_foo' => 1,
            ':value_1_bar' => 2,
            ':condition_2_foo' => 1
        ];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with($binds);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'string', ['autoincrement' => true]], 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->update('entity', $entity)->execute();
    }

    public function testWriteUsingInsert()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('insert')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('setValue')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('setValue')->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'string', ['autoincrement' => true]], 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new MockQuery($dbal, $bag, $mutator, false);
        $query->write('entity', $entity);
    }

    public function testWriteUsingUpdate()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'string', ['autoincrement' => true]], 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new MockQuery($dbal, $bag, $mutator, true);
        $query->write('entity', $entity);
    }

    public function testDelete()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('delete')->with('`table`');
        $builder->expects($this->at(1))->method('andWhere')->with('`foo` = :condition_0_foo');
        $builder->expects($this->at(2))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $binds = [
            ':condition_0_foo' => 1
        ];

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with($binds);

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;

        $query = new Query($dbal, $bag, $mutator);
        $query->delete('entity', $entity)->execute();
    }

    public function testClear()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('delete')->with('`table`');
        $builder->expects($this->at(1))->method('getSQL')->will($this->returnValue('generatedSQL'));

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->at(0))->method('execute')->with();

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->once())->method('prepare')->with('generatedSQL')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->clear('entity')->execute();
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->operation('foo', 'entity');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity must be a namespaced class name, its alias or object, got
     */
    public function testNoEntityClassProvided()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read([]);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing entity model for
     */
    public function testEntityWithoutModelProvided()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('foo');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Missing required entity for operation
     */
    public function testEntityInstanceIsMissing()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->write('entity', null);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Entity for operation
     */
    public function testEntityIsNotInstanceOfExpectedClass()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->write('entity', new \ArrayObject());
    }

    public function testFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]); // resets fields
        $builder->expects($this->at(6))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(7))->method('addSelect')->with('`foo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->fields(['bar', 'foo']);
    }

    public function testFieldWithoutMapping()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]); // resets fields
        $builder->expects($this->at(6))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(7))->method('addSelect')->with('`bar`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->fields(['foo'])->field('bar');
    }

    public function testFieldWithMapping()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`yadayada` AS `foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]); // resets fields
        $builder->expects($this->at(6))->method('addSelect')->with('`yadayada` AS `foo`');
        $builder->expects($this->at(7))->method('addSelect')->with('`bar`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'string', [], 'yadayada'], 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->fields(['foo'])->field('bar');
    }

    public function testDistinct()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('DISTINCT(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->distinct('foo', 'distFoo');
    }

    public function testCount()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('COUNT(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->count('foo', 'distFoo');
    }

    public function testAverage()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('AVERAGE(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->average('foo', 'distFoo');
    }

    public function testMax()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('MAX(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->max('foo', 'distFoo');
    }

    public function testMin()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('MIN(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->min('foo', 'distFoo');
    }

    public function testSum()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('SUM(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->sum('foo', 'distFoo');
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Invalid aggregation method
     */
    public function testInvalidAggregation()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->aggregate('foo', 'foo');
    }

    public function testGroup()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('addGroupBy')->with('`foo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->group('foo');
    }

    public function testValuesForInsert()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('insert')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('setValue')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('setValue')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('values')->with([]);
        $builder->expects($this->at(5))->method('setValue')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(6))->method('setValue')->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->insert('entity', $entity)->values(['foo', 'bar']);
    }

    public function testValuesForUpdate()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('values')->with([]);
        $builder->expects($this->at(6))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(7))->method('set')->with('`bar`', ':value_1_bar');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->update('entity', $entity)->values(['foo', 'bar']);
    }

    public function testValueForInsert()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('insert')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('setValue')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('setValue')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('values')->with([]);
        $builder->expects($this->at(5))->method('setValue')->with('`foo`', ':value_0_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->insert('entity', $entity)->values()->value('foo');
    }

    public function testValueForUpdate()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('update')->with('`table`');
        $builder->expects($this->at(1))->method('values')->with([]);
        $builder->expects($this->at(2))->method('set')->with('`foo`', ':value_0_foo');
        $builder->expects($this->at(3))->method('set')->with('`bar`', ':value_1_bar');
        $builder->expects($this->at(4))->method('andWhere')->with('`foo` = :condition_2_foo');
        $builder->expects($this->at(5))->method('values')->with([]);
        $builder->expects($this->at(6))->method('set')->with('`foo`', ':value_0_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->update('entity', $entity)->values()->value('foo');
    }

    /**
     * @dataProvider conditionStructureProvider
     */
    public function testWhereStructure($field, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where($field, $value);
    }

    /**
     * @dataProvider conditionComparisonOperatorProvider
     */
    public function testWhereComparisonOperators($operator, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where('foo', $value, $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testWhereWithInvalidComparisonOperator()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where('foo', 1, 'foo', 'and');
    }

    /**
     * @dataProvider conditionLogicalOperatorProvider
     */
    public function testWhereLogicalOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method($expected . 'Where')->with('`foo` = :condition_0_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where('foo', 1, '=', $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testWhereWithInvalidLogicalOperator()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where('foo', 1, '=', 'foo');
    }

    /**
     * @dataProvider conditionStructureProvider
     */
    public function testHavingStructure($field, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andHaving')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having($field, $value);
    }

    /**
     * @dataProvider conditionComparisonOperatorProvider
     */
    public function testHavingComparisonOperators($operator, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andHaving')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having('foo', $value, $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports comparison operator
     */
    public function testHavingWithInvalidComparisonOperator()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having('foo', 1, 'foo', 'and');
    }

    /**
     * @dataProvider conditionLogicalOperatorProvider
     */
    public function testHavingLogicalOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method($expected . 'Having')->with('`foo` = :condition_0_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having('foo', 1, '=', $operator);
    }

    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Query does not supports logical operator
     */
    public function testHavingWithInvalidLogicalOperator()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having('foo', 1, '=', 'foo');
    }

    public function conditionStructureProvider()
    {
        return [
            ['foo', 1, '`foo` = :condition_0_foo'],
            ['foo', [1, 2], '(`foo` = :condition_0_foo or `foo` = :condition_1_foo)'],
            [['foo', 'bar'], 1, '(`foo` = :condition_0_foo and `bar` = :condition_1_bar)'],
            [['foo', 'bar'], [1, 2], '((`foo` = :condition_0_foo or `foo` = :condition_1_foo) and (`bar` = :condition_2_bar or `bar` = :condition_3_bar))'],
        ];
    }

    public function conditionComparisonOperatorProvider()
    {
        return [
            ['=', 1, '`foo` = :condition_0_foo'],
            ['!=', 1, '`foo` != :condition_0_foo'],
            ['=', null, '`foo` IS NULL'],
            ['!=', null, '`foo` IS NOT NULL'],
            ['<', 1, '`foo` < :condition_0_foo'],
            ['<=', 1, '`foo` <= :condition_0_foo'],
            ['>', 1, '`foo` > :condition_0_foo'],
            ['>=', 1, '`foo` >= :condition_0_foo'],
            ['like', 1, '`foo` like :condition_0_foo'],
            ['regexp', 1, '`foo` regexp :condition_0_foo'],
        ];
    }

    public function conditionLogicalOperatorProvider()
    {
        return [
            ['and', 'and'],
            ['or', 'or'],
        ];
    }

    /**
     * @dataProvider orderProvider
     */
    public function testOrder($order)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('addOrderBy')->with('`foo`', $order);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->order('foo', $order);
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
    public function testInvalidOrderMethod()
    {
        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->order('foo', 'foo');
    }

    /**
     * @dataProvider limitProvider
     */
    public function testLimit($limit, $offset)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('setMaxResults')->with($limit);

        if($offset) {
            $builder->expects($this->once())->method('setFirstResult')->with($offset);
        }

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->limit($limit, $offset);
    }

    public function limitProvider()
    {
        return [
            [0, 0],
            [0, 10],
            [10, 0],
            [10, 20]
        ];
    }

    public function testWith()
    {
        $this->markTestIncomplete();
    }

    public function testRelation()
    {
        $this->markTestIncomplete();
    }

    public function testExecuteReadWithPublicProperties()
    {
        $obj = new \stdClass();
        $obj->foo = '1';
        $obj->bar = 'foo';

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->will($this->returnValue([$obj]));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    [['foo', 'integer'], ['bar', 'string']],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $result = $query->read('entity')->execute();

        $expected = new \stdClass();
        $expected->foo = 1;
        $expected->bar = 'foo';

        $this->assertEquals([$expected], $result);
    }

    public function testExecuteReadWithProtectedProperties()
    {
        $obj = new StubClass('1', 'foo');

        $builder = $this->mockQueryBuilder();

        $stmt = $this->getMock('\\Doctrine\DBAL\Driver\Statement');
        $stmt->expects($this->any())->method('fetchAll')->will($this->returnValue([$obj]));

        $dbal = $this->mockDBAL($builder);
        $dbal->expects($this->any())->method('prepare')->will($this->returnValue($stmt));

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\Moss\\Storage\\Query\\StubClass',
                    'table',
                    [['foo', 'integer'], ['bar', 'string']],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $result = $query->read('entity')->execute();

        $expected = new StubClass('1', 'foo');

        $this->assertEquals([$expected], $result);
    }

    public function testQueryString()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('getSQL');

        $dbal = $this->mockDBAL($builder);
        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->queryString();
    }

    public function testBinds()
    {
        $expected = [
            ':value_0_foo' => 1,
            ':value_1_bar' => 2,
            ':condition_2_foo' => 1
        ];

        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $result = $query->update('entity', $entity)->binds();

        $this->assertEquals($expected, $result);
    }

    public function testReset()
    {
        $expected = [
            ':value_0_foo' => 1,
            ':value_1_bar' => 2,
            ':condition_2_foo' => 1
        ];

        $builder = $this->mockQueryBuilder();

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();

        $entity = new \stdClass();
        $entity->foo = 1;
        $entity->bar = 2;

        $query = new Query($dbal, $bag, $mutator);
        $query->update('entity', $entity);

        $result = $query->binds();
        $this->assertEquals($expected, $result);

        $query->reset();
        $result = $query->binds();
        $this->assertEquals([], $result);
    }

    /**
     * @return \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockDBAL($queryBuilderMock = null)
    {
        $queryBuilderMock = $queryBuilderMock ?: $this->mockQueryBuilder();

        $dbalMock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $dbalMock->expects($this->any())->method('quoteIdentifier')->will($this->returnCallback(
            function ($val) {
                return sprintf('`%s`', $val);
            })
        );
        $dbalMock->expects($this->any())->method('createQueryBuilder')->will($this->returnValue($queryBuilderMock));

        return $dbalMock;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockQueryBuilder()
    {
        return $this->getMockBuilder('\Doctrine\DBAL\Query\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \Moss\Storage\Model\ModelBag|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModelBag($models = [])
    {
        $modelHasMap = [];
        $modelGetMap = [];
        foreach ($models as $alias => $model) {
            list($entity, $table, $fields, $primary, $index) = $model + [null, null, [], [], []];

            $modelHasMap[] = [$alias, true];
            $modelGetMap[] = [$alias, $this->mockModel($entity, $table, $fields, $primary, $index)];
        }

        $bagMock = $this->getMock('\Moss\Storage\Model\ModelBag');
        $bagMock->expects($this->any())->method('has')->will($this->returnValueMap($modelHasMap));
        $bagMock->expects($this->any())->method('get')->will($this->returnValueMap($modelGetMap));

        return $bagMock;
    }

    /**
     * @return \Moss\Storage\Model\ModelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModel($entity, $table, $fields = [], $primaryFields = [], $indexFields = [])
    {
        $fieldsMap = [];
        $indexFields = array_merge($indexFields, $primaryFields); // because all primary fields are index fields

        foreach ($fields as $i => $field) {
            list($name, $type, $attributes, $mapping) = (array) $field + [null, 'string', [], null];
            $mock = $this->mockField($name, $type, $attributes, $mapping);

            $fields[$i] = $mock;
            $fieldsMap[$mock->name()] = [$mock->name(), $mock];
        }

        foreach($primaryFields as $i => $field) {
            $primaryFields[$i] = $fieldsMap[$field][1];
        }

        foreach($indexFields as $i => $field) {
            $indexFields[$i] = $fieldsMap[$field][1];
        }

        $modelMock = $this->getMock('\Moss\Storage\Model\ModelInterface');

        $modelMock->expects($this->any())->method('table')->will($this->returnValue($table));
        $modelMock->expects($this->any())->method('entity')->will($this->returnValue($entity));

        $modelMock->expects($this->any())->method('isPrimary')->will($this->returnCallback(
            function ($field) use ($primaryFields) {
                return in_array($field, $primaryFields);
            })
        );
        $modelMock->expects($this->any())->method('primaryFields')->will($this->returnValue($primaryFields));

        $modelMock->expects($this->any())->method('isIndex')->will($this->returnCallback(
            function ($field) use ($indexFields) {
                return in_array($field, $indexFields);
            })
        );
        $modelMock->expects($this->any())->method('indexFields')->will($this->returnValue($indexFields));

        $modelMock->expects($this->any())->method('field')->will($this->returnValueMap($fieldsMap));
        $modelMock->expects($this->any())->method('fields')->will($this->returnValue($fields));

        return $modelMock;
    }

    /**
     * @return \Moss\Storage\Model\Definition\FieldInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockField($name, $type, $attributes = [], $mapping = null)
    {
        $fieldMock = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $fieldMock->expects($this->any())->method('name')->will($this->returnValue($name));
        $fieldMock->expects($this->any())->method('type')->will($this->returnValue($type));
        $fieldMock->expects($this->any())->method('mapping')->will($this->returnValue($mapping));

        $fieldMock->expects($this->any())->method('attribute')->will($this->returnCallback(
            function ($key) use ($attributes) {
                return array_key_exists($key, $attributes) ? $attributes[$key] : false;
            })
        );

        $fieldMock->expects($this->any())->method('attributes')->will($this->returnValue($attributes));

        return $fieldMock;
    }

    /**
     * @return \Moss\Storage\Mutator\MutatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockMutator()
    {
        $mutatorMock = $this->getMock('\Moss\Storage\Mutator\MutatorInterface');
        $mutatorMock->expects($this->any())->method($this->anything())->will($this->returnArgument(0));

        return $mutatorMock;
    }
}
