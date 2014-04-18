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

    protected function buildCheck(ModelInterface $model)
    {
        $this->queries[$model->table()] = $this->builder->reset()
            ->check($model->table())
            ->build();
    }

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
            ->add($model->table());

        foreach ($foreign as $index) {
            $this->after[] = $this->builder->reset()
                ->add($model->table())
                ->index($index->name(), $index->fields(), $index->type(), $index->table())
                ->build();
        }
    }

    protected function buildAlter(ModelInterface $model)
    {
        if (!$this->checkIfSchemaExists($model)) {
            throw new SchemaException(sprintf('Unable to alter table, table "%s" does not exists', $model->table()));
        }

        $current = $this->getCurrentSchema($model);

        // foreign keys
        foreach ($current['indexes'] as $index) {
            if ($index['type'] === 'foreign') {

                $this->before[] = $this->builder->reset()
                    ->remove($model->table())
                    ->index($index['name'], $index['fields'], $index['type'], $index['table'])
                    ->build();
            }
        }

        foreach ($model->indexes() as $index) {
            if ($index->type() === 'foreign') {

                $this->after[] = $this->builder->reset()
                    ->add($model->table())
                    ->index($index->name(), $index->fields(), $index->type(), $index->table())
                    ->build();
            }
        }

        $before = array();
        $after = array();
        $queries = array();

        // columns
        $fields = $current['fields'];
        foreach ($model->fields() as $field) {
            if (false === $i = $this->findNodeByName($fields, $field->name())) {
                $queries['FLD+' . $field->name()] = $this->builder->reset()
                    ->add($model->table())
                    ->column($field->name(), $field->type(), $field->attributes())
                    ->build();
                continue;
            }

            if ($this->sameField($fields[$i], $field)) {
                unset($fields[$i]);
                continue;
            }

            $queries['FLD*' . $field->name()] = $this->builder->reset()
                ->change($model->table())
                ->column($field->name(), $field->type(), $field->attributes())
                ->build();
            unset($fields[$i]);
        }

        foreach ($fields as $field) {
            $queries['FLD-' . $field['name']] = $this->builder->reset()
                ->remove($model->table())
                ->column($field['name'], $field['type'], $field['attributes'])
                ->build();
        }

        // indexes
        $indexes = $current['indexes'];
        foreach ($model->indexes() as $index) {
            if (false === $i = $this->findNodeByName($indexes, $index->name())) {
                $after['IDX+' . $index->name()] = $this->builder->reset()
                    ->add($model->table())
                    ->index($index->name(), $index->fields(), $index->type(), $index->table())
                    ->build();
                continue;
            }

            if ($this->sameIndex($indexes[$i], $index)) {
                unset($indexes[$i]);
                continue;
            }
        }

        foreach ($indexes as $index) {
            $before['IDX-' . $index['name']] = $this->builder->reset()
                ->remove($model->table())
                ->index($index['name'], $index['fields'], $index['type'], $index['table'])
                ->build();
        }

        $queries = array_merge($before, $queries, $after);
        $queries = array_diff($queries, $this->before, $this->queries, $this->after);

        if (empty($queries)) {
            return;
        }

        $this->queries = array_merge($this->queries, array_values($queries));
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
            ->fetchAll();

        $array = $this->builder->parse($result);

        return $array;
    }

    protected function findNodeByName($array, $name)
    {
        foreach ($array as $i => $node) {
            if ($node['name'] == $name) {
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

    protected function sameIndex($old, IndexInterface $new)
    {
        if ($old['type'] !== $new->type()) {
            return false;
        }

        return $old['fields'] == $new->fields();
    }

    protected function buildDrop(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            $current = $this->getCurrentSchema($model);
            foreach ($current['indexes'] as $index) {
                if ($index['type'] != 'foreign') {
                    continue;
                }

                $this->before[] = $this->builder->reset()
                    ->remove($model->table())
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