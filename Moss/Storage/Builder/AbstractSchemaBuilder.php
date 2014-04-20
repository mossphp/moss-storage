<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder;

/**
 * Abstract schema builder
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class AbstractSchemaBuilder
{
    protected $fieldTypes = array(
        'boolean' => array(),
        'integer' => array(),
        'decimal' => array(),
        'string' => array(),
        'datetime' => array(),
        'serial' => array()
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

    /**
     * Builds column definitions and return them as array
     *
     * @return array
     */
    abstract protected function buildColumns();

    /**
     * Builds column definitions for add alteration
     *
     * @return array
     */
    abstract protected function buildAddColumns();

    /**
     * Builds column definitions for change
     *
     * @return array
     */
    abstract protected function buildChangeColumns();

    /**
     * Builds columns list to drop
     *
     * @return array
     */
    abstract protected function buildDropColumns();

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

    /**
     * Builds key/index definitions and returns them as array
     *
     * @return array
     */
    abstract protected function buildIndexes();

    /**
     * Builds key/index definitions to add
     *
     * @return array
     */
    abstract protected function buildAddIndex();

    /**
     * Builds key/index definitions to add
     *
     * @return array
     */
    abstract protected function buildDropIndex();

    /**
     * Builds query string
     *
     * @return string
     */
    abstract public function build();

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
            'table' => $struct[0]['table_name'],
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

        foreach($indexes as &$node) {
            if($node['type'] === 'foreign') {
                $node['fields'] = array_combine($node['fields'], $node['foreign']);
            }
            unset($node['foreign'], $node);
        }

        $result['fields'] = array_values($fields);
        $result['indexes'] = array_values($indexes);

        return $result;
    }

    /**
     * Build model like column description from passed row
     *
     * @param array $node
     *
     * @return array
     */
    abstract protected function parseColumn($node);

    /**
     * Build model like index description from passed row
     *
     * @param array $node
     *
     * @return array
     */
    abstract protected function parseIndex($node);

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
        return (string) $this->build();
    }
}
