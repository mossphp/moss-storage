<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Schema;

use Moss\Storage\Builder\SchemaBuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\Definition\IndexInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;

/**
 * Schema used to create and execute table related operations (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Schema implements SchemaInterface
{

    /** @var DriverInterface */
    protected $driver;

    /** @var SchemaBuilderInterface */
    protected $builder;

    /** @var ModelBag */
    protected $models;

    private $operation;

    private $before = array();
    private $queries = array();
    private $after = array();

    /**
     * Constructor
     *
     * @param DriverInterface  $driver
     * @param SchemaBuilderInterface $builder
     * @param ModelBag         $models
     */
    public function __construct(DriverInterface $driver, SchemaBuilderInterface $builder, ModelBag $models)
    {
        $this->driver = & $driver;
        $this->builder = & $builder;
        $this->models = & $models;
    }

    /**
     * Returns driver instance
     *
     * @return DriverInterface
     */
    public function driver()
    {
        return $this->driver;
    }

    /**
     * Returns builder instance
     *
     * @return SchemaBuilderInterface
     */
    public function builder()
    {
        return $this->builder;
    }

    /**
     * Sets check operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function check($entity = array())
    {
        return $this->operation('check', (array) $entity);
    }

    /**
     * Sets create operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function create($entity = array())
    {
        return $this->operation('create', (array) $entity);
    }

    /**
     * Sets alter operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function alter($entity = array())
    {
        return $this->operation('alter', (array) $entity);
    }

    /**
     * Sets drop operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function drop($entity = array())
    {
        return $this->operation('drop', (array) $entity);
    }

    /**
     * Sets query operation
     *
     * @param string $operation
     * @param array  $entity
     *
     * @return $this
     * @throws SchemaException
     */
    public function operation($operation, array $entity = array())
    {
        $this->operation = $operation;

        $models = array();
        foreach ($entity as $node) {
            $models[] = $this->models->get($node);
        }

        if (empty($models)) {
            $models = $this->models->all();
        }

        switch ($this->operation) {
            case 'check':
                foreach ($models as $model) {
                    $this->buildCheck($model);
                }
                break;
            case 'create':
                foreach ($models as $model) {
                    $this->buildCreate($model);
                }
                break;
            case 'alter':
                foreach ($models as $model) {
                    $this->buildAlter($model);
                }
                break;
            case 'drop':
                foreach ($models as $model) {
                    $this->buildDrop($model);
                }
                break;
            default:
                throw new SchemaException(sprintf('Unknown operation "%s" in schema query', $this->operation));
        }

        return $this;
    }

    /**
     * Builds check query
     *
     * @param ModelInterface $model
     */
    protected function buildCheck(ModelInterface $model)
    {
        $this->queries[$model->table()] = $this->builder->reset()
            ->check($model->table())
            ->build();
    }

    /**
     * Builds create table queries
     *
     * @param ModelInterface $model
     *
     * @throws SchemaException
     */
    protected function buildCreate(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            throw new SchemaException(sprintf('Unable to create table, table "%s" already exists', $model->table()));
        }

        $this->builder->reset()
            ->create($model->table());

        foreach ($model->fields() as $index) {
            $this->builder->column($index->name(), $index->type(), $index->attributes());
        }

        $foreign = array();
        foreach ($model->indexes() as $index) {
            if ($index->type() === 'foreign') {
                $foreign[] = $index;
                continue;
            }

            $this->builder->index($index->name(), $index->fields(), $index->type(), $index->table());
        }

        $this->queries[] = $this->builder->build();

        $this->builder->reset()
            ->addTo($model->table());

        foreach ($foreign as $index) {
            $this->after[] = $this->builder->reset()
                ->addTo($model->table())
                ->index($index->name(), $index->fields(), $index->type(), $index->table())
                ->build();
        }
    }

    /**
     * Builds table alteration queries
     *
     * @param ModelInterface $model
     *
     * @throws SchemaException
     */
    protected function buildAlter(ModelInterface $model)
    {
        if (!$this->checkIfSchemaExists($model)) {
            throw new SchemaException(sprintf('Unable to alter table, table "%s" does not exists', $model->table()));
        }

        $current = $this->getCurrentSchema($model);

        // foreign keys
        $this->buildForeignKeysAlterations($model, $current);

        $before = array();
        $after = array();
        $queries = array();

        $this->buildColumnsAlterations($model, $current, $queries);
        $this->buildIndexesAlteration($model, $current, $before, $after);

        $queries = array_merge($before, $queries, $after);
        $queries = array_diff($queries, $this->before, $this->queries, $this->after);

        if (empty($queries)) {
            return;
        }

        $this->queries = array_merge($this->queries, array_values($queries));
    }

    /**
     * Builds remove and create queries for foreign keys
     *
     * @param ModelInterface $model
     * @param                $current
     */
    private function buildForeignKeysAlterations(ModelInterface $model, $current)
    {
        foreach ($current['indexes'] as $index) {
            if ($index['type'] === 'foreign') {
                $this->before[] = $this->builder->reset()
                    ->removeFrom($model->table())
                    ->index($index['name'], $index['fields'], $index['type'], $index['table'])
                    ->build();
            }
        }

        foreach ($model->indexes() as $index) {
            if ($index->type() === 'foreign') {
                $this->after[] = $this->builder->reset()
                    ->addTo($model->table())
                    ->index($index->name(), $index->fields(), $index->type(), $index->table())
                    ->build();
            }
        }
    }

    /**
     * Builds column alterations
     *
     * @param ModelInterface $model
     * @param                $current
     * @param array          $queries
     */
    private function buildColumnsAlterations(ModelInterface $model, $current, &$queries = array())
    {
        $fields = $current['fields'];

        foreach ($model->fields() as $field) {
            if (false === $i = $this->findNodeByName($fields, $field->name())) {
                $queries[] = $this->buildColumnOperation('add', $model->table(), $field->name(), $field->type(), $field->attributes());
                continue;
            }

            if ($this->sameField($fields[$i], $field)) {
                unset($fields[$i]);
                continue;
            }

            $queries[] = $this->buildColumnOperation('change', $model->table(), $field->name(), $field->type(), $field->attributes());
            unset($fields[$i]);
        }

        foreach ($fields as $field) {
            $queries[] = $this->buildColumnOperation('remove', $model->table(), $field['name'], $field['type'], $field['attributes']);
        }
    }

    /**
     * Builds actual queries altering columns
     *
     * @param string      $operation
     * @param string      $table
     * @param string      $name
     * @param string      $type
     * @param array       $attributes
     * @param null|string $after
     *
     * @return string
     */
    private function buildColumnOperation($operation, $table, $name, $type, $attributes, $after = null)
    {
        return $this->builder->reset()
            ->operation($operation)
            ->table($table)
            ->column($name, $type, $attributes, $after)
            ->build();
    }

    /**
     * Builds keys/indexes alterations
     *
     * @param ModelInterface $model
     * @param                $current
     * @param array          $before
     * @param array          $after
     */
    private function buildIndexesAlteration(ModelInterface $model, $current, &$before = array(), &$after = array())
    {
        $indexes = $current['indexes'];

        foreach ($model->indexes() as $index) {
            if (false === $i = $this->findNodeByName($indexes, $index->name())) {
                $after[] = $this->buildIndexOperation('add', $model->table(), $index->name(), $index->fields(), $index->type(), $index->table());
                continue;
            }

            if ($this->sameIndex($indexes[$i], $index)) {
                unset($indexes[$i]);
                continue;
            }
        }

        foreach ($indexes as $index) {
            $before[] = $this->buildIndexOperation('remove', $model->table(), $index['name'], $index['fields'], $index['type'], $index['table']);
        }
    }

    /**
     * Builds actual index altering queries
     *
     * @param string      $operation
     * @param string      $table
     * @param string      $name
     * @param array       $fields
     * @param string      $type
     * @param null|string $foreignTable
     *
     * @return string
     */
    private function buildIndexOperation($operation, $table, $name, $fields, $type, $foreignTable = null)
    {
        return $this->builder->reset()
            ->operation($operation)
            ->table($table)
            ->index($name, $fields, $type, $foreignTable)
            ->build();
    }

    /**
     * Returns true if schema exists
     *
     * @param ModelInterface $model
     *
     * @return bool
     */
    protected function checkIfSchemaExists(ModelInterface $model)
    {
        $query = $this->builder->reset()
            ->check($model->table())
            ->build();

        $count = $this->driver->prepare($query)
            ->execute()
            ->affectedRows();

        return $count == 1;
    }

    /**
     * Returns array representing current schema
     *
     * @param ModelInterface $model
     *
     * @return array
     */
    protected function getCurrentSchema(ModelInterface $model)
    {
        $query = $this->builder->reset()
            ->info($model->table())
            ->build();

        $result = $this->driver->prepare($query)
            ->execute()
            ->fetchAll();

        $array = $this->builder->parse($result);

        return $array;
    }

    /**
     * Finds node in array by its name
     *
     * @param $array
     * @param $name
     *
     * @return bool|int|string
     */
    protected function findNodeByName($array, $name)
    {
        foreach ($array as $i => $node) {
            if ($node['name'] == $name) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Returns true if both fields are equal
     *
     * @param array          $old
     * @param FieldInterface $new
     *
     * @return bool
     */
    protected function sameField($old, FieldInterface $new)
    {
        if ($old['type'] !== $new->type()) {
            return false;
        }

        $attributes = array('length' => null, 'precision' => null, 'null' => false, 'auto_increment' => false, 'default' => null);
        foreach ($new->attributes() as $key => $value) {
            $attributes[$key] = $value;
        }

        if (isset($old['attributes']['length']) && !isset($attributes['length'])) {
            $attributes['length'] = $old['attributes']['length'];
        }

        if (isset($old['attributes']['precision']) && !isset($attributes['precision'])) {
            $attributes['precision'] = $old['attributes']['precision'];
        }

        return $attributes == $old['attributes'];
    }

    /**
     * Returns true if both indexes are equal
     *
     * @param array          $old
     * @param IndexInterface $new
     *
     * @return bool
     */
    protected function sameIndex($old, IndexInterface $new)
    {
        if ($old['type'] !== $new->type()) {
            return false;
        }

        return $old['fields'] == $new->fields();
    }

    /**
     * Builds drop table query
     *
     * @param ModelInterface $model
     */
    protected function buildDrop(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            $current = $this->getCurrentSchema($model);
            foreach ($current['indexes'] as $index) {
                if ($index['type'] != 'foreign') {
                    continue;
                }

                $this->before[] = $this->builder->reset()
                    ->removeFrom($model->table())
                    ->index($index['name'], $index['fields'], $index['type'])
                    ->build();
            }
        }

        $this->queries[] = $this->builder->reset()
            ->drop($model->table())
            ->build();
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute()
    {
        $queries = array_merge($this->before, $this->queries, $this->after);

        $result = array();
        switch ($this->operation) {
            case 'check':
                foreach ($queries as $table => $query) {
                    $result[$table] = $this->driver
                            ->prepare($query)
                            ->execute()
                            ->affectedRows() == 1;
                }
                break;
            case 'create':
            case 'alter':
            case 'drop':
                foreach ($queries as $query) {
                    $this->driver
                        ->prepare($query)
                        ->execute();

                    $result[] = $query;
                }
                break;
            default:
                $result = array();
        }

        $this->reset();

        return $result;
    }

    /**
     * Returns current query string
     *
     * @return string
     */
    public function queryString()
    {
        return array_merge($this->before, $this->queries, $this->after);
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;
        $this->before = array();
        $this->queries = array();
        $this->after = array();

        return $this;
    }
}
