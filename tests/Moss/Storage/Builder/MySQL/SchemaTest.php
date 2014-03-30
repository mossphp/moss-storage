<?php
namespace Moss\Storage\Builder\MySQL;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testMissingTable()
    {
        $schema = new SchemaBuilder(null);
        $schema->build();
    }

    public function testTable()
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->column('foo');
        $this->assertEquals('ALTER TABLE `table` ADD `foo` TEXT NOT NULL', $schema->build());
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Missing table name
     */
    public function testTableWithEmptyString()
    {
        $query = new SchemaBuilder('table', 'check');
        $query->reset()
            ->table('');
    }

    /**
     * @expectedException \Moss\Storage\Builder\BuilderException
     * @expectedExceptionMessage Unknown operation
     */
    public function testInvalidOperation()
    {
        new SchemaBuilder('table', 'foo');
    }

    /**
     * @dataProvider shortOperationProvider
     */
    public function testOperation($operation, $expected)
    {
        $schema = new SchemaBuilder('table', 'check');
        $schema
            ->operation($operation)
            ->column('foo')
            ->index('idx', array('foo'), 'index');
        $this->assertEquals($expected, $schema->build());
    }

    /**
     * @dataProvider shortOperationProvider
     */
    public function testOperationAliases($operation, $expected)
    {
        $schema = new SchemaBuilder('foo', 'check');
        $schema->{$operation}('table')
                ->column('foo')
               ->index('idx', array('foo'), 'index');
        $this->assertEquals($expected, $schema->build());
    }

    public function shortOperationProvider()
    {
        return array(
            array(
                'check',
                'SHOW TABLES LIKE \'table\''
            ),
            array(
                'info',
                'SHOW CREATE TABLE `table`'
            ),
            array(
                'drop',
                'DROP TABLE IF EXISTS `table`'
            )
        );
    }

    /**
     * @dataProvider columnProvider
     */
    public function testCreateColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'create');
        $schema->column('foo', $actual);
        $this->assertEquals('CREATE TABLE `table` ( `foo` ' . $expected . ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testAddColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE `table` ADD `foo` ' . $expected . '', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testChangeColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'change');
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE `table` CHANGE `foo` `foo` ' . $expected . '', $schema->build());
    }

    /**
     * @dataProvider columnProvider
     */
    public function testRemoveColumn($actual, $expected)
    {
        $schema = new SchemaBuilder('table', 'remove');
        $schema->column('foo', $actual);
        $this->assertEquals('ALTER TABLE `table` DROP `foo`', $schema->build());
    }

    public function columnProvider()
    {
        return array(
            array(
                'boolean',
                'TINYINT(1) COMMENT \'boolean\' NOT NULL'
            ),
            array(
                'integer',
                'INT(10) NOT NULL',
            ),
            array(
                'decimal',
                'DECIMAL(10,0) NOT NULL',
            ),
            array(
                'string',
                'TEXT NOT NULL'
            ),
            array(
                'datetime',
                'DATETIME NOT NULL'
            ),
            array(
                'serial',
                'TEXT COMMENT \'serial\' NOT NULL'
            ),

        );
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testColumnAttributes($type, $attributes, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->column('foo', $type, $attributes);
        $this->assertEquals('ALTER TABLE `table` ADD `foo` ' . $expected . '', $schema->build());
    }

    public function attributeProvider()
    {
        return array(
            array(
                'integer',
                array('unsigned'),
                'INT(10) UNSIGNED NOT NULL'
            ),
            array(
                'integer',
                array('default' => 1),
                'INT(10) DEFAULT 1'
            ),
            array(
                'integer',
                array('auto_increment'),
                'INT(10) NOT NULL AUTO_INCREMENT'
            ),
            array(
                'integer',
                array('null'),
                'INT(10) DEFAULT NULL'
            ),
            array(
                'integer',
                array('length' => 6),
                'INT(6) NOT NULL'
            ),
            array(
                'string',
                array('length' => null),
                'TEXT NOT NULL'
            ),
            array(
                'string',
                array('length' => 2048),
                'TEXT NOT NULL'
            ),
            array(
                'string',
                array('length' => 512),
                'VARCHAR(512) NOT NULL'
            ),
            array(
                'string',
                array('length' => 10),
                'CHAR(10) NOT NULL'
            ),
            array(
                'decimal',
                array('precision' => 2),
                'DECIMAL(10,2) NOT NULL'
            ),
            array(
                'decimal',
                array('length' => 6, 'precision' => 2),
                'DECIMAL(6,2) NOT NULL'
            ),
            array(
                'integer',
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
        $schema = new SchemaBuilder('table', 'create');
        $schema->column('foo', 'integer')
               ->index('foo', $fields, $type, $table);
        $this->assertEquals('CREATE TABLE `table` ( `foo` INT(10) NOT NULL, ' . $expected . ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8', $schema->build());
    }

    /**
     * @dataProvider indexProvider
     */
    public function testAddIndex($type, $fields, $table, $expected)
    {
        $schema = new SchemaBuilder('table', 'add');
        $schema->index('foo', $fields, $type, $table);
        $this->assertEquals('ALTER TABLE `table` ADD ' . $expected . '', $schema->build());
    }


    public function indexProvider()
    {
        return array(
            array(
                'primary',
                array('foo'),
                null,
                'PRIMARY KEY (`foo`)'
            ),
            array(
                'unique',
                array('foo'),
                null,
                'UNIQUE KEY `foo` (`foo`)'
            ),
            array(
                'index',
                array('foo'),
                null,
                'KEY `foo` (`foo`)'
            ),
            array(
                'foreign',
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
        $schema = new SchemaBuilder('table', 'remove');
        $schema->index('foo', $fields, $type, $table);
        $this->assertEquals('ALTER TABLE `table` DROP ' . $expected, $schema->build());
    }

    public function dropIndexProvider()
    {
        return array(
            array(
                'primary',
                array('foo'),
                null,
                'PRIMARY KEY'
            ),
            array(
                'unique',
                array('foo'),
                null,
                'KEY `foo`',
            ),
            array(
                'index',
                array('foo'),
                null,
                'KEY `foo`',
            ),
            array(
                'foreign',
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

        $schema = new SchemaBuilder();
        $result = $schema->parse($stmt);
        $this->assertEquals($expected, $result);
    }

    public function parseProvider()
    {
        return array(
            array(
                'CREATE TABLE `table` (`int` int(10) unsigned NOT NULL AUTO_INCREMENT,) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'int', 'type' => 'integer', 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`int` int(10) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'int', 'type' => 'integer', 'attributes' => array('length' => 10)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`string` char(10) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'string', 'type' => 'string', 'attributes' => array('length' => 10)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`decimal` decimal(10,2) unsigned NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'decimal', 'type' => 'decimal', 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`boolean` tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'boolean', 'type' => 'boolean', 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => 1, 'comment' => 'boolean')),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`datetime` datetime NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'datetime', 'type' => 'datetime', 'attributes' => array()),
                ),
                array()
            ),
            array(
                'CREATE TABLE `table` (`serial` text COMMENT \'serial\') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'serial', 'type' => 'serial', 'attributes' => array('null' => true, 'comment' => 'serial')),
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
                    array('name' => 'id', 'type' => 'integer', 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => 'integer', 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => 'string', 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => 'decimal', 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => 'boolean', 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => 'datetime', 'attributes' => array()),
                    array('name' => 'serial', 'type' => 'serial', 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => 'primary', 'fields' => array('id')),
                    array('name' => 'uni', 'type' => 'unique', 'fields' => array('int')),
                    array('name' => 'ind', 'type' => 'index', 'fields' => array('string')),
                    array('name' => 'fk', 'type' => 'foreign', 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
            array(
                'CREATE TABLE `table` ( `id` int(10) unsigned NOT NULL AUTO_INCREMENT, `int` int(10) NOT NULL, `string` char(10) NOT NULL, `decimal` decimal(10,2) unsigned NOT NULL, `boolean` tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\', `datetime` datetime NOT NULL, `serial` text COMMENT \'serial\', PRIMARY KEY (`id`), UNIQUE KEY `uni` (`int`), KEY `ind` (`string`), CONSTRAINT `fk` FOREIGN KEY (`id`) REFERENCES `rm_rel` (`src_id`) ON UPDATE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'id', 'type' => 'integer', 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => 'integer', 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => 'string', 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => 'decimal', 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => 'boolean', 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => 'datetime', 'attributes' => array()),
                    array('name' => 'serial', 'type' => 'serial', 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => 'primary', 'fields' => array('id')),
                    array('name' => 'uni', 'type' => 'unique', 'fields' => array('int')),
                    array('name' => 'ind', 'type' => 'index', 'fields' => array('string')),
                    array('name' => 'fk', 'type' => 'foreign', 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
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
                    array('name' => 'id', 'type' => 'integer', 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => 'integer', 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => 'string', 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => 'decimal', 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => 'boolean', 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => 'datetime', 'attributes' => array()),
                    array('name' => 'serial', 'type' => 'serial', 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => 'primary', 'fields' => array('id')),
                    array('name' => 'uni', 'type' => 'unique', 'fields' => array('int')),
                    array('name' => 'ind', 'type' => 'index', 'fields' => array('string')),
                    array('name' => 'fk', 'type' => 'foreign', 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
            array(
                'CREATE TABLE table ( id int(10) unsigned NOT NULL AUTO_INCREMENT, int int(10) NOT NULL, string char(10) NOT NULL, decimal decimal(10,2) unsigned NOT NULL, boolean tinyint(1) unsigned NOT NULL DEFAULT \'1\' COMMENT \'boolean\', datetime datetime NOT NULL, serial text COMMENT \'serial\', PRIMARY KEY (id), UNIQUE KEY uni (int), KEY ind (string), CONSTRAINT fk FOREIGN KEY (id) REFERENCES rm_rel (src_id) ON UPDATE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
                array(
                    array('name' => 'id', 'type' => 'integer', 'attributes' => array('length' => 10, 'unsigned' => true, 'auto_increment' => true)),
                    array('name' => 'int', 'type' => 'integer', 'attributes' => array('length' => 10)),
                    array('name' => 'string', 'type' => 'string', 'attributes' => array('length' => 10)),
                    array('name' => 'decimal', 'type' => 'decimal', 'attributes' => array('length' => 10, 'precision' => 2, 'unsigned' => true)),
                    array('name' => 'boolean', 'type' => 'boolean', 'attributes' => array('length' => 1, 'unsigned' => true, 'default' => '1', 'comment' => 'boolean')),
                    array('name' => 'datetime', 'type' => 'datetime', 'attributes' => array()),
                    array('name' => 'serial', 'type' => 'serial', 'attributes' => array('null' => true, 'comment' => 'serial')),
                ),
                'indexes' => array(
                    array('name' => 'primary', 'type' => 'primary', 'fields' => array('id')),
                    array('name' => 'uni', 'type' => 'unique', 'fields' => array('int')),
                    array('name' => 'ind', 'type' => 'index', 'fields' => array('string')),
                    array('name' => 'fk', 'type' => 'foreign', 'fields' => array('id' => 'src_id'), 'table' => 'rm_rel')
                )
            ),
        );
    }
}