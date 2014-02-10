<?php
namespace moss\storage\builder\mysql;

use moss\storage\builder\SchemaInterface;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testMissingTable()
    {
        $schema = new Schema(null);
        $schema->build();
    }

    public function testTable()
    {
        $schema = new Schema('table', Schema::OPERATION_ADD);
        $schema->column('foo');
        $this->assertEquals('ALTER TABLE `table` ADD `foo` TEXT NOT NULL', $schema->build());
    }

    /**
     * @expectedException \moss\storage\builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        new Schema('table', 'foo');
    }


    /**
     * @dataProvider shortOperationProvider
     */
    public function testShortOperation($operation, $expected)
    {
        $schema = new Schema('table', $operation);
        $schema->column('foo')
               ->index('idx', array('foo'), 'index');
        $this->assertEquals($expected, $schema->build());
    }

    public function shortOperationProvider()
    {
        return array(
            array(
                Schema::OPERATION_CHECK,
                'SHOW TABLES LIKE \'table\''
            ),
            array(
                Schema::OPERATION_INFO,
                'SHOW CREATE TABLE `table`'
            ),
            array(
                Schema::OPERATION_DROP,
                'DROP TABLE IF EXISTS `table`'
            )
        );
    }

    /**
     * @dataProvider columnProvider
     */
    public function testCreateColumn($actual, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_CREATE);
        $schema->column('foo', $actual);
        $this->assertEquals('CREATE TABLE `table` ( `foo` ' . $expected . ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testAddColumn($actual, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_ADD);
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE `table` ADD `foo` ' . $expected . '', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testChangeColumn($actual, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_CHANGE);
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE `table` CHANGE `foo` `foo` ' . $expected . '', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testRemoveColumn($actual, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_REMOVE);
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE `table` DROP `foo`', $schema->build());
    }

    public function columnProvider()
    {
        return array(
            array(
                Schema::FIELD_BOOLEAN,
                'TINYINT(1) COMMENT \'boolean\' NOT NULL'
            ),
            array(
                Schema::FIELD_INTEGER,
                'INT(10) NOT NULL',
            ),
            array(
                Schema::FIELD_DECIMAL,
                'DECIMAL(10,0) NOT NULL',
            ),
            array(
                Schema::FIELD_STRING,
                'TEXT NOT NULL'
            ),
            array(
                Schema::FIELD_DATETIME,
                'DATETIME NOT NULL'
            ),
            array(
                Schema::FIELD_SERIAL,
                'TEXT COMMENT \'serial\' NOT NULL'
            ),

        );
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testColumnAttributes($type, $attributes, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_ADD);
        $schema->column('foo', $type, $attributes);
        $this->assertEquals('ALTER TABLE `table` ADD `foo` ' . $expected . '', $schema->build());
    }

    public function attributeProvider()
    {
        return array(
            array(
                Schema::FIELD_INTEGER,
                array('unsigned'),
                'INT(10) UNSIGNED NOT NULL'
            ),
            array(
                Schema::FIELD_INTEGER,
                array('default' => 1),
                'INT(10) DEFAULT 1'
            ),
            array(
                Schema::FIELD_INTEGER,
                array('auto_increment'),
                'INT(10) NOT NULL AUTO_INCREMENT'
            ),
            array(
                Schema::FIELD_INTEGER,
                array('null'),
                'INT(10) DEFAULT NULL'
            ),
            array(
                Schema::FIELD_INTEGER,
                array('length' => 6),
                'INT(6) NOT NULL'
            ),
            array(
                Schema::FIELD_STRING,
                array('length' => null),
                'TEXT NOT NULL'
            ),
            array(
                Schema::FIELD_STRING,
                array('length' => 2048),
                'TEXT NOT NULL'
            ),
            array(
                Schema::FIELD_STRING,
                array('length' => 512),
                'VARCHAR(512) NOT NULL'
            ),
            array(
                Schema::FIELD_STRING,
                array('length' => 10),
                'CHAR(10) NOT NULL'
            ),
            array(
                Schema::FIELD_DECIMAL,
                array('precision' => 2),
                'DECIMAL(10,2) NOT NULL'
            ),
            array(
                Schema::FIELD_DECIMAL,
                array('length' => 6, 'precision' => 2),
                'DECIMAL(6,2) NOT NULL'
            ),
            array(
                Schema::FIELD_INTEGER,
                array('comment' => 'some comment'),
                'INT(10) COMMENT \'some comment\' NOT NULL'
            ),
        );
    }

    /**
     * @dataProvider indexProvider
     */
    public function testCreateIndex($type, $fields, $table, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_CREATE);
        $schema->column('foo', Schema::FIELD_INTEGER)
               ->index('foo', $fields, $type, $table);
        $this->assertEquals('CREATE TABLE `table` ( `foo` INT(10) NOT NULL, ' . $expected . ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8', $schema->build());
    }

    /**
     * @dataProvider indexProvider
     */
    public function testAddIndex($type, $fields, $table, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_ADD);
        $schema->index('foo', $fields, $type, $table);
        $this->assertEquals('ALTER TABLE `table` ADD ' . $expected . '', $schema->build());
    }


    public function indexProvider()
    {
        return array(
            array(
                Schema::INDEX_PRIMARY,
                array('foo'),
                null,
                'PRIMARY KEY (`foo`)'
            ),
            array(
                Schema::INDEX_UNIQUE,
                array('foo'),
                null,
                'UNIQUE KEY `foo` (`foo`)'
            ),
            array(
                Schema::INDEX_INDEX,
                array('foo'),
                null,
                'KEY `foo` (`foo`)'
            ),
            array(
                Schema::INDEX_FOREIGN,
                array('foo' => 'bar'),
                'yada',
                'CONSTRAINT `foo` FOREIGN KEY (`foo`) REFERENCES `yada` (`bar`) ON UPDATE CASCADE ON DELETE RESTRICT'
            ),
        );
    }

    /**
     * @dataProvider dropIndexProvider
     */
    public function testRemoveIndex($type, $fields, $table, $expected)
    {
        $schema = new Schema('table', Schema::OPERATION_REMOVE);
        $schema->index('foo', $fields, $type, $table);
        $this->assertEquals('ALTER TABLE `table` DROP ' . $expected, $schema->build());
    }

    public function dropIndexProvider()
    {
        return array(
            array(
                Schema::INDEX_PRIMARY,
                array('foo'),
                null,
                'PRIMARY KEY'
            ),
            array(
                Schema::INDEX_UNIQUE,
                array('foo'),
                null,
                'KEY `foo`',
            ),
            array(
                Schema::INDEX_INDEX,
                array('foo'),
                null,
                'KEY `foo`',
            ),
            array(
                Schema::INDEX_FOREIGN,
                array('bar'),
                'yada',
                'FOREIGN KEY `foo`',
            ),
        );
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParse($stmt, $fields, $indexes)
    {
        $expected = array(
            'table' => 'table',
            'fields' => $fields,
            'indexes' => $indexes,
        );

        $schema = new Schema();
        $result = $schema->parse($stmt);
        $this->assertEquals($expected, $result);
    }

    public function parseProvider()
    {
        return array(
            array(
                'CREATE TABLE `table` (`int` int(10) unsigned NOT NULL AUTO_INCREMENT,) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'int', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`int` int(10) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'int', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`string` char(10) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'string', 'type' => SchemaInterface::FIELD_STRING, 'attributes' => array('length' => 10)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`decimal` decimal(10,2) unsigned NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'decimal', 'type' => SchemaInterface::FIELD_DECIMAL, 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`boolean` tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'boolean', 'type' => SchemaInterface::FIELD_BOOLEAN, 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => 1, 'comment' => 'boolean')),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`datetime` datetime NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'datetime', 'type' => SchemaInterface::FIELD_DATETIME, 'attributes' => array()),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`serial` text COMMENT \'serial\') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'serial', 'type' => SchemaInterface::FIELD_SERIAL, 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`int` int(10) NOT NULL,
`string` char(10) NOT NULL,
`decimal` decimal(10,2) unsigned NOT NULL,
`boolean` tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\',
`datetime` datetime NOT NULL,
`serial` text COMMENT \'serial\',
PRIMARY KEY (`id`),
UNIQUE KEY `uni` (`int`),
KEY `ind` (`string`),
CONSTRAINT `fk` FOREIGN KEY (`id`) REFERENCES `rm_rel` (`src_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'id', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => SchemaInterface::FIELD_STRING, 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => SchemaInterface::FIELD_DECIMAL, 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => SchemaInterface::FIELD_BOOLEAN, 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => SchemaInterface::FIELD_DATETIME, 'attributes' => array()),
                    array('name' => 'serial', 'type' => SchemaInterface::FIELD_SERIAL, 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => SchemaInterface::INDEX_PRIMARY, 'fields' => array('id')),
                    array('name' => 'uni', 'type' => SchemaInterface::INDEX_UNIQUE, 'fields' => array('int')),
                    array('name' => 'ind', 'type' => SchemaInterface::INDEX_INDEX, 'fields' => array('string')),
                    array('name' => 'fk', 'type' => SchemaInterface::INDEX_FOREIGN, 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
            array(
                'CREATE TABLE `table` ( `id` int(10) unsigned NOT NULL AUTO_INCREMENT, `int` int(10) NOT NULL, `string` char(10) NOT NULL, `decimal` decimal(10,2) unsigned NOT NULL, `boolean` tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\', `datetime` datetime NOT NULL, `serial` text COMMENT \'serial\', PRIMARY KEY (`id`), UNIQUE KEY `uni` (`int`), KEY `ind` (`string`), CONSTRAINT `fk` FOREIGN KEY (`id`) REFERENCES `rm_rel` (`src_id`) ON UPDATE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'id', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => SchemaInterface::FIELD_STRING, 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => SchemaInterface::FIELD_DECIMAL, 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => SchemaInterface::FIELD_BOOLEAN, 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => SchemaInterface::FIELD_DATETIME, 'attributes' => array()),
                    array('name' => 'serial', 'type' => SchemaInterface::FIELD_SERIAL, 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => SchemaInterface::INDEX_PRIMARY, 'fields' => array('id')),
                    array('name' => 'uni', 'type' => SchemaInterface::INDEX_UNIQUE, 'fields' => array('int')),
                    array('name' => 'ind', 'type' => SchemaInterface::INDEX_INDEX, 'fields' => array('string')),
                    array('name' => 'fk', 'type' => SchemaInterface::INDEX_FOREIGN, 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
            array(
                'CREATE TABLE table (
id int(10) unsigned NOT NULL AUTO_INCREMENT,
int int(10) NOT NULL,
string char(10) NOT NULL,
decimal decimal(10,2) unsigned NOT NULL,
boolean tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\',
datetime datetime NOT NULL,
serial text COMMENT \'serial\',
PRIMARY KEY (id),
UNIQUE KEY uni (int),
KEY ind (string),
CONSTRAINT fk FOREIGN KEY (id) REFERENCES rm_rel (src_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'id', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => SchemaInterface::FIELD_STRING, 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => SchemaInterface::FIELD_DECIMAL, 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => SchemaInterface::FIELD_BOOLEAN, 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => SchemaInterface::FIELD_DATETIME, 'attributes' => array()),
                    array('name' => 'serial', 'type' => SchemaInterface::FIELD_SERIAL, 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => SchemaInterface::INDEX_PRIMARY, 'fields' => array('id')),
                    array('name' => 'uni', 'type' => SchemaInterface::INDEX_UNIQUE, 'fields' => array('int')),
                    array('name' => 'ind', 'type' => SchemaInterface::INDEX_INDEX, 'fields' => array('string')),
                    array('name' => 'fk', 'type' => SchemaInterface::INDEX_FOREIGN, 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
            array(
                'CREATE TABLE table ( id int(10) unsigned NOT NULL AUTO_INCREMENT, int int(10) NOT NULL, string char(10) NOT NULL, decimal decimal(10,2) unsigned NOT NULL, boolean tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\', datetime datetime NOT NULL, serial text COMMENT \'serial\', PRIMARY KEY (id), UNIQUE KEY uni (int), KEY ind (string), CONSTRAINT fk FOREIGN KEY (id) REFERENCES rm_rel (src_id) ON UPDATE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'id', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => SchemaInterface::FIELD_INTEGER, 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => SchemaInterface::FIELD_STRING, 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => SchemaInterface::FIELD_DECIMAL, 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => SchemaInterface::FIELD_BOOLEAN, 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => SchemaInterface::FIELD_DATETIME, 'attributes' => array()),
                    array('name' => 'serial', 'type' => SchemaInterface::FIELD_SERIAL, 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => SchemaInterface::INDEX_PRIMARY, 'fields' => array('id')),
                    array('name' => 'uni', 'type' => SchemaInterface::INDEX_UNIQUE, 'fields' => array('int')),
                    array('name' => 'ind', 'type' => SchemaInterface::INDEX_INDEX, 'fields' => array('string')),
                    array('name' => 'fk', 'type' => SchemaInterface::INDEX_FOREIGN, 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
        );
    }
}