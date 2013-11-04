<?php
namespace moss\storage\query;

use moss\storage\builder\QueryBuilderInterface;
use moss\storage\builder\SchemaBuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelInterface;
use moss\storage\model\definition\FieldInterface;
use moss\storage\query\relation\RelationInterface;

/**
 * Entity query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class Query extends Prototype implements EntityQueryInterface
{
    private $fields = array();
    private $aggregates = array();
    private $values = array();
    private $conditions = array();
    private $order = array();
    private $limit = null;
    private $offset = null;

    /** @var RelationInterface[] */
    protected $relations = array();

    public function __construct(DriverInterface $driver, QueryBuilderInterface $builder, ModelInterface $model, $operation, $entity = null)
    {
        $this->driver = & $driver;
        $this->builder = & $builder;
        $this->model = & $model;

        $this->operation($operation, $entity);
    }

    /**
     * Sets query operation
     *
     * @param string $operation
     * @param object $entity
     *
     * @return $this
     * @throws QueryException
     */
    public function operation($operation, $entity = null)
    {
        switch ($operation) {
            case self::OPERATION_COUNT:
            case self::OPERATION_READ_ONE:
            case self::OPERATION_READ:
            case self::OPERATION_CLEAR:
                $this->reset();
                $this->operation = $operation;
                break;
            case self::OPERATION_WRITE:
            case self::OPERATION_INSERT:
            case self::OPERATION_UPDATE:
            case self::OPERATION_DELETE:
                $this->reset();

                if ($entity === null) {
                    throw new QueryException(sprintf('Missing required entity instance for operation "%s" in query "%s"', $operation, $this->model->entity()));
                }

                $entityClass = $this->model->entity();
                if (!$entity instanceof $entityClass) {
                    throw new QueryException(sprintf('Invalid entity instance in "%s" query, expected "%s", got "%s" in query "%s"', $operation, $entityClass, is_object($entity) ? get_class($entity) : gettype($entity), $this->model->entity()));
                }

                $this->operation = $operation;
                $this->instance = $entity;
                $this->reflection = new \ReflectionObject($entity);

                break;
            default:
                throw new QueryException(sprintf(
                    'Unknown operation "%s" in query "%s"',
                    $operation,
                    $this->model->entity()
                ));
        }

        return $this;
    }

    /**
     * Sets fields read by query
     *
     * @param array $fields
     *
     * @return $this
     * @throws QueryException
     */
    public function fields($fields = array())
    {
        $this->fields = array();

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->field($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->field($this->model->field($field));

        }

        return $this;
    }

    protected function field(FieldInterface $field)
    {
        $this->fields[] = array(
            $field->name(),
            $field->name() == $field->mapping() ? null : $field->mapping()
        );

        $this->casts[$field->mapping()] = $field->type();
    }

    /**
     * Adds aggregation method to read results
     *
     * @param string      $method
     * @param string      $field
     * @param null|string $group
     *
     * @return $this
     * @throws QueryException
     */
    public function aggregate($method, $field, $group = null)
    {
        $this->aggregates[] = array(
            $method,
            $this->model
                ->field($field)
                ->mapping(),
            $this->model
                ->field($group ? $group : $field)
                ->mapping()
        );

        return $this;
    }


    /**
     * Adds condition to query
     *
     * @param string|array $field
     * @param string|array $value
     * @param string       $comparisonOperator
     * @param string       $logicalOperator
     *
     * @return $this
     * @throws QueryException
     */
    public function condition($field, $value, $comparisonOperator = QueryBuilderInterface::COMPARISON_EQUAL, $logicalOperator = QueryBuilderInterface::LOGICAL_AND)
    {
        switch ($comparisonOperator) {
            case QueryBuilderInterface::COMPARISON_EQUAL:
            case QueryBuilderInterface::COMPARISON_NOT_EQUAL:
            case QueryBuilderInterface::COMPARISON_LESS:
            case QueryBuilderInterface::COMPARISON_GREATER:
            case QueryBuilderInterface::COMPARISON_LESS_EQUAL:
            case QueryBuilderInterface::COMPARISON_GREATER_EQUAL:
            case QueryBuilderInterface::COMPARISON_LIKE:
            case QueryBuilderInterface::COMPARISON_REGEX:
                break;
            default:
                throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $comparisonOperator, $this->model->entity()));
        }

        switch ($logicalOperator) {
            case QueryBuilderInterface::LOGICAL_AND:
            case QueryBuilderInterface::LOGICAL_OR:
                break;
            default:
                throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $logicalOperator, $this->model->entity()));
        }

        $fields = array();
        $values = array();

        if (!is_array($field) && is_array($value)) {
            for ($i = 0, $l = count($value); $i < $l; $i++) {
                $fields[] = $this->model
                    ->field($field)
                    ->mapping();
                $values[] = $this->bind('condition', $field, $value[$i]);
            }
        } else {
            foreach ((array) $field as $i => $f) {
                $fields[] = $this->model
                    ->field($f)
                    ->mapping();
                $values[] = $this->bind('condition', $f, is_array($value) ? $value[$i] : $value);
            }
        }

        $this->conditions[] = array(
            $fields,
            $values,
            $comparisonOperator,
            $logicalOperator
        );

        return $this;
    }

    /**
     * Adds order method to query
     *
     * @param string $field
     * @param string $order
     *
     * @return $this
     * @throws QueryException
     */
    public function order($field, $order = QueryBuilderInterface::ORDER_ASC)
    {
        if (!is_array($order) && $order != QueryBuilderInterface::ORDER_ASC && $order != QueryBuilderInterface::ORDER_DESC) {
            throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', is_scalar($order) ? $order : gettype($order), $this->model->entity()));
        }

        if (is_array($order)) {
            foreach ($order as $i => &$o) {
                $order[$i] = $this->bind('order', $field, (string) $o);
            }
        }

        $this->order[] = array(
            $this->model
                ->field($field)
                ->mapping(),
            $order
        );

        return $this;
    }

    /**
     * Sets limits to results
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     * @throws QueryException
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = (int) $limit;
        $this->offset = $offset ? (int) $offset : null;

        return $this;
    }

    /**
     * Adds relation to query
     *
     * @param RelationInterface $relation
     *
     * @return $this
     */
    public function setRelation(RelationInterface $relation)
    {
        $this->relations[$relation->name()] = $relation;

        return $this;
    }

    /**
     * Returns relation in query
     *
     * @param string $relationName
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function getRelation($relationName)
    {
        if (!isset($this->relations[$relationName])) {
            throw new QueryException(sprintf('Relation "%s" not found in query "%s"', $relationName, $this->model->entity()));
        }

        return $this->relations[$relationName];
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
        if ($this->operation == self::OPERATION_WRITE) {
            $query = new self($this->driver, $this->builder, $this->model, self::OPERATION_COUNT);

            foreach ($this->model->primaryFields() as $field) {
                $query->condition($field, $this->accessProperty($this->instance, $field));
            }

            $operation = $query->execute() > 0 ? self::OPERATION_UPDATE : self::OPERATION_INSERT;
            $this->operation($operation, $this->instance);
        }

        switch ($this->operation) {
            case self::OPERATION_COUNT:
                $result = $this->executeCount();
                break;
            case self::OPERATION_READ:
            case self::OPERATION_READ_ONE:
                $result = $this->executeRead();
                break;
            case self::OPERATION_INSERT:
                $result = $this->executeInsert();
                break;
            case self::OPERATION_UPDATE:
                $result = $this->executeUpdate();

                break;
            case self::OPERATION_DELETE:
                $result = $this->executeDelete();

                break;
            case self::OPERATION_CLEAR:
                $result = $this->executeClear();
                break;
            default:
                $result = false;
        }

        $this->reset();

        return $result;
    }

    /**
     * Executes counting query
     *
     * @return int
     */
    protected function executeCount()
    {
        $this->builder
            ->reset()
            ->operation(QueryBuilderInterface::OPERATION_SELECT)
            ->container($this->model->container());

        foreach ($this->model->primaryFields() as $node) {
            $node = $this->model->field($node);
            $this->builder->field($node->mapping());
        }

        foreach ($this->conditions as $node) {
            $this->builder->condition($node[0], $node[1], $node[2], $node[3]);
        }

        $query = $this->builder->build();

        return $this->driver
            ->prepare($query)
            ->execute($this->binds)
            ->affectedRows();
    }

    /**
     * Executes container creating or altering query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeRead()
    {
        $this->builder
            ->reset()
            ->operation(QueryBuilderInterface::OPERATION_SELECT)
            ->container($this->model->container());

        if (empty($this->fields)) {
            $this->fields();
        }

        foreach ($this->fields as $node) {
            $this->builder->field($node[0], $node[1]);
        }

        foreach ($this->aggregates as $node) {
            $this->builder
                ->aggregate($node[0], $node[1])
                ->group($node[2]);
        }

        foreach ($this->conditions as $node) {
            $this->builder->condition($node[0], $node[1], $node[2], $node[3]);
        }

        foreach ($this->order as $node) {
            $this->builder->order($node[0], $node[1]);
        }

        if ($this->operation == self::OPERATION_READ_ONE) {
            $this->builder->limit(1);
        } elseif ($this->limit) {
            $this->builder->limit($this->limit, $this->offset);
        }

        $query = $this->builder->build();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        $result = $this->driver->fetchAll($this->model->entity(), $this->casts);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }

        if ($this->operation !== self::OPERATION_READ_ONE) {
            return $result;
        }

        if (!count($result)) {
            throw new QueryException(sprintf('Result out of range or does not exists for "%s"', $this->model->entity()));
        }

        return array_shift($result);
    }

    /**
     * Executes inserting query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeInsert()
    {
        $this->builder
            ->reset()
            ->operation(QueryBuilderInterface::OPERATION_INSERT)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            $value = $this->accessProperty($this->instance, $field->name());

            if ($value === null && $field->attribute(ModelInterface::ATTRIBUTE_AUTO)) {
                continue;
            }

            $this->assertValue($field, $value);

            $this->builder->value($field->mapping(), $this->bind('value', $field->name(), $value));
        }

        $query = $this->builder->build();

        $result = $this->driver
            ->prepare($query)
            ->execute($this->binds)
            ->lastInsertId();

        $this->identifyEntity($result);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    /**
     * Executes updating query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeUpdate()
    {
        $this->builder
            ->reset()
            ->operation(QueryBuilderInterface::OPERATION_SELECT)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            $value = $this->accessProperty($this->instance, $field->name());
            $value = $this->bind('value', $field->name(), $value);

            $this->assertValue($field, $value);

            $this->builder->value($field->mapping(), $this->bind('value', $field->name(), $value));

            if ($this->model->isPrimary($field->name())) {
                $this->builder->condition($field->mapping(), $this->bind('condition', $field->name(), $value), QueryBuilderInterface::COMPARISON_EQUAL, QueryBuilderInterface::LOGICAL_AND);
            }
        }

        $query = $this->builder->build();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    /**
     * Checks if value matches field definition
     *
     * @param FieldInterface $field
     * @param mixed          $value
     *
     * @throws QueryException
     */
    protected function assertValue($field, $value)
    {
        if ($value === null && !$field->attribute(SchemaBuilderInterface::ATTRIBUTE_NULL)) {
            throw new QueryException(sprintf('Missing required field value "%s" for entity "%s"', $field->name(), $this->model->entity()));
        }

        // TODO - add silent mode?

        // check value length (with distinction for precision)
        if ($limit = $field->attribute('length')) {
            switch ($field->type()) {
                case SchemaBuilderInterface::FIELD_DECIMAL:
                    $length = strlen(preg_replace('/[^0-9.]+/im', '', $value));

                    if ($length > $length) {
                        throw new QueryException(sprintf('Value length (%u) exceeds limit (%u) in field "%s" for entity "%s"', $length, $limit, $field->name(), $this->model->entity()));
                    }

                    if ($precLimit = $field->attribute('precision') && false !== $pos = strpos('.', $value)) {
                        $prec = substr($value, strpos('.', $value) + 1);
                        throw new QueryException(sprintf('Value precision (%u) exceeds limit (%u) in field "%s" for entity "%s"', $prec, $precLimit, $field->name(), $this->model->entity()));
                    }
                    break;
                default:
                    $length = strlen($value);
                    if ($length > $limit) {
                        throw new QueryException(sprintf('Value length (%u) exceeds limit (%u) in field "%s" for entity "%s"', $length, $limit, $field->name(), $this->model->entity()));
                    }
            }
        }

        // check if value is positive for unsigned fields
        if ($field->attribute('unsigned') && ($field->type() === SchemaBuilderInterface::FIELD_INTEGER || $field->type() === SchemaBuilderInterface::FIELD_DECIMAL) && $value < 0) {
            throw new QueryException(sprintf('Negative value (%s) in unsigned field "%s" for entity "%s"', $value, $field->name(), $this->model->entity()));
        }
    }

    /**
     * Executes deleting query
     *
     * @return mixed
     */
    protected function executeDelete()
    {
        foreach ($this->relations as $relation) {
            $relation->delete($this->instance);
        }

        $this->builder
            ->reset()
            ->operation(QueryBuilderInterface::OPERATION_SELECT)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            if (!$this->model->isPrimary($field->name())) {
                continue;
            }

            $value = $this->accessProperty($this->instance, $field->name());
            $this->builder->condition($field->mapping(), $this->bind('condition', $field->name(), $value), QueryBuilderInterface::COMPARISON_EQUAL, QueryBuilderInterface::LOGICAL_AND);
        }

        $query = $this->builder->build();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        $this->identifyEntity(null);

        return $this->instance;
    }

    /**
     * Executes clearing query
     *
     * @return mixed
     */
    protected function executeClear()
    {
        foreach ($this->relations as $relation) {
            $relation->clear();
        }

        $query = $this->builder
            ->reset()
            ->operation(QueryBuilderInterface::OPERATION_CLEAR)
            ->container($this->model->container())
            ->build();

        $this->driver
            ->prepare($query)
            ->execute();

        return true;
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;

        $this->fields = array();
        $this->aggregates = array();
        $this->values = array();
        $this->conditions = array();
        $this->order = array();
        $this->limit = null;
        $this->offset = null;

        $this->binds = array();
        $this->casts = array();

        $this->driver->reset();

        return $this;
    }
}
