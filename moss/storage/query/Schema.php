<?php
namespace moss\storage\query;

use moss\storage\builder\SchemaInterface as BuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelBag;
use moss\storage\model\ModelInterface;

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
     * Sets query operation
     *
     * @param string        $operation
     * @param string|object $entity
     *
     * @return $this
     * @throws QueryException
     */
    public function operation($operation, $entity = null)
    {
        $this->operation = $operation;

        $models = $entity === null ? $this->models->all() : array($this->models->get($entity));

        switch ($this->operation) {
            case self::OPERATION_CHECK:
                foreach ($models as $model) {
                    $this->buildCheck($model);
                }
                break;
            case self::OPERATION_CREATE:
                foreach ($models as $model) {
                    $this->buildCreate($model);
                }
                break;
            case self::OPERATION_ALTER:
                foreach ($models as $model) {
                    $this->buildAlter($model);
                }
                break;
            case self::OPERATION_DROP:
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
        $this->queries[$model->container()] = $this->builder->reset()
                                                            ->operation(BuilderInterface::OPERATION_CHECK)
                                                            ->container($model->container())
                                                            ->build();
    }

    protected function buildCreate(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            throw new QueryException(sprintf('Unable to create container, container "%s" already exists', $model->container()));
        }

        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_CREATE)
                      ->container($model->container());

        foreach ($model->fields() as $node) {
            $this->builder->column($node->name(), $node->type(), $node->attributes());
        }

        $foreign = array();
        foreach ($model->indexes() as $node) {
            if ($node->type() === BuilderInterface::INDEX_FOREIGN) {
                $foreign[] = $node;
                continue;
            }

            $this->builder->index($node->name(), $node->fields(), $node->type(), $node->container());
        }

        $this->queries[] = $this->builder->build();

        foreach ($foreign as $node) {
            $this->after[] = $this->buildIndexAdd($model, $node->name(), $node->fields(), $node->type(), $node->container());
        }
    }

    protected function buildAlter(ModelInterface $model)
    {
        if (!$this->checkIfSchemaExists($model)) {
            throw new QueryException(sprintf('Unable to alter container, container "%s" does not exists', $model->container()));
        }

        $current = $this->getCurrentSchema($model);

        // removing foreign keys
        foreach ($current['indexes'] as $index) {
            if ($index['type'] !== BuilderInterface::INDEX_FOREIGN) {
                continue;
            }
            $this->before[] = $this->buildIndexRemove($model, $index['name'], $index['fields'], $index['type']);
        }

        // removing auto increment
        foreach ($current['fields'] as $columns) {
            if (!isset($columns['attributes'][BuilderInterface::ATTRIBUTE_AUTO])) {
                continue;
            }
            unset($columns['attributes'][BuilderInterface::ATTRIBUTE_AUTO]);
            $this->before[] = $this->buildColumnChange($model, $columns['name'], $columns['type'], $columns['attributes']);
        }

        // removing primary keys and indexes
        foreach ($current['indexes'] as $index) {
            if ($index['type'] == BuilderInterface::INDEX_FOREIGN) {
                continue;
            }

            $this->before[] = $this->buildIndexRemove($model, $index['name'], $index['fields'], $index['type']);
        }

        // updating columns
        $aiColumn = null;
        $prev = null;
        foreach ($model->fields() as $column) {
            $attributes = $column->attributes();
            if (isset($attributes[BuilderInterface::ATTRIBUTE_AUTO])) {
                $aiColumn = $column;
                unset($attributes[BuilderInterface::ATTRIBUTE_AUTO]);
            }

            if (false !== $i = $this->findField($current['fields'], $column->mapping())) {
                $this->queries[] = $this->buildColumnChange($model, $column->mapping(), $column->type(), $attributes, $column->name());
                unset($current['fields'][$i]);
            } else {
                $this->queries[] = $this->buildColumnAdd($model, $column->mapping(), $column->type(), $attributes, $column->name(), $prev);
            }

            $prev = $column->mapping();
        }

        foreach ($current['fields'] as $column) {
            $this->queries[] = $this->buildColumnRemove($model, $column['name'], $column['type'], $column['attributes']);
        }

        // applying indexes
        foreach ($model->indexes() as $index) {
            if ($index->type() == BuilderInterface::INDEX_FOREIGN) {
                $this->after[] = $this->buildIndexAdd($model, $index->name(), $index->fields(), $index->type(), $index->container());
                continue;
            }

            $this->queries[] = $this->buildIndexAdd($model, $index->name(), $index->fields(), $index->type(), $index->container());
        }

        // applying auto increment
        if ($aiColumn) {
            $this->queries[] = $this->buildColumnChange($model, $aiColumn->name(), $aiColumn->type(), $aiColumn->attributes(), $aiColumn->name());
        }
    }

    protected function checkIfSchemaExists(ModelInterface $model)
    {
        $query = $this->builder->reset()
                               ->operation(BuilderInterface::OPERATION_CHECK)
                               ->container($model->container())
                               ->build();

        $count = $this->driver->prepare($query)
                              ->execute()
                              ->affectedRows();

        return $count == 1;
    }

    protected function getCurrentSchema(ModelInterface $model)
    {
        $query = $this->builder->reset()
                               ->operation(BuilderInterface::OPERATION_INFO)
                               ->container($model->container())
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

    protected function buildDrop(ModelInterface $model)
    {
        if ($this->checkIfSchemaExists($model)) {
            $current = $this->getCurrentSchema($model);
            foreach ($current['indexes'] as $index) {
                if ($index['type'] != BuilderInterface::INDEX_FOREIGN) {
                    continue;
                }

                // removing foreign keys
                $this->before[] = $this->buildIndexRemove($model, $index['name'], $index['fields'], $index['type']);
            }
        }

        $this->queries[] = $this->builder->reset()
                                         ->operation(BuilderInterface::OPERATION_DROP)
                                         ->container($model->container())
                                         ->build();
    }

    protected function buildIndexAdd(ModelInterface $model, $name, $fields, $type, $container = null)
    {
        return $this->builder->reset()
                             ->container($model->container())
                             ->operation(BuilderInterface::OPERATION_ADD)
                             ->index($name, $fields, $type, $container)
                             ->build();
    }

    protected function buildIndexRemove(ModelInterface $model, $name, $fields, $type, $container = null)
    {
        return $this->builder->reset()
                             ->container($model->container())
                             ->operation(BuilderInterface::OPERATION_REMOVE)
                             ->index($name, $fields, $type, $container)
                             ->build();

    }

    protected function buildColumnAdd(ModelInterface $model, $name, $type, $attributes, $prev = null)
    {
        return $this->builder->reset()
                             ->container($model->container())
                             ->operation(BuilderInterface::OPERATION_ADD)
                             ->column($name, $type, $attributes, $prev)
                             ->build();

    }

    protected function buildColumnChange(ModelInterface $model, $name, $type, $attributes, $prev = null)
    {
        return $this->builder->reset()
                             ->container($model->container())
                             ->operation(BuilderInterface::OPERATION_CHANGE)
                             ->column($name, $type, $attributes, $prev)
                             ->build();
    }

    protected function buildColumnRemove(ModelInterface $model, $name, $type, $attributes)
    {
        return $this->builder->reset()
                             ->container($model->container())
                             ->operation(BuilderInterface::OPERATION_REMOVE)
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
            case self::OPERATION_CHECK:
                foreach ($queries as $container => $query) {
                    $result[$container] = $this->driver
                            ->prepare($query)
                            ->execute()
                            ->affectedRows() == 1;
                }
                break;
            case self::OPERATION_CREATE:
            case self::OPERATION_ALTER:
            case self::OPERATION_DROP:
                foreach ($queries as $query) {
                    $this->driver
                        ->prepare($query)
                        ->execute();

                    $result[$query] = true;
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