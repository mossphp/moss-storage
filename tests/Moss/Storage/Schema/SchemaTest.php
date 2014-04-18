<?php
namespace Moss\Storage\Schema;

use Moss\Storage\Builder\MySQL\SchemaBuilder as Builder;
use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\One;
use Moss\Storage\Model\Definition\Relation\Relation;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider tableProvider
     */
    public function testCheck($table)
    {
        $schema = new Schema($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
            ->check($table);

        $tableName = $this->mockModelBag()
            ->get($table)
            ->table();

        $this->assertEquals(array($tableName => 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = \'' . $tableName . '\''), $schema->queryString());
    }

    /**
     * @dataProvider tableProvider
     */
    public function testDrop($table)
    {
        $schema = new Schema($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
            ->drop($table);

        $tableName = $this->mockModelBag()
            ->get($table)
            ->table();

        $this->assertEquals(array(0 => 'DROP TABLE IF EXISTS ' . $tableName . ''), $schema->queryString());
    }

    public function tableProvider()
    {
        return array(
            array('table'),
            array('other')
        );
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate($table, $expected)
    {
        $schema = new Schema($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
            ->create($table);
        $this->assertEquals(array(0 => $expected), $schema->queryString());
    }

    public function createProvider()
    {
        return array(
            array('table', 'CREATE TABLE test_table ( id INT(11) NOT NULL AUTO_INCREMENT, text CHAR(128) DEFAULT NULL, PRIMARY KEY (id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8'),
            array('other', 'CREATE TABLE test_other ( id INT(11) NOT NULL AUTO_INCREMENT, text CHAR(128) DEFAULT NULL, PRIMARY KEY (id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8')
        );
    }

    /**
     * @dataProvider alterProvider
     */
    public function testAlter($current, $expected)
    {
        $schema = new Schema($this->mockDriver($current), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
            ->alter('table');
        $this->assertEquals($expected, $schema->queryString());
    }

    public function alterProvider()
    {
        return array(
            array(
                array(
                    $this->createInputColumn('id', 'int', array('length' => 11, 'auto_increment' => 'YES'), array('name' => 'primary', 'type' => 'primary')),
                    $this->createInputColumn('text', 'char', array('length' => 128, 'null' => 'YES')),
                ),
                array()
            ),
            array(
                array(
                    $this->createInputColumn('id', 'int', array('length' => 5, 'auto_increment' => 'YES'), array('name' => 'primary', 'type' => 'primary')),
                    $this->createInputColumn('text', 'char', array('length' => 128, 'null' => 'YES')),
                ),
                array(
                    'ALTER TABLE test_table CHANGE id id INT(11) NOT NULL AUTO_INCREMENT'
                )
            ),
            array(
                array(
                    $this->createInputColumn('id', 'int', array('length' => 11, 'auto_increment' => 'YES'), array('name' => 'primary', 'type' => 'primary')),
                    $this->createInputColumn('text', 'char', array('length' => 1024, 'null' => 'YES')),
                ),
                array(
                    'ALTER TABLE test_table CHANGE text text CHAR(128) DEFAULT NULL',
                )
            ),
        );
    }

    protected function createInputColumn($name, $type, $attributes = array(), $index = array(), $ref = array())
    {
        return array(
            'pos' => 1,
            'table_schema' => 'test',
            'table_name' => 'test_table',
            'column_name' => $name,
            'column_type' => $type,
            'column_length' => $this->get($attributes, 'length'),
            'column_precision' => $this->get($attributes, 'precision', 0),
            'column_nullable' => $this->get($attributes, 'null', 'NO'),
            'column_auto_increment' => $this->get($attributes, 'auto_increment', 'NO'),
            'column_default' => $this->get($attributes, 'default', null),
            'index_name' => array_key_exists('name', $index) ? (array_key_exists('type', $index) && $index['type'] !== 'primary' ? 'table_' : null) . $index['name'] : null,
            'index_type' => $this->get($index, 'type', null),
            'index_pos' => $this->get($index, 'pos', null),
            'ref_schema' => $this->get($ref, 'schema', null),
            'ref_table' => $this->get($ref, 'table', null),
            'ref_column' => $this->get($ref, 'column', null),
        );
    }

    protected function get($array, $offset, $default = null)
    {
        return array_key_exists($offset, $array) ? $array[$offset] : $default;
    }

    protected function mockDriver($result = null)
    {
        $driver = $this->getMock('\Moss\Storage\Driver\DriverInterface');
        $driver->expects($this->any())
            ->method('prepare')
            ->will($this->returnSelf());

        $driver->expects($this->any())
            ->method('execute')
            ->will($this->returnSelf());

        $driver->expects($this->any())
            ->method('fetchField')
            ->will($this->returnValue($result));

        $driver->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($result));

        $driver->expects($this->any())
            ->method('affectedRows')
            ->will($this->returnValue($result ? 1 : 0));

        return $driver;
    }

    protected function mockBuilder()
    {
        $builder = new Builder();

        return $builder;
    }


    protected function mockModelBag()
    {
        $table = new Model(
            '\stdClass',
            'test_table',
            array(
                new Integer('id', array('auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                new One('\stdClass', array('id' => 'id'), 'other')
            )
        );

        $other = new Model(
            '\altClass',
            'test_other',
            array(
                new Integer('id', array('auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            )
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');

        return $bag;
    }
}
 