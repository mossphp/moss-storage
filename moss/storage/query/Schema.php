<?php
namespace moss\storage\query;

use moss\storage\builder\SchemaBuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelInterface;

/**
 * Entity query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class Schema extends Prototype implements SchemaQueryInterface
{
    private $columns = array();
    private $indexes = array();

    public function __construct(DriverInterface $driver, SchemaBuilderInterface $builder, ModelInterface $model, $operation)
    {
        $this->driver = & $driver;
        $this->builder = & $builder;
        $this->model = & $model;

        $this->operation($operation);
    }

    /**
     * Sets query operation
     *
     * @param string $operation
     *
     * @return $this
     * @throws QueryException
     */
    public function operation($operation)
    {
        if (!in_array($operation, array(self::OPERATION_CHECK, self::OPERATION_INFO, self::OPERATION_CREATE, self::OPERATION_ALTER, self::OPERATION_DROP))) {
            throw new QueryException(sprintf('Unknown operation "%s" in schema query "%s"', $operation, $this->model->entity()));
        }

        $this->operation = $operation;

        return $this;
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     * @throws QueryException
     */
    public function execute()
    {
        switch ($this->operation) {
            case self::OPERATION_CHECK:
                $result = $this->executeCheck();
                break;
            case self::OPERATION_INFO:
                $result = $this->executeInfo();
                break;
            case self::OPERATION_CREATE:
                $result = $this->executeCreate();
                break;
            case self::OPERATION_ALTER:
                $result = $this->executeAlter();
                break;
            case self::OPERATION_DROP:
                $result = $this->executeDrop();
                break;
            default:
                $result = false;
        }

        $this->reset();

        return $result;
    }

    /**
     * Executes checking query
     */
    protected function executeCheck()
    {
        $query = $this->builder
            ->reset()
            ->operation(SchemaBuilderInterface::OPERATION_CHECK)
            ->container($this->model->container())
            ->build();

        return (bool) $this->driver
            ->prepare($query)
            ->execute()
            ->affectedRows();
    }

    /**
     * Executes checking query
     *
     * @return array
     */
    protected function executeInfo()
    {
        $result = array(
            'columns' => array(),
            'indexes' => array(),
        );

        $query = $this->builder
            ->reset()
            ->operation(SchemaBuilderInterface::OPERATION_INFO)
            ->container($this->model->container())
            ->mode(SchemaBuilderInterface::INFO_COLUMNS)
            ->build();

        $columns = $this->driver
            ->prepare($query, 'columns info')
            ->execute()
            ->fetchAll();

        $result['columns'] = $this->builder->parseColumns($columns);

        $query = $this->builder
            ->reset()
            ->operation(SchemaBuilderInterface::OPERATION_INFO)
            ->container($this->model->container())
            ->mode(SchemaBuilderInterface::INFO_INDEXES)
            ->build();

        $indexes = $this->driver
            ->prepare($query, 'indexes info')
            ->execute()
            ->fetchAll();

        $result['indexes'] = $this->builder->parseIndexes($indexes);

        return $result;
    }

    /**
     * Executes checking query
     *
     * @return bool
     */
    protected function executeCreate()
    {
        $this->builder
            ->reset()
            ->operation(SchemaBuilderInterface::OPERATION_CREATE)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            $this->builder->addColumn($field->name(), $field->type(), $field->attributes());
        }

        foreach ($this->model->indexes() as $index) {
            $this->builder->addIndex($index->name(), $index->fields(), $index->type());
        }

        $query = $this->builder->build();

        return (bool) $this->driver
            ->prepare($query)
            ->execute();
    }

    /**
     * Executes altering query
     */
    protected function executeAlter()
    {
        $current = $this->executeInfo();

        $this->builder
            ->reset()
            ->operation(SchemaBuilderInterface::OPERATION_ALTER)
            ->container($this->model->container());

        // remove current indexes
        foreach ($current['indexes'] as $index) {
            $this->builder->dropIndex($index['name'], $index['type'] === SchemaBuilderInterface::INDEX_PRIMARY);
        }

        // build columns
        $ai = null;
        $prev = null;
        foreach ($this->model->fields() as $column) {
            $attributes = $column->attributes(); // for easy override in case of auto increment
            if ($column->attribute(SchemaBuilderInterface::ATTRIBUTE_AUTO)) {
                $ai = $column;
                $attributes = array();
            }

            if (isset($current['columns'][$column->mapping()])) {
                $this->builder->alterColumn($column->mapping(), $column->type(), $attributes, $column->name());
                unset($current['columns'][$column->mapping()]);
            } else {
                $this->builder->addColumn($column->mapping(), $column->type(), $attributes, $prev);
            }

            $prev = $column->mapping();
        }

        // remove obsolete columns
        foreach ($current['columns'] as $column) {
            $this->builder->dropColumn($column['name']);
        }

        // build indexes
        foreach ($this->model->indexes() as $index) {
            $this->builder->addIndex($index->name(), $index->fields(), $index->type());
        }

        $query = $this->builder->build();
        $this->driver
            ->prepare($query, 'altering table without auto increments')
            ->execute();

        // reapply auto increment
        if ($ai) {
            $query = $this->builder
                ->reset()
                ->operation(SchemaBuilderInterface::OPERATION_ALTER)
                ->container($this->model->container())
                ->alterColumn($ai->name(), $ai->type(), $ai->attributes(), $ai->name())
                ->build();

            $this->driver
                ->prepare($query, 'reapplying auto increment')
                ->execute();
        }

        return true;
    }

    /**
     * Executes dropping query
     *
     * @return bool
     */
    protected function executeDrop()
    {
        $query = $this->builder
            ->reset()
            ->operation(SchemaBuilderInterface::OPERATION_DROP)
            ->container($this->model->container())
            ->build();

        return (bool) $this->driver
            ->prepare($query)
            ->execute();
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;

        $this->columns = array();
        $this->indexes = array();

        $this->binds = array();
        $this->casts = array();

        $this->driver->reset();

        return $this;
    }
}
