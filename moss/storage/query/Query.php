<?php
namespace moss\storage\query;

use moss\storage\builder\QueryInterface as BuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\definition\FieldInterface;
use moss\storage\model\ModelInterface;
use moss\storage\query\relation\Many;
use moss\storage\query\relation\One;
use moss\storage\query\relation\RelationInterface;

/**
 * Entity query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class Query implements QueryInterface
{
    /** @var DriverInterface */
    protected $driver;

    /** @var BuilderInterface */
    protected $builder;

    /** @var ModelInterface */
    protected $model;

    private $operation;

    private $fields = array();
    private $aggregates = array();
    private $values = array();
    private $conditions = array();
    private $order = array();
    private $limit = null;
    private $offset = null;
    private $join = null;

    private $binds = array();
    private $casts = array();

    /** @var RelationInterface[] */
    private $relations = array();

    private $instance;

    /** @var \ReflectionObject */
    private $reflection;

    public function __construct(DriverInterface $driver, BuilderInterface $builder, ModelInterface $model)
    {
        $this->driver = & $driver;
        $this->builder = & $builder;
        $this->model = & $model;
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
        if (in_array($operation, array(self::OPERATION_COUNT, self::OPERATION_READ_ONE, self::OPERATION_READ, self::OPERATION_CLEAR))) {
            $this->operation = $operation;

            return $this;
        }

        if (in_array($operation, array(self::OPERATION_WRITE, self::OPERATION_INSERT, self::OPERATION_UPDATE, self::OPERATION_DELETE))) {
            if ($entity === null) {
                throw new QueryException(sprintf('Missing required entity instance for operation "%s" in query "%s"', $operation, $this->model->entity()));
            }

            $entityClass = $this->model->entity();
            if (!$entity instanceof $entityClass) {
                throw new QueryException(sprintf('Invalid entity instance for operation "%s", expected "%s", got "%s" in query "%s"', $operation, $entityClass, is_object($entity) ? get_class($entity) : gettype($entity), $this->model->entity()));
            }

            $this->operation = $operation;
            $this->instance = & $entity;
            $this->reflection = new \ReflectionObject($entity);

            return $this;
        }

        throw new QueryException(sprintf('Unknown operation "%s" in query "%s"', $operation, $this->model->entity()));
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

        // todo - check if model has field
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
        // todo - check if model has field
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

    public function join()
    {

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
    public function condition($field, $value, $comparisonOperator = BuilderInterface::COMPARISON_EQUAL, $logicalOperator = BuilderInterface::LOGICAL_AND)
    {
        $comparisonOperators = array(
            BuilderInterface::COMPARISON_EQUAL,
            BuilderInterface::COMPARISON_NOT_EQUAL,
            BuilderInterface::COMPARISON_LESS,
            BuilderInterface::COMPARISON_GREATER,
            BuilderInterface::COMPARISON_LESS_EQUAL,
            BuilderInterface::COMPARISON_GREATER_EQUAL,
            BuilderInterface::COMPARISON_LIKE,
            BuilderInterface::COMPARISON_REGEX
        );
        if (!in_array($comparisonOperator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $comparisonOperator, $this->model->entity()));
        }

        $logicalOperators = array(
            BuilderInterface::LOGICAL_AND,
            BuilderInterface::LOGICAL_OR
        );
        if (!in_array($logicalOperator, $logicalOperators)) {
            throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $logicalOperator, $this->model->entity()));
        }

        // todo - check if model has fields or aggregation method has been defined

        $fields = array();
        $values = array();

        if (!is_array($field) && is_array($value)) {
            for ($i = 0, $l = count($value); $i < $l; $i++) {
                $fields[] = $this->model
                    ->field($field)
                    ->mapping();

                $values[] = !array_key_exists($i, $value) || $value[$i] === null ? null : $this->bind('condition', $field, $value[$i]);
            }
        } else {
            foreach ((array) $field as $i => $f) {
                $fields[] = $this->model
                    ->field($f)
                    ->mapping();
                $values[] = $value === null ? null : $this->bind('condition', $f, is_array($value) ? $value[$i] : $value);
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
    public function order($field, $order = BuilderInterface::ORDER_ASC)
    {
        // todo - check if model has field

        if (!is_array($order) && !in_array($order, array(BuilderInterface::ORDER_ASC, BuilderInterface::ORDER_DESC))) {
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
     * @param string $relationName
     * @param bool   $transparent
     *
     * @return $this
     * @throws QueryException
     */
    public function relation($relationName, $transparent = false)
    {
        list($relationName, $furtherRelations) = $this->splitRelationName($relationName);

        $relation = $this->model->relation($relationName);
        $query = new self($this->driver, $this->builder, $this->model);

        switch ($relation->type()) {
            case RelationInterface::RELATION_ONE:
                $relation = new One($query, $relation);
                break;
            case RelationInterface::RELATION_MANY:
                $relation = new Many($query, $relation);
                break;
            default:
                throw new QueryException(sprintf('Invalid relation type "%s" in relation "%s" for "%s"', $relation->type(), $relationName, $this->model->entity()));
        }

        $relation->transparent($transparent);

        if ($furtherRelations) {
            $relation
                ->query()
                ->relation($furtherRelations, $transparent);
        }

        $this->relations[] = $relation;

        return $this;
    }

    protected function splitRelationName($relationName)
    {
        $furtherRelations = null;
        if (strpos($relationName, '.') !== false) {
            return explode('.', $relationName, 2);
        }

        return array($relationName, null);
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
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
     * Previews query
     * Write operation is treated as update
     *
     * @return string
     */
    public function preview()
    {
        switch ($this->operation) {
            case self::OPERATION_COUNT:
                $query = $this->buildCount();
                break;
            case self::OPERATION_READ:
            case self::OPERATION_READ_ONE:
                $query = $this->buildRead();
                break;
            case self::OPERATION_INSERT:
                $query = $this->buildInsert();
                break;
            case self::OPERATION_WRITE:
            case self::OPERATION_UPDATE:
                $query = $this->buildUpdate();

                break;
            case self::OPERATION_DELETE:
                $query = $this->buildDelete();

                break;
            case self::OPERATION_CLEAR:
                $query = $this->buildClear();
                break;
            default:
                $query = false;
        }

        $result = array($query, $this->binds);

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
        $query = $this->buildCount();

        return $this->driver
            ->prepare($query)
            ->execute($this->binds)
            ->affectedRows();
    }

    /**
     * Builds counting query
     *
     * @return string
     */
    protected function buildCount()
    {
        $this->builder
            ->reset()
            ->operation(BuilderInterface::OPERATION_SELECT)
            ->container($this->model->container());

        foreach ($this->model->primaryFields() as $node) {
            $node = $this->model->field($node);
            $this->builder->field($node->mapping());
        }

        foreach ($this->conditions as $node) {
            $this->builder->condition($node[0], $node[1], $node[2], $node[3]);
        }

        return $this->builder->build();
    }

    /**
     * Executes reading all matching entities query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeRead()
    {
        $query = $this->buildRead();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        $result = $this->driver->fetchAll($this->model->entity(), $this->casts);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }

        return $result;
    }

    /**
     * Executes reading one matching entities query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeReadOne()
    {
        $this->limit(1);

        $result = $this->executeRead();

        if (!count($result)) {
            throw new QueryException(sprintf('Result out of range or does not exists for "%s"', $this->model->entity()));
        }

        return array_shift($result);
    }

    /**
     * Builds reading query
     *
     * @return string
     */
    protected function buildRead()
    {
        $this->builder
            ->reset()
            ->operation(BuilderInterface::OPERATION_SELECT)
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

        $this->builder->limit($this->limit, $this->offset);

        return $this->builder->build();
    }

    /**
     * Executes inserting query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeInsert()
    {
        $query = $this->buildInsert();

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
     * Builds inserting query
     *
     * @return string
     */
    protected function buildInsert()
    {
        $this->builder
            ->reset()
            ->operation(BuilderInterface::OPERATION_INSERT)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            $value = $this->accessProperty($this->instance, $field->name());

            if ($value === null && $field->attribute(ModelInterface::ATTRIBUTE_AUTO)) {
                continue;
            }

            $this->assertValue($field, $value);

            $this->builder->value($field->mapping(), $this->bind('value', $field->name(), $value));
        }

        return $this->builder->build();
    }

    /**
     * Executes updating query
     *
     * @return mixed
     * @throws QueryException
     */
    protected function executeUpdate()
    {
        $query = $this->buildUpdate();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    /**
     * Builds updating query
     * return string
     */
    protected function buildUpdate()
    {
        $this->builder
            ->reset()
            ->operation(BuilderInterface::OPERATION_UPDATE)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            $value = $this->accessProperty($this->instance, $field->name());

            $isUpdated = $this->fieldExists($field);
            $isPrimary = $this->model->isPrimary($field->name());

            if (!$isUpdated && !$isPrimary) {
                continue;
            }

            $this->assertValue($field, $value);
            $value = $this->bind('value', $field->name(), $value);

            if ($isUpdated) {
                $this->builder->value($field->mapping(), $value);
            }

            if ($isPrimary) {
                $this->builder->condition($field->mapping(), $value, BuilderInterface::COMPARISON_EQUAL, BuilderInterface::LOGICAL_AND);
            }
        }

        return $this->builder->build();
    }

    /**
     * Returns true if field exists in selected fields
     *
     * @param $field
     *
     * @return bool
     */
    protected function fieldExists($field)
    {
        if (empty($this->fields)) {
            return true;
        }

        foreach ($this->fields as $node) {
            if ($field->name() == $node[0]) {
                return true;
            }
        }

        return false;
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
        if ($value === null && !$field->attribute(ModelInterface::ATTRIBUTE_AUTO) && !$field->attribute(ModelInterface::ATTRIBUTE_NULL)) {
            throw new QueryException(sprintf('Missing required field value "%s" for entity "%s"', $field->name(), $this->model->entity()));
        }

        // check value length (with distinction for precision)
        if ($limit = $field->attribute('length')) {
            switch ($field->type()) {
                case ModelInterface::FIELD_DECIMAL:
                    $length = strlen(preg_replace('/[^0-9]+/im', '', $value));

                    if ($length > $limit) {
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
        if ($field->attribute('unsigned') && ($field->type() === ModelInterface::FIELD_INTEGER || $field->type() === ModelInterface::FIELD_DECIMAL) && $value < 0) {
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

        $query = $this->buildDelete();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        $this->identifyEntity(null);

        return $this->instance;
    }

    /**
     * Builds deleting query
     *
     * @return string
     */
    protected function buildDelete()
    {
        $this->builder
            ->reset()
            ->operation(BuilderInterface::OPERATION_DELETE)
            ->container($this->model->container());

        foreach ($this->model->fields() as $field) {
            if (!$this->model->isPrimary($field->name())) {
                continue;
            }

            $value = $this->accessProperty($this->instance, $field->name());
            $this->builder->condition($field->mapping(), $this->bind('condition', $field->name(), $value), BuilderInterface::COMPARISON_EQUAL, BuilderInterface::LOGICAL_AND);
        }

        return $this->builder->build();
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

        $query = $this->buildClear();

        $this->driver
            ->prepare($query)
            ->execute();

        return true;
    }

    /**
     * Builds clearing query
     *
     * @return string
     */
    protected function buildClear()
    {
        return $this->builder
            ->reset()
            ->operation(BuilderInterface::OPERATION_CLEAR)
            ->container($this->model->container())
            ->build();
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
        $this->builder->reset();

        return $this;
    }

    /**
     * Binds value to unique key and returns it
     *
     * @param string $operation
     * @param string $field
     * @param mixed  $value
     *
     * @return string
     */
    protected function bind($operation, $field, $value)
    {
        $key = ':' . implode('_', array($operation, count($this->binds), $field));
        $type = $this->model
            ->field($field)
            ->type();

        $this->binds[$key] = $this->driver->cast($value, $type);

        return $key;
    }

    /**
     * Assigns passed identifier to primary key
     * Possible only when entity has one primary key
     *
     * @param int|string $identifier
     *
     * @return void
     */
    protected function identifyEntity($identifier)
    {
        $primaryKeys = $this->model->primaryFields();
        if (count($primaryKeys) !== 1) {
            return;
        }

        $field = reset($primaryKeys);

        if ($this->instance instanceof \ArrayAccess) {
            $this->instance[$field] = $identifier;

            return;
        }

        if (!$this->reflection->hasProperty($field)) {
            $this->instance->$field = $identifier;

            return;
        }

        $prop = $this->reflection->getProperty($field);
        $prop->setAccessible(true);
        $prop->setValue($this->instance, $identifier);
    }

    /**
     * Returns property value
     *
     * @param object $entity
     * @param string $field
     *
     * @return mixed
     */
    protected function accessProperty($entity, $field)
    {
        if ($entity instanceof \ArrayAccess) {
            return isset($entity[$field]) ? $entity[$field] : null;
        }

        if (!$this->reflection->hasProperty($field)) {
            return null;
        }

        $prop = $this->reflection->getProperty($field);
        $prop->setAccessible(true);

        return $prop->getValue($entity);
    }
}
