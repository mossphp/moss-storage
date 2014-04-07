<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder\PgSQL;

use Moss\Storage\Builder\BuilderException;
use Moss\Storage\Builder\SchemaBuilderInterface;

/**
 * Postgres schema builder - builds queries managing tables (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\Postgres
 */
class SchemaBuilder implements SchemaBuilderInterface
{
    const QUOTE = '"';

    private $fieldTypes = array(
        'boolean' => array('boolean'),
        'integer' => array('smallint', 'integer', 'bigint'),
        'decimal' => array('decimal', 'numeric', 'real', 'double precision'),
        'string' => array('character', 'varchar', 'char', 'text'),
        'datetime' => array('timestamp', 'date', 'time'),
        'serial' => array('bytea')
    );

    protected $operation;

    protected $table;

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
                return 'BOOLEAN';
                break;
            case 'integer':
                return isset($attributes['auto_increment']) ? 'SERIAL' : 'INTEGER';
                break;
            case 'decimal':
                $len = isset($attributes['length']) ? $attributes['length'] : 10;
                $prc = isset($attributes['precision']) ? $attributes['precision'] : 0;

                return sprintf('NUMERIC(%u,%u)', $len, $prc);
                break;
            case 'datetime':
                return 'TIMESTAMP WITHOUT TIME ZONE';
                break;
            case 'serial':
                return 'BYTEA';
                break;
            case 'string':
                $len = isset($attributes['length']) ? $attributes['length'] : null;

