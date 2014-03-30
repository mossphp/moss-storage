<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder\MySQL;

use Moss\Storage\Builder\BuilderException;
use Moss\Storage\Builder\SchemaBuilderInterface;

/**
 * MySQL schema builder - builds queries managing tables (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\MySQL
 */
class SchemaBuilder implements SchemaBuilderInterface
{
    const QUOTE = '`';

    private $fieldTypes = array(
        'boolean' => array('tinyint:boolean'), // tinyint with "bool" in comment
        'integer' => array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'),
        'decimal' => array('decimal'),
        'string' => array('char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext'),
        'datetime' => array('time', 'date', 'datetime', 'timestamp', 'year'),
        'serial' => array('text:serial') // text with "serial" in comment
    );

    private $defaults = array(
        'name' => null,
        'type' => 'string',
        'attributes' => 'null',
        'length' => null,
        'precision' => null,
        'null' => false,
        'unsigned' => false,
        'auto' => false,
        'default' => null,
        'comment' => null
    );

    protected $operation;

    protected $table;
    protected $engine = 'InnoDB';
    protected $charset = 'utf8';

    protected $columns = array();
    protected $indexes = array();

    /**
     * Constructor
     *
     * @param string $table
     * @param string $operation
     */
    public function __construct($table = null, $operation = 'create')
    {
        if ($table !== null) {
            $this->table($table);
        }

        if ($operation !== null) {
            $this->operation($operation);
        }
    }

    protected function quote($string)
    {
        return self::QUOTE . $string . self::QUOTE;
    }

    /**
     * Sets check operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function check($table)
    {
        return $this->operation('check')
            ->table($table);
    }

    /**
     * Sets info operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function info($table)
    {
        return $this->operation('info')
            ->table($table);
    }

    /**
     * Sets create operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function create($table)
    {
        return $this->operation('create')
            ->table($table);
    }

    /**
     * Sets add operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function add($table)
    {
        return $this->operation('add')
            ->table($table);
    }

    /**
     * Sets change operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function change($table)
    {
        return $this->operation('change')
            ->table($table);
    }

    /**
     * Sets remove operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function remove($table)
    {
        return $this->operation('remove')
            ->table($table);
    }

    /**
     * Sets drop operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function drop($table)
    {
        return $this->operation('drop')
            ->table($table);
    }

    /**
     * Sets table name
     *
     * @param string $table
     *
     * @return $this
     * @throws BuilderException
     */
    public function table($table)
    {
        if (empty($table)) {
            throw new BuilderException('Missing table name');
        }

        $this->table = $table;

        return $this;
    }

    /**
     * Sets operation for builder
     *
     * @param string $operation
     *
     * @return $this
     * @throws BuilderException
     */
    public function operation($operation)
    {
        switch ($operation) {
            case 'check':
            case 'info':
            case 'create':
            case 'add':
            case 'change':
            case 'remove':
            case 'drop':
                break;
            default:
                throw new BuilderException(sprintf('Unknown operation "%s"', $operation));
        }

        $this->operation = $operation;

        return $this;
    }

    /**
     * Sets table column
     *
     * @param string      $name
     * @param string      $type
     * @param array       $attributes
     * @param null|string $after
     *
     * @return $this
     * @throws BuilderException
     */
    public function column($name, $type = 'string', $attributes = array(), $after = null)
    {
        $this->assertColumnType($type);

        $this->columns[] = array(
            $name,
            $type,
            $this->prepareAttributes($attributes),
            $after
        );

        return $this;
    }

    protected function assertColumnType($type)
    {
        if (!isset($this->fieldTypes[$type])) {
            throw new BuilderException(sprintf('Invalid column type "%s" in "%s"', $type, $this->table));
        }
    }

