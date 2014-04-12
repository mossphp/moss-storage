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
use Moss\Storage\Builder\AbstractSchemaBuilder;
use Moss\Storage\Builder\SchemaBuilderInterface;

/**
 * Postgres schema builder - builds queries managing tables (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class SchemaBuilder extends AbstractSchemaBuilder implements SchemaBuilderInterface
{
    protected $fieldTypes = array(
        'boolean' => array('boolean'),
        'integer' => array('smallint', 'integer', 'bigint'),
        'decimal' => array('decimal', 'numeric', 'real', 'double precision'),
        'string' => array('character', 'varchar', 'char', 'text'),
        'datetime' => array('timestamp', 'date', 'time'),
        'serial' => array('bytea')
    );

    /**
     * Builds column definitions and return them as array
     *
     * @return array
     */
    protected function buildColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $nodes[] = $this->buildColumn($node[0], $node[1], $node[2]);
        }

        return $nodes;
    }

    /**
     * Builds column definitions for add alteration
     *
     * @return array
     */
    protected function buildAddColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $str = 'ADD ' . $this->buildColumn($node[0], $node[1], $node[2]);

            if ($node[3] !== null) {
                $str .= ' AFTER ' . $node[3];
            }

            $nodes[] = $str;
        }

        return $nodes;
    }

    /**
     * Builds column definitions for change
     *
     * @return array
     */
    protected function buildChangeColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $nodes[] = 'CHANGE ' . ($node[3] ? $node[3] : $node[0]) . ' ' . $this->buildColumn($node[0], $node[1], $node[2]);
        }

        return $nodes;
    }

    /**
     * Builds columns list to drop
     *
     * @return array
     */
    protected function buildDropColumns()
    {
        $nodes = array();

        foreach ($this->columns as $node) {
            $nodes[] = 'DROP ' . $node[0];
        }

        return $nodes;
    }


    private function buildColumn($name, $type, array $attributes)
    {
        return $name . ' ' . $this->buildColumnType($name, $type, $attributes) . ' ' . $this->buildColumnAttributes($type, $attributes);
    }

    private function buildColumnType($name, $type, array $attributes)
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

    private function buildColumnAttributes($type, array $attributes)
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
     * Builds key/index definitions and returns them as array
     *
     * @param bool $index
     *
     * @return array
     */
    protected function buildIndexes($index = false)
    {
        $nodes = array();

        foreach ($this->indexes as $node) {
            if (($node[2] === 'index' && $index === true) || ($node[2] !== 'index' && $index === false)) {
                $nodes[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
            }
        }

        return $nodes;
    }

    /**
     * Builds key/index definitions to add
     *
     * @return array
     */
    protected function buildAddIndex()
    {
        $nodes = array();

        foreach ($this->indexes as $node) {
            if ($node[2] === 'index') {
                continue;
            }

            $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
        }

        return $nodes;
    }

    /**
     * Builds key/index definitions to add
     *
     * @return array
     */
    protected function buildDropIndex()
    {
        $nodes = array();

        foreach ($this->indexes as $node) {
            switch ($node[2]) {
                case 'primary':
                    $nodes[] = 'DROP PRIMARY KEY';
                    break;
                case 'foreign':
                    $nodes[] = 'DROP CONSTRAINT ' . $node[0];
                    break;
                default:
                    $nodes[] = 'DROP INDEX ' . $node[0];
            }
        }

        return $nodes;
    }

    private function buildIndex($name, array $fields, $type = 'index', $table = null)
    {
        switch ($type) {
            case 'primary':
                return 'CONSTRAINT ' . $this->table . '_pk' . ' PRIMARY KEY (' . implode(', ', $fields) . ')';
                break;
            case 'foreign':
                return 'CONSTRAINT ' . $this->table . '_' . $name . ' FOREIGN KEY (' . implode(', ', array_keys($fields)) . ') REFERENCES ' . $table . ' (' . implode(', ', array_values($fields)) . ') MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT';
                break;
            case 'unique':
                return 'CONSTRAINT ' . $this->table . '_' . $name . ' UNIQUE (' . implode(', ', $fields) . ')';
                break;
            case 'index':
                return 'CREATE INDEX ' . $this->table . '_' . $name . ' ON ' . $this->table . ' ( ' . implode(', ', $fields) . ' )';
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for index "%s"', $type, $name));
                break;
        }
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
                $stmt[] = 'SELECT c.ordinal_position AS pos, c.table_schema AS table_schema, c.table_name AS table_name, c.column_name AS column_name, c.data_type AS column_type, CASE WHEN c.character_maximum_length IS NOT NULL THEN c.character_maximum_length ELSE c.numeric_precision END AS column_length, c.numeric_scale AS column_precision, \'TODO\' AS column_unsigned, c.is_nullable AS column_nullable, CASE WHEN POSITION(\'nextval\' IN c.column_default) > 0 THEN \'YES\' ELSE \'NO\' END AS column_auto_increment, CASE WHEN POSITION(\'nextval\' IN c.column_default) > 0 THEN NULL ELSE c.column_default END AS column_default, \'\' AS column_comment, k.constraint_name AS index_name, i.constraint_type AS index_type, k.ordinal_position AS index_pos, CASE WHEN i.constraint_type = \'FOREIGN KEY\' THEN u.table_schema ELSE NULL END AS ref_schema, CASE WHEN i.constraint_type = \'FOREIGN KEY\' THEN u.table_name ELSE NULL END AS ref_table, CASE WHEN i.constraint_type = \'FOREIGN KEY\' THEN u.column_name ELSE NULL END AS ref_column FROM information_schema.columns AS c LEFT JOIN information_schema.key_column_usage AS k ON c.table_schema = k.table_schema AND c.table_name = k.table_name AND c.column_name = k.column_name LEFT JOIN information_schema.table_constraints AS i ON k.constraint_name = i.constraint_name AND i.constraint_type != \'CHECK\' LEFT JOIN information_schema.constraint_column_usage AS u ON u.constraint_name = i.constraint_name WHERE c.table_name = \'' . $this->table . '\' ORDER BY pos';
                break;
            case 'create':
                $stmt[] = 'CREATE TABLE';
                $stmt[] = $this->table;
                $stmt[] = '(';
                $stmt[] = implode(', ', array_merge($this->buildColumns(), $this->buildIndexes(false)));
                $stmt[] = ')';

                $nodes = $this->buildIndexes(true);
                $stmt[] = $nodes ? '; '.implode('; ', $nodes) : null;
                break;
            case 'add':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->table;
                $stmt[] = implode(', ', array_merge($this->buildAddColumns(), $this->buildAddIndex(false)));

                $nodes = $this->buildIndexes(true);
                $stmt[] = $nodes ? '; '.implode('; ', $nodes) : null;
                break;
            case 'change':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->table;
                $stmt[] = implode(', ', array_merge($this->buildChangeColumns()));
                break;
            case 'remove':
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->table;
                $stmt[] = implode(', ', array_merge($this->buildDropColumns(), $this->buildDropIndex()));
                break;
            case 'drop':
                $stmt[] = 'DROP TABLE IF EXISTS';
                $stmt[] = $this->table;
                break;
        }

        $stmt = array_filter($stmt);

        return implode(' ', $stmt);
    }

    /**
     * Returns string with additional options for table creation
     *
     * @return string
     */
    protected function buildAdditionalCreateOptions()
    {
        return '';
    }

    /**
     * Build model like column description from passed row
     *
     * @param array $node
     *
     * @return array
     * @throws BuilderException
     */
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

    /**
     * Build model like index description from passed row
     *
     * @param array $node
     *
     * @return array
     * @throws BuilderException
     */
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

        if($result['type'] == 'primary') {
            $result['name'] = 'primary';
        } else {
            $result['name'] = substr($result['name'], strlen($node['table_name'])+1);
        }

        return $result;
    }
}
