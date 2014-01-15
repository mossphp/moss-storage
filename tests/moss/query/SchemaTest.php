<?php
namespace moss\storage\query;

use moss\storage\builder\mysql\Schema as Builder;
use moss\storage\model\definition\field\Field;
use moss\storage\model\definition\index\Primary;
use moss\storage\model\definition\relation\Relation;
use moss\storage\model\Model;
use moss\storage\model\ModelBag;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /** @var Schema */
    protected $schema;
    protected $queryString;

    public function setUp()
    {
        $this->queryString = null;

        $self = & $this;
        $driver = $this->getMock('\moss\storage\driver\DriverInterface');
        $driver->expects($this->any())
               ->method('prepare')
               ->will($this->returnValue($driver));
        $driver->expects($this->any())
               ->method('execute')
               ->will($this->returnValue($driver));
        $driver->expects($this->any())
               ->method('fetchField')
               ->will($this->returnCallback(function () use ($self) { return $this->queryString; }));
        $driver->expects($this->any())
               ->method('affectedRows')
               ->will($this->returnCallback(function () use ($self) { return empty($this->queryString) ? 0 : 1; }));

        $builder = new Builder();

        $table = new Model(
            '\stdClass',
            'table',
            array(
                 new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                 new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                 new Primary(array('id')),
            ),
            array(
                 new Relation('\stdClass', 'one', array('id' => 'id'), 'other')
            )
        );

        $other = new Model(
            '\altClass',
            'other',
            array(
                 new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                 new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                 new Primary(array('id')),
            )
        );


        $modelbag = new ModelBag();
        $modelbag->set($table, 'table');
        $modelbag->set($other, 'other');

        $this->schema = new Schema($driver, $builder, $modelbag);
    }

    /**
     * @dataProvider tableProvider
     */
    public function testCheck($table)
    {
        $this->schema->reset()
                     ->operation(Schema::OPERATION_CHECK, $table);
        $this->assertEquals(array($table => 'SHOW TABLES LIKE \'' . $table . '\''), $this->schema->queryString());
    }

    /**
     * @dataProvider tableProvider
     */
    public function testDrop($table)
    {
        $this->schema->reset()
                     ->operation(Schema::OPERATION_DROP, $table);
        $this->assertEquals(array(0 => 'DROP TABLE IF EXISTS `' . $table . '`'), $this->schema->queryString());
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
        $this->schema->reset()
                     ->operation(Schema::OPERATION_CREATE, $table);
        $this->assertEquals(array(0 => $expected), $this->schema->queryString());
    }

    public function createProvider()
    {
        return array(
            array('table', 'CREATE TABLE `table` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8'),
            array('other', 'CREATE TABLE `other` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8')
        );
    }

    /**
     * @dataProvider alterProvider
     */
    public function testAlter($current, $expected)
    {
        $this->queryString = $current;
        $this->schema->reset()
                     ->operation(Schema::OPERATION_ALTER);
        $this->assertEquals($expected, $this->schema->queryString());
    }

    public function alterProvider()
    {
        return array(
            array(
                'CREATE TABLE `table` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
                array()
            ),
            array(
                'CREATE TABLE `table` ( `id` CHAR(10) NOT NULL, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
                array(
                    'ALTER TABLE `table` DROP PRIMARY KEY',
                    'ALTER TABLE `table` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL',
                    'ALTER TABLE `table` ADD PRIMARY KEY (`id`)',
                )
            ),
            array(
                'CREATE TABLE `table` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(1024) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
                array(
                    'ALTER TABLE `table` CHANGE `text` `text` TEXT(1024) DEFAULT NULL',
                )
            ),
        );
    }
}
 