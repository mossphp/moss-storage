<?php
namespace Moss\Storage\Query;

use Moss\Storage\Builder\MySQL\Schema as Builder;
use Moss\Storage\Model\Definition\Field\Field;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\Relation;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /** @var Schema */
    protected $schema;
    protected $queryString;

    /**
     * @dataProvider tableProvider
     */
    public function testCheck($table)
    {
        $schema = new Schema($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
               ->operation(Schema::OPERATION_CHECK, $table);
        $this->assertEquals(array($table => 'SHOW TABLES LIKE \'' . $table . '\''), $schema->queryString());
    }

    /**
     * @dataProvider tableProvider
     */
    public function testDrop($table)
    {
        $schema = new Schema($this->mockDriver(), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
               ->operation(Schema::OPERATION_DROP, $table);
        $this->assertEquals(array(0 => 'DROP TABLE IF EXISTS `' . $table . '`'), $schema->queryString());
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
               ->operation(Schema::OPERATION_CREATE, $table);
        $this->assertEquals(array(0 => $expected), $schema->queryString());
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
        $schema = new Schema($this->mockDriver($current), $this->mockBuilder(), $this->mockModelBag());
        $schema->reset()
               ->operation(Schema::OPERATION_ALTER);
        $this->assertEquals($expected, $schema->queryString());
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

    protected function mockDriver($queryString = null)
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
               ->will($this->returnValue($queryString));

        $driver->expects($this->any())
               ->method('affectedRows')
               ->will($this->returnValue(empty($this->queryString) ? 0 : 1));

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
            'table',
            array(
                new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                new Relation('\stdClass', Model::RELATION_ONE, array('id' => 'id'), 'other')
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

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');

        return $bag;
    }
}
 