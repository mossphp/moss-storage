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
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = \'table\''
            ),
            array(
                'info',
                'SELECT c.ORDINAL_POSITION AS `pos`, c.TABLE_SCHEMA AS `schema`, c.TABLE_NAME AS `table`, c.COLUMN_NAME AS `column_name`, c.DATA_TYPE AS `column_type`, CASE WHEN LOCATE(\'(\', c.NUMERIC_PRECISION) > 0 IS NOT NULL THEN c.NUMERIC_PRECISION ELSE c.CHARACTER_MAXIMUM_LENGTH END AS `column_length`, c.NUMERIC_SCALE AS `column_precision`, CASE WHEN INSTR(LOWER(c.COLUMN_TYPE), \'unsigned\') > 0 THEN \'YES\' ELSE \'NO\' END AS `column_unsigned`, c.IS_NULLABLE AS `column_nullable`, CASE WHEN INSTR(LOWER(c.EXTRA), \'auto_increment\') > 0 THEN \'YES\' ELSE \'NO\' END AS `column_auto_increment`, c.COLUMN_DEFAULT AS `column_default`, c.COLUMN_COMMENT AS `column_comment`, k.CONSTRAINT_NAME AS `index_name`, CASE WHEN (i.CONSTRAINT_TYPE IS NULL AND k.CONSTRAINT_NAME IS NOT NULL) THEN \'INDEX\' ELSE i.CONSTRAINT_TYPE END AS `index_type`, k.ORDINAL_POSITION AS `index_pos`, k.REFERENCED_TABLE_SCHEMA AS `ref_schema`, k.REFERENCED_TABLE_NAME AS `ref_table`, k.REFERENCED_COLUMN_NAME AS `ref_column` FROM information_schema.COLUMNS AS c LEFT JOIN information_schema.KEY_COLUMN_USAGE AS k ON c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME LEFT JOIN information_schema.STATISTICS AS s ON c.TABLE_SCHEMA = s.TABLE_SCHEMA AND c.TABLE_NAME = s.TABLE_NAME AND c.COLUMN_NAME = s.COLUMN_NAME LEFT JOIN information_schema.TABLE_CONSTRAINTS AS i ON k.CONSTRAINT_SCHEMA = i.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME = i.CONSTRAINT_NAME WHERE c.TABLE_NAME = \'table\' ORDER BY `pos`'
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
                'TINYINT(1) NOT NULL'
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
                'BLOB NOT NULL'
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
                'UNIQUE KEY `table_foo` (`foo`)'
            ),
            array(
                'index',
                array('foo'),
                null,
                'KEY `table_foo` (`foo`)'
            ),
            array(
                'foreign',
                array('foo' => 'bar'),
                'yada',
                'CONSTRAINT `table_foo` FOREIGN KEY (`foo`) REFERENCES `yada` (`bar`) ON UPDATE CASCADE ON DELETE RESTRICT'
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
    public function testParse($array, $fields, $indexes = array())
    {
        $expected = array(
            'table' => 'table',
            'fields' => $fields,
            'indexes' => $indexes,
        );

        $schema = new SchemaBuilder();
        $result = $schema->parse($array);
        $this->assertEquals($expected, $result);
    }

    public function parseProvider()
    {
        return array(
            array(
                array($this->createInputColumn('column', 'tinyint', array())),
                array($this->createOutputColumn('column', 'boolean', array())),
            ),
            array(
                array($this->createInputColumn('column', 'tinyint', array('comment' => 'boolean', 'default' => 0))),
                array($this->createOutputColumn('column', 'boolean', array('comment' => 'boolean', 'default' => 0))),
            ),
            array(
                array($this->createInputColumn('column', 'int')),
                array($this->createOutputColumn('column', 'integer')),
            ),
            array(
                array($this->createInputColumn('column', 'int', array('length' => 5))),
                array($this->createOutputColumn('column', 'integer', array('length' => 5))),
            ),
            array(
                array($this->createInputColumn('column', 'int', array('unsigned' => 'YES'))),
                array($this->createOutputColumn('column', 'integer', array('unsigned' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'int', array('auto_increment' => 'YES'))),
                array($this->createOutputColumn('column', 'integer', array('auto_increment' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'int', array('default' => 10))),
                array($this->createOutputColumn('column', 'integer', array('default' => 10))),
            ),
            array(
                array($this->createInputColumn('column', 'int', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'integer', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'decimal')),
                array($this->createOutputColumn('column', 'decimal')),
            ),
            array(
                array($this->createInputColumn('column', 'decimal', array('unsigned' => 'YES'))),
                array($this->createOutputColumn('column', 'decimal', array('unsigned' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'decimal', array('length' => 4, 'precision' => 2))),
                array($this->createOutputColumn('column', 'decimal', array('length' => 4, 'precision' => 2))),
            ),
            array(
                array($this->createInputColumn('column', 'decimal', array('default' => 10.2))),
                array($this->createOutputColumn('column', 'decimal', array('default' => 10.2))),
            ),
            array(
                array($this->createInputColumn('column', 'decimal', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'decimal', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'text')),
                array($this->createOutputColumn('column', 'string')),
            ),
            array(
                array($this->createInputColumn('column', 'char', array('length' => 100))),
                array($this->createOutputColumn('column', 'string', array('length' => 100))),
            ),
            array(
                array($this->createInputColumn('column', 'varchar', array('length' => 300))),
                array($this->createOutputColumn('column', 'string', array('length' => 300))),
            ),
            array(
                array($this->createInputColumn('column', 'text', array('length' => 2000))),
                array($this->createOutputColumn('column', 'string', array('length' => 2000))),
            ),
            array(
                array($this->createInputColumn('column', 'text', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'string', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'datetime')),
                array($this->createOutputColumn('column', 'datetime')),
            ),
            array(
                array($this->createInputColumn('column', 'datetime', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'datetime', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'blob', array())),
                array($this->createOutputColumn('column', 'serial', array())),
            ),
            array(
                array($this->createInputColumn('column', 'blob', array('null' => 'YES'))),
                array($this->createOutputColumn('column', 'serial', array('null' => true))),
            ),
            array(
                array($this->createInputColumn('column', 'int', array(), array('name' => 'primary', 'type' => 'primary', 'pos' => 1))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('primary', 'primary', array('column')))
            ),
            array(
                array($this->createInputColumn('column', 'int', array(), array('name' => 'idx', 'type' => 'unique', 'pos' => 1))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('idx', 'unique', array('column')))
            ),
            array(
                array($this->createInputColumn('column', 'int', array(), array('name' => 'idx', 'type' => 'index', 'pos' => 1))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('idx', 'index', array('column')))
            ),
            array(
                array($this->createInputColumn('column', 'int', array(), array('name' => 'idx', 'type' => 'foreign', 'pos' => 1), array('table' => 'other', 'column' => 'ref'))),
                array($this->createOutputColumn('column', 'integer', array('length' => 0))),
                array($this->createOutputIndex('idx', 'foreign', array('column'), array('table' => 'other', 'fields' => array('ref'))))
            ),
            array(
                array(
                    $this->createInputColumn('columnA', 'int', array(), array('name' => 'idx', 'type' => 'foreign', 'pos' => 1), array('table' => 'other', 'column' => 'refA')),
                    $this->createInputColumn('columnB', 'int', array(), array('name' => 'idx', 'type' => 'foreign', 'pos' => 2), array('table' => 'other', 'column' => 'refB'))
                ),
                array(
                    $this->createOutputColumn('columnA', 'integer', array('length' => 0)),
                    $this->createOutputColumn('columnB', 'integer', array('length' => 0))
                ),
                array(
                    $this->createOutputIndex('idx', 'foreign', array('columnA', 'columnB'), array('table' => 'other', 'fields' => array('refA', 'refB')))
                )
            ),
        );
    }

    protected function createInputColumn($name, $type, $attributes = array(), $index = array(), $ref = array())
    {
        return array(
            'pos' => 1,
            'schema' => 'test',
            'table' => 'table',
            'column_name' => $name,
            'column_type' => $type,
            'column_length' => $this->get($attributes, 'length'),
            'column_precision' => $this->get($attributes, 'precision', 0),
            'column_unsigned' => $this->get($attributes, 'unsigned', 'NO'),
            'column_nullable' => $this->get($attributes, 'nullable', 'NO'),
            'column_auto_increment' => $this->get($attributes, 'auto_increment', 'NO'),
            'column_default' => $this->get($attributes, 'default', null),
            'column_comment' => $this->get($attributes, 'comment', ''),
            'index_name' => $this->get($index, 'name', null),
            'index_type' => $this->get($index, 'type', null),
            'index_pos' => $this->get($index, 'pos', null),
            'ref_schema' => $this->get($ref, 'schema', null),
            'ref_table' => $this->get($ref, 'table', null),
            'ref_column' => $this->get($ref, 'column', null),
        );
    }

    protected function createOutputColumn($name, $type, $attributes = array())
    {
        return array(
            'name' => $name,
            'type' => $type,
            'attributes' => array(
                'length' => $this->get($attributes, 'length'),
                'precision' => $this->get($attributes, 'precision', 0),
                'null' => $this->get($attributes, 'nullable', false),
                'unsigned' => $this->get($attributes, 'unsigned', false),
                'auto_increment' => $this->get($attributes, 'auto_increment', false),
                'default' => $this->get($attributes, 'default', null),
                'comment' => $this->get($attributes, 'comment', null),
            )
        );
    }

    protected function createOutputIndex($name, $type, array $fields, $ref = array())
    {
        return array(
            'name' => $name,
            'type' => $type,
            'fields' => $fields,
            'table' => $this->get($ref, 'table'),
            'foreign' => $this->get($ref, 'fields', array())
        );
    }

    protected function get($array, $offset, $default = null)
    {
        return array_key_exists($offset, $array) ? $array[$offset] : $default;
    }
}