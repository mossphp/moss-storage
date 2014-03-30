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

use Moss\Storage\Builder\SchemaBuilderInterface as BuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;

/**
 * Schema used to create and execute table related operations (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Query
 */
class Schema implements SchemaInterface
{

    /** @var DriverInterface */
    protected $driver;

    /** @var BuilderInterface */
    protected $builder;

    /** @var ModelBag */
    protected $models;

    private $operation;

    private $before = array();
    private $queries = array();
    private $after = array();

    public function __construct(DriverInterface $driver, BuilderInterface $builder, ModelBag $models)
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
     * @return BuilderInterface
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
     * @throws QueryException
     */
    public function operation($operation, array $entity = array())
    {
        $this->operation = $operation;

        $models = array();
        foreach ($entity as $node) {
            $models[] = $this->models->get($node);
        }

        if(empty($models)) {
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
                throw new QueryException(sprintf('Unknown operation "%s" in schema query', $this->operation));
        }

        return $this;
    }

    protected function buildCheck(ModelInterface $model)
    {
        $this->queries[$model->table()] = $this->builder->reset()
            ->check($model->table())
            ->build();
    }

    protected function buildCreate(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            throw new QueryException(sprintf('Unable to create table, table "%s" already exists', $model->table()));
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

        foreach ($foreign as $index) {
            $this->after[] = $this->buildIndexAdd($model, $index->name(), $index->fields(), $index->type(), $index->table());
        }
    }

    protected function buildAlter(ModelInterface $model)
    {
        if (!$this->checkIfSchemaExists($model)) {
            throw new QueryException(sprintf('Unable to alter table, table "%s" does not exists', $model->table()));
        }

        $current = $this->getCurrentSchema($model);
        // todo - optimize, remove unnecessary index operations (add, remove) and column alterations
        // removing foreign keys
        foreach ($current['indexes'] as $index) {
            if ($index['type'] !== 'foreign') {
                continue;
            }
            $this->before[] = $this->buildIndexRemove($model, $index['name'], $index['fields'], $index['type']);
        }

        // applying indexes
        foreach ($model->indexes() as $index) {
            if ($index->type() !== 'foreign') {
                continue;
            }

            $this->after[] = $this->buildIndexAdd($model, $index->name(), $index->fields(), $index->type(), $index->table());
        }


        // removing auto increment
        foreach ($current['fields'] as $columns) {
            if (!isset($columns['attributes']['auto_increment'])) {
                continue;
            }
            unset($columns['attributes']['auto_increment']);
            $this->queries[] = $this->buildColumnChange($model, $columns['name'], $columns['type'], $columns['attributes']);
        }

        // removing primary keys and indexes
        foreach ($current['indexes'] as $index) {
            if ($index['type'] == 'foreign') {
                continue;
            }

            $this->queries[] = $this->buildIndexRemove($model, $index['name'], $index['fields'], $index['type']);
        }

        // updating columns
        $prev = null;
        foreach ($model->fields() as $column) {
            $attributes = $column->attributes();
            if (isset($attributes['auto_increment'])) {
                unset($attributes['auto_increment']);
            }

            $i = $this->findField($current['fields'], $column->mapping());
            if ($i !== false) {
                if (!$this->sameField($current['fields'][$i], $column)) {
                    $this->queries[] = $this->buildColumnChange($model, $column->mapping(), $column->type(), $attributes);
                }
                unset($current['fields'][$i]);
            }

            if ($i === false) {
                $this->queries[] = $this->buildColumnAdd($model, $column->mapping(), $column->type(), $attributes, $prev);
            }

            $prev = $column->mapping();
        }

        foreach ($current['fields'] as $column) {
            $this->queries[] = $this->buildColumnRemove($model, $column['name'], $column['type'], $column['attributes']);
        }

        // applying indexes
        foreach ($model->indexes() as $index) {
            if ($index->type() === 'foreign') {
                continue;
            }

            $this->queries[] = $this->buildIndexAdd($model, $index->name(), $index->fields(), $index->type(), $index->table());
        }

        // applying auto increment
        foreach ($model->fields() as $column) {
            if ($column->attribute('auto_increment')) {
                $this->queries[] = $this->buildColumnChange($model, $column->name(), $column->type(), $column->attributes());
            }
        }
    }

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

    protected function getCurrentSchema(ModelInterface $model)
    {
        $query = $this->builder->reset()
            ->info($model->table())
            ->build();

        $result = $this->driver->prepare($query)
            ->execute()
            ->fetchField(1);

        $array = $this->builder->parse($result);

        return $array;
    }

    protected function findField($fields, $name)
    {
        foreach ($fields as $i => $field) {
            if ($field['name'] == $name) {
                return $i;
            }
        }

        return false;
    }

    protected function sameField($old, FieldInterface $new)
    {
        if ($old['type'] !== $new->type()) {
            return false;
        }

        $attributes = $new->attributes();

        if (isset($old['attributes']['length']) && !isset($attributes['length'])) {
            $attributes['length'] = $old['attributes']['length'];
        }

        if (isset($old['attributes']['precision']) && !isset($attributes['precision'])) {
            $attributes['precision'] = $old['attributes']['precision'];
        }

        if (in_array($new->type(), array('boolean', 'serial')) && !isset($attributes['comment'])) {
            $attributes['comment'] = $old['attributes']['comment'];
        }

        return array_diff($attributes, $old['attributes']) === array();
    }

    protected function buildDrop(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            $current = $this->getCurrentSchema($model);
            foreach ($current['indexes'] as $index) {
                if ($index['type'] != 'foreign') {
                    continue;
                }

                // removing foreign keys
                $this->before[] = $this->buildIndexRemove($model, $index['name'], $index['fields'], $index['type']);
            }
        }

        $this->queries[] = $this->builder->reset()
            ->drop($model->table())
            ->build();
    }

    protected function buildIndexAdd(ModelInterface $model, $name, $fields, $type, $table = null)
    {
        return $this->builder->reset()
            ->add($model->table())
            ->index($name, $fields, $type, $table)
            ->build();
    }

    protected function buildIndexRemove(ModelInterface $model, $name, $fields, $type, $table = null)
    {
        return $this->builder->reset()
            ->remove($model->table())
            ->index($name, $fields, $type, $table)
            ->build();

    }

    protected function buildColumnAdd(ModelInterface $model, $name, $type, $attributes, $prev = null)
    {
        return $this->builder->reset()
            ->add($model->table())
            ->column($name, $type, $attributes, $prev)
            ->build();

    }

    protected function buildColumnChange(ModelInterface $model, $name, $type, $attributes, $prev = null)
    {
        return $this->builder->reset()
            ->change($model->table())
            ->column($name, $type, $attributes, $prev)
            ->build();
    }

    protected function buildColumnRemove(ModelInterface $model, $name, $type, $attributes)
    {
        return $this->builder->reset()
            ->remove($model->table())
            ->column($name, $type, $attributes)
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