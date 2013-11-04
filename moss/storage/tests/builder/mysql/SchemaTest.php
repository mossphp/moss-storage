<?php
namespace moss\storage\builder\mysql;

class SchemaTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addColumn('foo');
        $this->assertEquals('ALTER TABLE `foo` ADD `foo` TEXT NOT NULL', $builder->build());
    }

    /**
     * @dataProvider containerProvider
     */
    public function testContainer($container)
    {
        $builder = new Schema('alter');
        $builder->container($container)
                ->addColumn('foo');
        $this->assertEquals('ALTER TABLE `' . $container . '` ADD `foo` TEXT NOT NULL', $builder->build());
    }

    public function containerProvider()
    {
        return array(
            array('foo'),
            array('bar')
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Missing container name
     */
    public function testWithoutContainer()
    {
        $builder = new Schema('alter');
        $builder->addColumn('foo')
                ->build();
    }

    /**
     * @dataProvider operationProvider
     */
    public function testOperation($op, $mode, $expected)
    {
        $builder = new Schema($op);
        $builder->container('foo');

        switch ($op) {
            case 'info':
                $builder->mode($mode, 1);
                break;
            case 'create':
            case 'alter':
                $builder->addcolumn('foo');
        }

        $this->assertEquals($expected, $builder->build());
    }

    public function operationProvider()
    {
        return array(
            array('check', null, 'SHOW TABLES LIKE \'foo\''),
            array('info', 'columns', 'SHOW FULL COLUMNS FROM `foo`'),
            array('info', 'indexes', 'SHOW INDEXES FROM `foo`'),
            array('create', null, 'CREATE TABLE `foo` ( `foo` TEXT NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'),
            array('alter', null, 'ALTER TABLE `foo` ADD `foo` TEXT NOT NULL'),
            array('drop', null, 'DROP TABLE IF EXISTS `foo`')
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        new Schema('foo');
    }

    /**
     * @dataProvider columnProvider
     */
    public function testAddColumn($type, $attributes, $expected)
    {
        $builder = new Schema('alter');
        $builder->container('foo');
        $builder->addColumn('foo', $type, $attributes);
        $this->assertEquals('ALTER TABLE `foo` ADD `foo` ' . $expected . '', $builder->build());
    }

    public function testAddColumnFirst()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addColumn('foo', 'integer', array(), 'first');
        $this->assertEquals('ALTER TABLE `foo` ADD `foo` INT(10) NOT NULL FIRST', $builder->build());
    }

    public function testAddColumnAfter()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addColumn('foo', 'integer', array(), 'bar');
        $this->assertEquals('ALTER TABLE `foo` ADD `foo` INT(10) NOT NULL AFTER `bar`', $builder->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testAlterColumn($type, $attributes, $expected)
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->alterColumn('foo', $type, $attributes, 'bar');
        $this->assertEquals('ALTER TABLE `foo` CHANGE `bar` `foo` ' . $expected . '', $builder->build());
    }

    public function columnProvider()
    {
        return array(
            array('boolean', array(), 'TINYINT(1) COMMENT \'boolean\' NOT NULL'),
            array('integer', array(), 'INT(10) NOT NULL'),
            array('integer', array('auto_increment' => true), 'INT(10) NOT NULL AUTO_INCREMENT'),
            array('integer', array('length' => 4), 'INT(4) NOT NULL'),
            array('integer', array('unsigned' => true), 'INT(10) UNSIGNED NOT NULL'),
            array('integer', array('null' => true), 'INT(10) DEFAULT NULL'),
            array('integer', array('default' => 1), 'INT(10) DEFAULT 1'),
            array('decimal', array(), 'DECIMAL(10,0) NOT NULL'),
            array('decimal', array('length' => 4, 'precision' => 2), 'DECIMAL(4,2) NOT NULL'),
            array('string', array(), 'TEXT NOT NULL'),
            array('string', array('length' => 32), 'CHAR(32) NOT NULL'),
            array('string', array('length' => 128), 'CHAR(128) NOT NULL'),
            array('string', array('length' => 512), 'VARCHAR(512) NOT NULL'),
            array('string', array('length' => 2048), 'TEXT NOT NULL'),
            array('string', array('default' => 'foo'), 'TEXT DEFAULT \'foo\''),
            array('datetime', array(), 'DATETIME NOT NULL'),
            array('serial', array(), 'TEXT COMMENT \'serial\' NOT NULL'),
        );
    }

    public function testDropColumn()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->dropColumn('bar');
        $this->assertEquals('ALTER TABLE `foo` DROP `bar`', $builder->build());
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Invalid column type
     */
    public function testInvalidColumn()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addColumn('foo', 'foo');
        $builder->build();
    }

    /**
     * @dataProvider parseColumnsProvider
     */
    public function testParseColumns($data, $expected)
    {
        $builder = new Schema('alter');
        $this->assertEquals($expected, $builder->parseColumns(array($data)));
    }

    public function parseColumnsProvider()
    {
        return array(
            array(
                array('Field' => 'foo', 'Type' => 'tinyint(1)', 'Null' => null, 'Extra' => null, 'Comment' => 'boolean'),
                array('foo' => array('name' => 'foo', 'type' => 'boolean', 'attributes' => array('precision' => null, 'length' => 1))),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'int(10)', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'integer', 'attributes' => array('precision' => null, 'length' => 10))),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'int(10) unsigned', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'integer', 'attributes' => array('precision' => null, 'length' => 10, 'unsigned' => true))),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'int(10)', 'Null' => 'Yes', 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'integer', 'attributes' => array('precision' => null, 'length' => 10, 'null' => true))),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'int(10)', 'Null' => null, 'Extra' => 'auto_increment', 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'integer', 'attributes' => array('precision' => null, 'length' => 10, 'auto_increment' => true))),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'decimal(10,2)', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'decimal', 'attributes' => array('precision' => 2, 'length' => 10))),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'char(128)', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'string', 'attributes' => array('precision' => null, 'length' => 128,)))
            ),
            array(
                array('Field' => 'foo', 'Type' => 'text', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'string', 'attributes' => array())),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'text', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'string', 'attributes' => array())),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'datetime', 'Null' => null, 'Extra' => null, 'Comment' => null),
                array('foo' => array('name' => 'foo', 'type' => 'datetime', 'attributes' => array())),
            ),
            array(
                array('Field' => 'foo', 'Type' => 'text', 'Null' => null, 'Extra' => null, 'Comment' => 'serial'),
                array('foo' => array('name' => 'foo', 'type' => 'serial', 'attributes' => array())),
            ),
        );
    }

    /**
     * @dataProvider addIndexProvider
     */
    public function testAddIndex($type, $fields, $expected)
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addIndex('foo', $fields, $type);
        $this->assertEquals('ALTER TABLE `foo` ADD ' . $expected . '', $builder->build());
    }

    public function addIndexProvider()
    {
        return array(
            array('primary', array('foo'), 'PRIMARY KEY (`foo`)'),
            array('primary', array('foo', 'bar'), 'PRIMARY KEY (`foo`, `bar`)'),
            array('unique', array('foo'), 'UNIQUE KEY `foo` (`foo`)'),
            array('unique', array('foo', 'bar'), 'UNIQUE KEY `foo` (`foo`, `bar`)'),
            array('index', array('foo'), 'KEY `foo` (`foo`)'),
            array('index', array('foo', 'bar'), 'KEY `foo` (`foo`, `bar`)'),
        );
    }

    /**
     * @dataProvider alterIndexProvider
     */
    public function testAlterIndex($type, $fields, $expected)
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->alterIndex('foo', $fields, $type);
        $this->assertEquals('ALTER TABLE `foo` ' . $expected, $builder->build());
    }

    public function alterIndexProvider()
    {
        return array(
            array('primary', array('foo'), 'DROP PRIMARY KEY, ADD PRIMARY KEY (`foo`)'),
            array('primary', array('foo', 'bar'), 'DROP PRIMARY KEY, ADD PRIMARY KEY (`foo`, `bar`)'),
            array('unique', array('foo'), 'DROP INDEX `foo`, ADD UNIQUE KEY `foo` (`foo`)'),
            array('unique', array('foo', 'bar'), 'DROP INDEX `foo`, ADD UNIQUE KEY `foo` (`foo`, `bar`)'),
            array('index', array('foo'), 'DROP INDEX `foo`, ADD KEY `foo` (`foo`)'),
            array('index', array('foo', 'bar'), 'DROP INDEX `foo`, ADD KEY `foo` (`foo`, `bar`)'),
        );
    }

    /**
     * @dataProvider dropIndexProvider
     */
    public function testDropIndex($primary, $expected)
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->dropIndex('foo', $primary);
        $this->assertEquals('ALTER TABLE `foo` ' . $expected, $builder->build());
    }

    public function dropIndexProvider()
    {
        return array(
            array(true, 'DROP PRIMARY KEY'),
            array(false, 'DROP INDEX `foo`'),
        );
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Invalid index type
     */
    public function testInvalidIndex()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addIndex('foo', array('foo'), 'foo');
        $builder->build();
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Missing fields for index
     */
    public function testIndexWithoutFields()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->addIndex('foo', array())
                ->build();
    }

    public function testReset()
    {
        $builder = new Schema('alter');
        $builder->container('foo')
                ->reset()
                ->container('bar')
                ->operation('alter')
                ->addColumn('foo');
        $this->assertEquals('ALTER TABLE `bar` ADD `foo` TEXT NOT NULL', $builder->build());
    }
} 