                return $len === null || $len > 1023 ? 'TEXT' : 'CHARACTER VARYING';
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for field "%s"', $type, $name));
                break;
        }
    }

    protected function buildColumnAttributes($type, array $attributes)
    {
        $node = array();

        if (isset($attributes['comment'])) {
            $node[] = 'COMMENT \'' . $attributes['comment'] . '\'';
        }

        if (isset($attributes['default'])) {
            if (!in_array($type, array('boolean', 'integer', 'decimal'))) {
                $node[] = 'DEFAULT \'' . $attributes['default'] . '\'';
            } elseif ($type === 'boolean') {
                $node[] = 'DEFAULT ' . ($attributes['default'] ? 'TRUE' : 'FALSE');
            } else {
                $node[] = 'DEFAULT ' . $attributes['default'];
            }
        } elseif (isset($attributes['null'])) {
            $node[] = 'DEFAULT NULL';
        } else {
            $node[] = 'NOT NULL';
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
                return 'CONSTRAINT ' . $this->quote($this->table . '_pk') . ' PRIMARY KEY (' . $this->buildIndexFields($fields) . ')';
                break;
            case 'foreign':
                return 'CONSTRAINT ' . $this->quote($this->table . '_' . $name) . ' FOREIGN KEY (' . $this->buildIndexFields(array_keys($fields)) . ') REFERENCES ' . $this->quote($table) . ' (' . $this->buildIndexFields(array_values($fields)) . ') MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT';
                break;
            case 'unique':
                return 'CONSTRAINT ' . $this->quote($this->table . '_' . $name) . ' UNIQUE (' . $this->buildIndexFields($fields) . ')';
                break;
            case 'index':
                return 'CREATE INDEX ' . $this->quote($this->table . '_' . $name) . ' ON ' . $this->quote($this->table) . ' ( ' . $this->buildIndexFields($fields) . ' )';
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
            $fields,
            function (&$field) use ($self) {
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
                $stmt[] = 'SELECT table_name FROM information_schema.tables WHERE table_name = \'' . $this->table . '\'';
                break;
            case 'info':
                // todo - read comments
                $stmt[] = 'SELECT c.ordinal_position AS "pos", c.table_schema AS "schema", c.table_name AS "table", c.column_name AS "column_name", c.data_type AS "column_type", CASE WHEN c.character_maximum_length IS NOT NULL THEN c.character_maximum_length ELSE c.numeric_precision END AS "column_length", c.numeric_scale AS "column_precision", \'TODO\' AS "column_unsigned", c.is_nullable AS "column_nullable", CASE WHEN POSITION(\'nextval\' IN c.column_default) > 0 THEN \'YES\' ELSE \'NO\' END AS "column_auto_increment", CASE WHEN POSITION(\'nextval\' IN c.column_default) > 0 THEN NULL ELSE c.column_default END AS "column_default", \'\' AS "column_comment", k.constraint_name AS "index_name", i.constraint_type AS "index_type", k.ordinal_position AS "index_pos", CASE WHEN i.constraint_type = \'FOREIGN KEY\' THEN u.table_schema ELSE NULL END AS "ref_schema", CASE WHEN i.constraint_type = \'FOREIGN KEY\' THEN u.table_name ELSE NULL END AS "ref_table", CASE WHEN i.constraint_type = \'FOREIGN KEY\' THEN u.column_name ELSE NULL END AS "ref_column" FROM information_schema.columns AS c LEFT JOIN information_schema.key_column_usage AS k ON c.table_schema = k.table_schema AND c.table_name = k.table_name AND c.column_name = k.column_name LEFT JOIN information_schema.table_constraints AS i ON k.constraint_name = i.constraint_name AND i.constraint_type != \'CHECK\' LEFT JOIN information_schema.constraint_column_usage AS u ON u.constraint_name = i.constraint_name WHERE c.table_name = \'' . $this->table . '\' ORDER BY "pos"';
                break;
            case 'create':
                $stmt[] = 'CREATE TABLE';
                $stmt[] = $this->quote($this->table);
                $stmt[] = '(';

                $nodes = array();
                foreach ($this->columns as $node) {
                    $nodes[] = $this->buildColumn($node[0], $node[1], $node[2]);
                }

                $indexes = array();
                foreach ($this->indexes as $node) {
                    if ($node[2] === 'index') {
                        $indexes[] = $node;
                        continue;
                    }

                    $nodes[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
                }

                $stmt[] = implode(', ', $nodes);
                $stmt[] = ')';

                foreach ($this->indexes as $node) {
                    if ($node[2] !== 'index') {
                        continue;
                    }

                    if (!empty($stmt)) {
                        $stmt[] = ';';
                    }

                    $stmt[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
                }

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
                    if ($node[2] === 'index') {
                        continue;
                    }

                    $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
                }

                $stmt[] = implode(', ', $nodes);

                foreach ($this->indexes as $node) {
                    if ($node[2] !== 'index') {
                        continue;
                    }

                    if (!empty($stmt)) {
                        $stmt[] = ';';
                    }

                    $stmt[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
                }

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
                            $nodes[] = 'DROP CONSTRAINT ' . $this->quote($node[0]);
                            break;
                        default:
                            $nodes[] = 'DROP INDEX ' . $this->quote($node[0]);
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
     * Parsers read table structure into model-like array
     *
     * @param array $struct
     *
     * @return array
     */
    public function parse(array $struct)
    {
        $result = array(
            'table' => $struct[0]['table'],
            'fields' => array(),
            'indexes' => array()
        );

        $fields = array();
        $indexes = array();
        foreach ($struct as $node) {
            if (!isset($fields[$node['column_name']])) {
                $fields[$node['column_name']] = $this->parseColumn($node);
            }

            if (empty($node['index_name'])) {
                continue;
            }

            if (!isset($indexes[$node['index_name']])) {
                $indexes[$node['index_name']] = $this->parseIndex($node);
            } else {
                if (!in_array($node['column_name'], $indexes[$node['index_name']]['fields'])) {
                    $indexes[$node['index_name']]['fields'][] = $node['column_name'];
                }

                if (!empty($node['ref_column']) && !in_array($node['ref_column'], $indexes[$node['index_name']]['foreign'])) {
                    $indexes[$node['index_name']]['foreign'][] = $node['ref_column'];
                }
            }
        }

        $result['fields'] = array_values($fields);
        $result['indexes'] = array_values($indexes);

        return $result;
    }

    protected function parseColumn($node)
    {
        $type = strtolower(preg_replace('/^([^ (]+).*/i', '$1', $node['column_type']));

        $result = array(
            'name' => $node['column_name'],
            'type' => $node['column_type'],
            'attributes' => array(
                'length' => (int) $node['column_length'],
                'precision' => (int) $node['column_precision'],
                'null' => $node['column_nullable'] == 'YES',
                'unsigned' => $node['column_unsigned'] === 'YES',
                'auto_increment' => $node['column_auto_increment'] === 'YES',
                'default' => empty($node['column_default']) ? null : $node['column_default'],
                'comment' => empty($node['column_comment']) ? null : $node['column_comment']
            )
        );

        switch ($type) {
            case in_array($type, $this->fieldTypes['boolean']):
                $result['type'] = 'boolean';
                break;
            case in_array($type, $this->fieldTypes['serial']):
                $result['type'] = 'serial';
                break;
            case in_array($type, $this->fieldTypes['integer']):
                $result['type'] = 'integer';
                break;
            case in_array($type, $this->fieldTypes['decimal']):
                $result['type'] = 'decimal';
                break;
            case in_array($type, $this->fieldTypes['string']):
                $result['type'] = 'string';
                break;
            case in_array($type, $this->fieldTypes['datetime']):
                $result['type'] = 'datetime';
                break;
            default:
                throw new BuilderException(sprintf('Invalid or unsupported field type "%s" in table "%s"', $type, $this->table));
        }

        return $result;
    }

    protected function parseIndex($node)
    {
        $type = strtolower(preg_replace('/^([^ (]+).*/i', '$1', $node['index_type']));

        $result = array(
            'name' => $node['index_name'],
            'type' => $node['index_type'],
            'fields' => array($node['column_name']),
            'table' => $node['ref_table'],
            'foreign' => empty($node['ref_column']) ? array() : array($node['ref_column'])
        );

        switch ($type) {
            case 'primary':
                $result['type'] = 'primary';
                break;
            case 'unique':
                $result['type'] = 'unique';
                break;
            case 'index':
                $result['type'] = 'index';
                break;
            case 'foreign':
                $result['type'] = 'foreign';
                break;
            default:
                throw new BuilderException(sprintf('Invalid or unsupported index type "%s" in table "%s"', $type, $this->table));
        }

        return $result;
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
