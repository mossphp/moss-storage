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
            ->check($table);

        $tableName = $this->mockModelBag()
            ->get($table)
            ->table();

        $this->assertEquals(array($tableName => 'SHOW TABLES LIKE \'' . $tableName . '\''), $schema->queryString());
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

        $this->assertEquals(array(0 => 'DROP TABLE IF EXISTS `' . $tableName . '`'), $schema->queryString());
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
            array('table', 'CREATE TABLE `test_table` ( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8'),
            array('other', 'CREATE TABLE `test_other` ( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8')
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
                'CREATE TABLE `test_table` ( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
                array()
            ),
            array(
                'CREATE TABLE `test_table` ( `id` CHAR(10) NOT NULL, `text` CHAR(128) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
                array(
                    'ALTER TABLE `test_table` DROP PRIMARY KEY',
                    'ALTER TABLE `test_table` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL',
                    'ALTER TABLE `test_table` ADD PRIMARY KEY (`id`)',
                )
            ),
            array(
                'CREATE TABLE `test_table` ( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `text` CHAR(1024) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
                array(
                    'ALTER TABLE `test_table` CHANGE `text` `text` TEXT(1024) DEFAULT NULL',
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
            ->will($this->returnValue($this->queryString || $queryString ? 1 : 0));

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
                new Integer('id', array('unsigned', 'auto_increment')),
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
                new Integer('id', array('unsigned', 'auto_increment')),
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
 