    protected function prepareAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                unset($attributes[$key]);
                $key = $value;
                $value = true;
            }

            if ($key === 'length' || $key === 'precision') {
                $value = (int) $value;
            }

            $attributes[$key] = $value;
        }

        return array_change_key_case($attributes, \CASE_LOWER);
    }

    protected function buildColumn($name, $type, array $attributes)
    {
        return $this->quote($name) . ' ' . $this->buildColumnType($name, $type, $attributes) . ' ' . $this->buildColumnAttributes($type, $attributes);
    }

    protected function buildColumnType($name, $type, array $attributes)
    {
        switch ($type) {
            case 'boolean':
                return 'TINYINT(1) COMMENT \'boolean\'';
                break;
            case 'integer':
                $len = isset($attributes['length']) ? $attributes['length'] : 10;

                return sprintf('INT(%u)', $len);
                break;
            case 'decimal':
                $len = isset($attributes['length']) ? $attributes['length'] : 10;
                $prc = isset($attributes['precision']) ? $attributes['precision'] : 0;

                return sprintf('DECIMAL(%u,%u)', $len, $prc);
                break;
            case 'datetime':
                return 'DATETIME';
                break;
            case 'serial':
                return 'TEXT COMMENT \'serial\'';
                break;
            case 'string':
                $len = isset($attributes['length']) ? $attributes['length'] : null;
                if ($len == 0 || $len > 1023) {
                    return 'TEXT';
                } elseif ($len > 255) {
                    return sprintf('VARCHAR(%u)', $len);
                } else {
                    return sprintf('CHAR(%u)', $len);
                }
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for field "%s"', $type, $name));
                break;
        }
    }

    protected function buildColumnAttributes($type, array $attributes)
    {
        $node = array();

        if (isset($attributes['comment']) && $type != 'boolean' && $type != 'serial') {
            $node[] = 'COMMENT \'' . $attributes['comment'] . '\'';
        }

        if (isset($attributes['unsigned']) && in_array($type, array('integer', 'decimal'))) {
            $node[] = 'UNSIGNED';
        }

        if (isset($attributes['default'])) {
            if (!in_array($type, array('boolean', 'integer', 'decimal'))) {
                $node[] = 'DEFAULT \'' . $attributes['default'] . '\'';
            } else {
                $node[] = 'DEFAULT ' . $attributes['default'];
            }
        } elseif (isset($attributes['null'])) {
            $node[] = 'DEFAULT NULL';
        } else {
            $node[] = 'NOT NULL';
        }

        if ($type == 'integer' && isset($attributes['auto_increment'])) {
            $node[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $node);
    }

    /**
     * Sets key/index to table
     *
     * @param array $localFields
     *
     * @return $this
     */
    public function primary(array $localFields)
    {
        $this->assertIndexFields($localFields);

        $this->index('primary', $localFields, 'primary');
    }

    /**
     * Sets key/index to table
     *
     * @param string $name
     * @param array  $fields
     * @param string $table
     *
     * @return $this
     */
    public function foreign($name, array $fields, $table)
    {
        $this->assertIndexFields($fields);

        $this->indexes[] = array(
            $name,
            (array) $fields,
            'foreign',
            $table,
        );

        return $this;
    }

    /**
     * Sets key/index to table
     *
     * @param string $name
     * @param array  $fields
     *
     * @return $this
     */
    public function unique($name, array $fields)
    {
        $this->assertIndexFields($fields);

        $this->indexes[] = array(
            $name,
            (array) $fields,
            'unique',
            null
        );

        return $this;
    }

    /**
     * Sets key/index to table
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     * @param null   $table
     *
     * @return $this
     */
    public function index($name, array $fields, $type = 'index', $table = null)
    {
        $this->assertIndexType($type);
        $this->assertIndexFields($fields);

        $this->indexes[] = array(
            $name,
            (array) $fields,
            $type,
            $table,
        );

        return $this;
    }

    protected function assertIndexType($type)
    {
        if (!in_array($type, array('primary', 'index', 'unique', 'foreign'))) {
            throw new BuilderException(sprintf('Invalid index type "%s" in "%s"', $type, $this->table));
        }
    }

    protected function assertIndexFields($fields)
    {
        if (empty($fields)) {
            throw new BuilderException(sprintf('Missing fields for index in "%s"', $this->table));
        }
    }

    protected function buildIndex($name, array $fields, $type = 'index', $table = null)
    {
        switch ($type) {
            case 'primary':
                return 'PRIMARY KEY (' . $this->buildIndexFields($fields) . ')';
                break;
            case 'foreign':
                return 'CONSTRAINT ' . $this->quote($name) . ' FOREIGN KEY (' . $this->buildIndexFields(array_keys($fields)) . ') REFERENCES ' . $this->quote($table) . ' (' . $this->buildIndexFields(array_values($fields)) . ') ON UPDATE CASCADE ON DELETE RESTRICT';
                break;
            case 'unique':
                return 'UNIQUE KEY ' . $this->quote($name) . ' (' . $this->buildIndexFields($fields) . ')';
                break;
            case 'index':
                return 'KEY ' . $this->quote($name) . ' (' . $this->buildIndexFields($fields) . ')';
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for index "%s"', $type, $name));
                break;
        }
    }

    protected function buildIndexFields(array $fields)
    {
        $self = & $this;

        array_walk(
            $fields, function (&$field) use ($self) {
                $field = $this->quote($field);
            }
        );

        return implode(', ', $fields);
    }

    /**
     * Builds query string
     *
     * @return string
     * @throws BuilderException
     */
    public function build()
    {
        if (empty($this->table)) {
            throw new BuilderException('Missing table name');
        }

        $stmt = array();

        switch ($this->operation) {
            case 'check':
                $stmt[] = 'SHOW TABLES LIKE';
                $stmt[] = '\'' . $this->table . '\'';
                break;
            case 'info':
                $stmt[] = 'SHOW CREATE TABLE';
                $stmt[] = $this->quote($this->table);
                break;
            case 'create':
                $stmt[] = 'CREATE TABLE';
                $stmt[] = $this->quote($this->table);
                $stmt[] = '(';

                $nodes = array();
                foreach ($this->columns as $node) {
                    $nodes[] = $this->buildColumn($node[0], $node[1], $node[2]);
                }

                foreach ($this->indexes as $node) {
                    $nodes[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
                }

                $stmt[] = implode(', ', $nodes);

                $stmt[] = ')';
                $stmt[] = 'ENGINE=' . $this->engine;
                $stmt[] = sprintf('DEFAULT CHARSET=%1$s', $this->charset);
                break;
            case 'add':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->quote($this->table);
                $nodes = array();
                foreach ($this->columns as $node) {
                    $str = 'ADD ' . $this->buildColumn($node[0], $node[1], $node[2]);

                    if ($node[3] !== null) {
                        $str .= ' AFTER ' . $this->quote($node[3]);
                    }

                    $nodes[] = $str;
                }
                foreach ($this->indexes as $node) {
                    $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case 'change':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->quote($this->table);
                $nodes = array();
                foreach ($this->columns as $node) {
                    $str = 'CHANGE ' . $this->quote($node[3] ? $node[3] : $node[0]) . ' ' . $this->buildColumn($node[0], $node[1], $node[2]);
                    $nodes[] = $str;
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case 'remove':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->quote($this->table);
                $nodes = array();
                foreach ($this->columns as $node) {
                    $nodes[] = 'DROP ' . $this->quote($node[0]);
                }
                foreach ($this->indexes as $node) {
                    switch ($node[2]) {
                        case 'primary':
                            $nodes[] = 'DROP PRIMARY KEY';
                            break;
                        case 'foreign':
                            $nodes[] = 'DROP FOREIGN KEY ' . $this->quote($node[0]);
                            break;
                        default:
                            $nodes[] = 'DROP KEY ' . $this->quote($node[0]);
                    }
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case 'drop':
                $stmt[] = 'DROP TABLE IF EXISTS';
                $stmt[] = $this->quote($this->table);
                break;
        }

        $stmt = array_filter($stmt);

        return implode(' ', $stmt);
    }

    /**
     * Parsers create statement into array
     *
     * @param string $statement
     *
     * @return array
     * @throws BuilderException
     */
    public function parse($statement)
    {
        $statement = str_replace(array("\n", "\r", "\t", '  '), ' ', $statement);

        $content = preg_replace('/CREATE TABLE `?[^` (]+`? +\((.+)\).*/i', '$1', $statement);

        $result = array(
            'table' => preg_replace('/CREATE TABLE `?([^` (]+)`? +\(.*/i', '$1', $statement),
            'fields' => $this->parseColumns($content),
            'indexes' => $this->parseIndexes($content)
        );

        return $result;
    }

    protected function parseColumns($statement)
    {
        preg_match_all('/`?(?P<name>[^` ]+)`? (?P<type>((tiny|small|medium|big)?int|integer|decimal|(var)?char|(tiny|medium|long)?text|(date)?time(stamp)?|year))(\((?P<length>[\d]+)(\,(?P<precision>[\d]+))?\))?(?P<attributes>[^,)]+)?,?/i', $statement, $matches, \PREG_SET_ORDER);

        $columns = array();
        foreach ($matches as $match) {
            $match = array_merge($this->defaults, $match);

            $node = array(
                'name' => $match['name'],
                'type' => $match['type'],
                'attributes' => array(
                    'length' => (int) $match['length'],
                    'precision' => (int) $match['precision'],
                    'null' => stripos($match['attributes'], 'not null') === false || stripos($match['attributes'], 'default null') !== false,
                    'unsigned' => stripos($match['attributes'], 'unsigned') !== false,
                    'auto_increment' => stripos($match['attributes'], 'auto_increment') !== false,
                    'default' => stripos($match['attributes'], 'default') !== false ? preg_replace('/.*DEFAULT \'([^\']+)\'.*/i', '$1', $match['attributes']) : null,
                    'comment' => stripos($match['attributes'], 'comment') !== false ? preg_replace('/.*COMMENT \'([^\']+)\'.*/i', '$1', $match['attributes']) : null
                )
            );

            $type = strtolower($node['type']);
            $comm = strtolower($node['attributes']['comment']);
            switch ($type) {
                case in_array($type . ':' . $comm, $this->fieldTypes['boolean']):
                    $node['type'] = 'boolean';
                    break;
                case in_array($type . ':' . $comm, $this->fieldTypes['serial']):
                    $node['type'] = 'serial';
                    break;
                case in_array($type, $this->fieldTypes['integer']):
                    $node['type'] = 'integer';
                    break;
                case in_array($type, $this->fieldTypes['decimal']):
                    $node['type'] = 'decimal';
                    break;
                case in_array($type, $this->fieldTypes['string']):
                    $node['type'] = 'string';
                    break;
                case in_array($type, $this->fieldTypes['datetime']):
                    $node['type'] = 'datetime';
                    break;
                default:
                    throw new BuilderException(sprintf('Invalid or unsupported field type "%s" in table "%s"', $type, $this->table));
            }

            foreach ($node['attributes'] as $i => $attr) {
                if (!$attr) {
                    unset($node['attributes'][$i]);
                }
            }

            $columns[] = $node;
        }

        return $columns;
    }

    protected function parseIndexes($statement)
    {
        preg_match_all('/(?P<fname>`?[^` ,]+`?)? ?(?P<type>PRIMARY KEY|FOREIGN KEY|UNIQUE KEY|KEY) (?P<name>`?[^` (,]+`?)? ?\((?P<fields>[^)]+)\)( REFERENCES `?(?P<table>[^` (]+)`? ?\((?P<foreign>[^)]+)\))?/i', $statement, $matches, \PREG_SET_ORDER);

        $indexes = array();
        foreach ($matches as $match) {
            $node = array(
                'name' => trim($match['fname'] ? $match['fname'] : $match['name'], '`'),
                'type' => trim($match['type']),
                'fields' => explode(',', str_replace(array('`', ' '), null, $match['fields'])),
                'table' => isset($match['table']) ? trim($match['table'], '`') : null,
                'foreign' => isset($match['foreign']) ? explode(',', str_replace(array('`', ' '), null, $match['foreign'])) : array()
            );

            switch ($node['type']) {
                case 'PRIMARY KEY':
                    $node['name'] = 'primary';
                    $node['type'] = 'primary';
                    break;
                case 'FOREIGN KEY':
                    $node['type'] = 'foreign';
                    break;
                case 'UNIQUE KEY':
                    $node['type'] = 'unique';
                    break;
                default:
                    $node['type'] = 'index';
            }

            if ($node['type'] === 'foreign') {
                $node['fields'] = array_combine($node['fields'], $node['foreign']);
                unset($node['foreign']);
            } else {
                unset($node['table'], $node['foreign']);
            }

            $indexes[] = $node;
        }

        return $indexes;
    }

    /**
     * Resets builder
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;
        $this->table = null;

        $this->columns = array();
        $this->indexes = array();

        return $this;
    }

    /**
     * Casts query to string (builds it)
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this->build();
        } catch (\Exception $e) {
            return get_class($e) . ' - ' . $e->getMessage();
        }
    }
}
