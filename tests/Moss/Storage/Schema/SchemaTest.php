<?php
namespace Moss\Storage\Schema;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $table = $this->mockTable();
        $schema = $this->mockSchema($table);
        $manager = $this->mockManager($schema);
        $dbal = $this->mockDBAL($manager);
        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar', 'yada', 'bubba'],
                    [
                        ['primary', 'primary', 'table', ['foo']],
                        ['index', 'index', 'table', ['bar']],
                        ['unique', 'unique', 'table', ['yada']],
                        ['foreign', 'foreign', 'fktable', ['bubba' => 'hubba']]
                    ]
                ]
            ]
        );

        $manager->expects($this->once())
            ->method('tablesExist')
            ->with('table');

        $table->expects($this->at(0))
            ->method('addColumn')
            ->with('`foo`', 'string', []);
        $table->expects($this->at(1))
            ->method('addColumn')
            ->with('`bar`', 'string', []);
        $table->expects($this->at(2))
            ->method('addColumn')
            ->with('`yada`', 'string', []);
        $table->expects($this->at(3))
            ->method('addColumn')
            ->with('`bubba`', 'string', []);

        $table->expects($this->at(4))
            ->method('setPrimaryKey')
            ->with(['`foo`'], '`primary`');
        $table->expects($this->at(5))
            ->method('addIndex')
            ->with(['`bar`'], '`index`');
        $table->expects($this->at(6))
            ->method('addUniqueIndex')
            ->with(['`yada`'], '`unique`');
        $table->expects($this->at(7))
            ->method('addForeignKeyConstraint')
            ->with('fktable', ['`bubba`'], ['`hubba`'], ['onUpdate' => 'CASCADE', 'onDelete' => 'RESTRICT'], '`foreign`');

        $schema = new Schema($dbal, $bag);
        $schema->create()
            ->execute();
    }

    /**
     * @expectedException \Moss\Storage\Schema\SchemaException
     * @expectedExceptionMessage Unable to create table, table
     */
    public function testCreateWhenTableExists()
    {
        $table = $this->mockTable();
        $schema = $this->mockSchema($table);
        $manager = $this->mockManager($schema);
        $dbal = $this->mockDBAL($manager);
        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo']
                ]
            ]
        );

        $manager->expects($this->any())
            ->method('tablesExist')
            ->will($this->returnValue(true));

        $schema = new Schema($dbal, $bag);
        $schema->create()
            ->execute();
    }

    public function testAlter()
    {
        $table = $this->mockTable();
        $schema = $this->mockSchema($table);
        $manager = $this->mockManager($schema);
        $dbal = $this->mockDBAL($manager);
        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                ]
            ]
        );

        $schema->expects($this->once())
            ->method('dropTable')
            ->with('table');

        $table->expects($this->at(0))
            ->method('addColumn')
            ->with('`foo`', 'string', []);

        $schema = new Schema($dbal, $bag);
        $schema->alter()
            ->execute();
    }

    public function testDrop()
    {
        $table = $this->mockTable();
        $schema = $this->mockSchema($table);
        $manager = $this->mockManager($schema);
        $dbal = $this->mockDBAL($manager);
        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo']
                ]
            ]
        );


        $manager->expects($this->once())
            ->method('tablesExist')
            ->with('table')->will($this->returnValue(true));

        $schema->expects($this->once())
            ->method('dropTable')
            ->with('table');

        $schema = new Schema($dbal, $bag);
        $schema->drop()
            ->execute();
    }

    public function mockDBAL(AbstractSchemaManager $schemaManager)
    {
        $mock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager));

        $mock->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->getMock('\Doctrine\DBAL\Platforms\MySqlPlatform')));

        $mock->expects($this->any())
            ->method('quoteIdentifier')
            ->will(
                $this->returnCallback(
                    function ($val) {
                        return sprintf('`%s`', $val);
                    }
                )
            );

        $mock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($this->getMock('\\Doctrine\DBAL\Driver\Statement')));

        return $mock;
    }

    public function mockManager(AbstractAsset $schema)
    {
        $mock = $this->getMockBuilder('\Doctrine\DBAL\Schema\MySqlSchemaManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())
            ->method('createSchema')
            ->will($this->returnValue($schema));

        return $mock;
    }

    public function mockSchema(Table $table)
    {
        $mock = $this->getMockBuilder('\Doctrine\DBAL\Schema\Schema')
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())
            ->method('createTable')
            ->will($this->returnValue($table));

        $mock->expects($this->any())
            ->method('toSQL')
            ->will($this->returnValue(['SQLQueryString']));

        $mock->expects($this->any())
            ->method('getMigrateToSql')
            ->will($this->returnValue(['SQLMigrationQueryString']));

        return $mock;
    }

    public function mockTable()
    {
        $mock = $this->getMockBuilder('\Doctrine\DBAL\Schema\Table')
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    /**
     * @return \Moss\Storage\Model\ModelBag|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModelBag($models = [])
    {
        $modelHasMap = [];
        $modelGetMap = [];
        foreach ($models as $alias => $model) {
            list($entity, $table, $fields, $indexes) = $model + [null, null, [], []];

            $model = $this->mockModel($entity, $table, $fields, $indexes);

            $modelHasMap[] = [$alias, true];
            $modelGetMap[] = [$alias, $model];

            $models[$alias] = $model;
        }

        $bagMock = $this->getMock('\Moss\Storage\Model\ModelBag');
        $bagMock->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap($modelHasMap));
        $bagMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($modelGetMap));
        $bagMock->expects($this->any())
            ->method('all')
            ->will($this->returnValue($models));

        return $bagMock;
    }

    /**
     * @return \Moss\Storage\Model\ModelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModel($entity, $table, $fields = [], $indexes = [])
    {
        $mock = $this->getMock('\Moss\Storage\Model\ModelInterface');

        $mock->expects($this->any())
            ->method('table')
            ->will($this->returnValue($table));
        $mock->expects($this->any())
            ->method('entity')
            ->will($this->returnValue($entity));

        foreach ($fields as $i => $field) {
            list($name, $type, $attributes, $mapping) = (array) $field + [null, 'string', [], null];
            $fields[$i] = $this->mockField($name, $type, $attributes, $mapping);
        }
        $mock->expects($this->any())
            ->method('fields')
            ->will($this->returnValue($fields));

        foreach ($indexes as $i => $index) {
            list($name, $type, $table, $fields, $attributes) = (array) $index + [null, 'index', null, [], []];
            $indexes[$i] = $this->mockIndex($name, $type, $table, $fields, $attributes);
        }
        $mock->expects($this->any())
            ->method('indexes')
            ->will($this->returnValue($indexes));

        return $mock;
    }

    /**
     * @return \Moss\Storage\Model\Definition\FieldInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockField($name, $type, $attributes = [], $mapping = null)
    {
        $fieldMock = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $fieldMock->expects($this->any())
            ->method('name')
            ->will($this->returnValue($name));
        $fieldMock->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));
        $fieldMock->expects($this->any())
            ->method('mapping')
            ->will($this->returnValue($mapping));

        $fieldMock->expects($this->any())
            ->method('attribute')
            ->will(
                $this->returnCallback(
                    function ($key) use ($attributes) {
                        return array_key_exists($key, $attributes) ? $attributes[$key] : false;
                    }
                )
            );

        $fieldMock->expects($this->any())
            ->method('attributes')
            ->will($this->returnValue($attributes));

        return $fieldMock;
    }

    /**
     * @return \Moss\Storage\Model\Definition\IndexInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockIndex($name, $type, $table, array $fields, array $attributes = [])
    {
        $indexMock = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $indexMock->expects($this->any())
            ->method('name')
            ->will($this->returnValue($name));
        $indexMock->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));
        $indexMock->expects($this->any())
            ->method('table')
            ->will($this->returnValue($table));
        $indexMock->expects($this->any())
            ->method('fields')
            ->will($this->returnValue($fields));
        $indexMock->expects($this->any())
            ->method('attributes')
            ->will($this->returnValue($attributes));

        return $indexMock;
    }
}
