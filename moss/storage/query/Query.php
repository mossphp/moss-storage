<?php
namespace moss\storage\query;

use moss\storage\builder\QueryInterface as BuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\definition\FieldInterface;
use moss\storage\model\ModelBag;
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

    /** @var ModelBag */
    protected $models;

    /** @var ModelInterface */
    protected $model;

    /** @var \ReflectionObject */
    private $reflection;
    private $instance;

    private $operation;

    private $binds = array();
    private $casts = array();

    /** @var RelationInterface[] */
    private $relations = array();

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
     * Returns model instance
     *
     * @return ModelInterface
     */
    public function model()
    {
        return $this->model;
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
    public function operation($operation, $entity)
    {
        $this->operation = $operation;
        $this->model = $this->models->get($entity);

        if ($this->operation == self::OPERATION_WRITE) {
            $query = new self($this->driver, $this->builder, $this->models);
            $query->operation(self::OPERATION_READ, $entity);

            foreach ($this->model->primaryFields() as $field) {
                $query->where($field, $this->accessProperty($this->instance, $field));
            }

            $this->operation = $query->execute() > 0 ? self::OPERATION_UPDATE : self::OPERATION_INSERT;
        }

        switch ($this->operation) {
            case self::OPERATION_COUNT:
                $this->buildCount();
                break;
            case self::OPERATION_READ:
                $this->buildRead();
                break;
            case self::OPERATION_READ_ONE:
                $this->buildRead(1);
                break;
            case self::OPERATION_INSERT:
                $this->assertEntity($entity);
                $this->instance = & $entity;
                $this->reflection = new \ReflectionObject($entity);
                $this->buildInsert();
                break;
            case self::OPERATION_UPDATE:
                $this->assertEntity($entity);
                $this->instance = & $entity;
                $this->reflection = new \ReflectionObject($entity);
                $this->buildUpdate();
                break;
            case self::OPERATION_DELETE:
                $this->assertEntity($entity);
                $this->instance = & $entity;
                $this->reflection = new \ReflectionObject($entity);
                $this->buildDelete();
                break;
            case self::OPERATION_CLEAR:
                $this->buildClear();
                break;
            default:
                throw new QueryException(sprintf('Unknown operation "%s" in query "%s"', $this->operation, $this->model->entity()));
        }

        return $this;
    }

    protected function assertEntity($entity)
    {
        if ($entity === null) {
            throw new QueryException(sprintf('Missing required entity instance for operation "%s" in query "%s"', $this->operation, $this->model->entity()));
        }

        $entityClass = $this->model->entity();
        if (!$entity instanceof $entityClass) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new QueryException(sprintf('Invalid entity instance for operation "%s", expected "%s", got "%s" in query "%s"', $this->operation, $entityClass, $type, $this->model->entity()));
        }
    }

    /**
     * Binds value to unique key and returns it
     *
     * @param string         $operation
     * @param FieldInterface $field
     * @param mixed          $value
     *
     * @return string
     */
    protected function bind($operation, FieldInterface $field, $value)
    {
        $key = ':' . implode('_', array($operation, count($this->binds), $field->name()));
        $type = $field->type();

        $this->binds[$key] = $this->driver->cast($value, $type);

        return $key;
    }

    protected function buildCount()
    {
        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_SELECT)
                      ->container($this->model->container());

        foreach ($this->model->primaryFields() as $field) {
            $this->assignField($field);
        }
    }

    protected function buildRead($limit = 0)
    {
        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_SELECT)
                      ->container($this->model->container());

        $this->fields();

        if (!$limit) {
            return;
        }

        $this->builder->limit($limit);
    }

    protected function buildInsert()
    {
        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_INSERT)
                      ->container($this->model->container());

        $this->values();
    }

    protected function buildUpdate()
    {
        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_UPDATE)
                      ->container($this->model->container());

        $this->values();

        foreach ($this->model->primaryFields() as $field) {
            $value = $this->accessProperty($this->instance, $field->name());
            $value = $this->bind('condition', $field, $value);

            $this->builder->where(
                          $field->mapping(),
                          $value,
                          BuilderInterface::COMPARISON_EQUAL,
                          BuilderInterface::LOGICAL_AND
            );
        }
    }

    protected function buildDelete()
    {
        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_DELETE)
                      ->container($this->model->container());

        foreach ($this->model->primaryFields() as $field) {
            $value = $this->accessProperty($this->instance, $field->name());
            $value = $this->bind('condition', $field, $value);

            $this->builder->where(
                          $field->mapping(),
                          $value,
                          BuilderInterface::COMPARISON_EQUAL,
                          BuilderInterface::LOGICAL_AND
            );
        }
    }

    protected function buildClear()
    {
        $this->builder->reset()
                      ->operation(BuilderInterface::OPERATION_CLEAR)
                      ->container($this->model->container());
    }

    protected function resolveField($field)
    {
        if (strpos($field, BuilderInterface::SEPARATOR) === false) {
            if (!$this->model->hasField($field)) {
                throw new QueryException(sprintf('Unable to access field "%s", from local model "%s"', $field, $this->model->entity()));
            }

            return $this->model->field($field);
        }

        list($model, $field) = explode(BuilderInterface::SEPARATOR, $field);

        if (!$this->model->hasRelation($model)) {
            throw new QueryException(sprintf('Unable to access field "%s", from local model or via relation in query "%s"', $field, $this->model->entity()));
        }

        return $this->models->get($model)
                            ->field($field);
    }

    protected function assertValue($value)
    {
        // todo - assert if value matches field type, length or can be converted to it
    }

    /**
     * Sets field names which will be read
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields($fields = array())
    {
        $this->builder->fields(array());
        $this->casts = array();

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->assignField($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->assignField($this->resolveField($field));
        }

        return $this;
    }

    /**
     * Adds field to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function field($field)
    {
        $this->assignField($this->resolveField($field));

        return $this;
    }

    protected function assignField(FieldInterface $field)
    {
        $this->builder->field(
                      $field->container() . BuilderInterface::SEPARATOR . $field->name(),
                      $field->name() == $field->mapping() ? null : $field->mapping()
        );

        $this->casts[$field->mapping()] = $field->type();
    }

    /**
     * Adds distinct method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function distinct($field, $alias = null)
    {
        $this->aggregate(BuilderInterface::AGGREGATE_DISTINCT, $field, $alias);

        return $this;
    }

    /**
     * Adds count method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function count($field, $alias = null)
    {
        $this->aggregate(BuilderInterface::AGGREGATE_COUNT, $field, $alias);

        return $this;
    }

    /**
     * Adds average method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function average($field, $alias = null)
    {
        $this->aggregate(BuilderInterface::AGGREGATE_AVERAGE, $field, $alias);

        return $this;
    }

    /**
     * Adds max method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function max($field, $alias = null)
    {
        $this->aggregate(BuilderInterface::AGGREGATE_MAX, $field, $alias);

        return $this;
    }

    /**
     * Adds min method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function min($field, $alias = null)
    {
        $this->aggregate(BuilderInterface::AGGREGATE_MIN, $field, $alias);

        return $this;
    }

    /**
     * Adds sum method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function sum($field, $alias = null)
    {
        $this->aggregate(BuilderInterface::AGGREGATE_SUM, $field, $alias);

        return $this;
    }

    /**
     * Adds aggregate method to query
     *
     * @param string $method
     * @param string $field
     * @param string $alias
     *
     * @return $this
     * @throws QueryException
     */
    public function aggregate($method, $field, $alias = null)
    {
        $this->assertAggregate($method);

        $field = $this->resolveField($field);

        $this->builder->aggregate(
                      $method,
                      $field->container() . BuilderInterface::SEPARATOR . $field->mapping(),
                      $alias
        );

        return $this;
    }

    protected function assertAggregate($method)
    {
        $aggregateMethods = array(
            BuilderInterface::AGGREGATE_DISTINCT,
            BuilderInterface::AGGREGATE_COUNT,
            BuilderInterface::AGGREGATE_AVERAGE,
            BuilderInterface::AGGREGATE_MAX,
            BuilderInterface::AGGREGATE_MIN,
            BuilderInterface::AGGREGATE_SUM,
        );
        if (!in_array($method, $aggregateMethods)) {
            throw new QueryException(sprintf('Invalid aggregation method "%s" in query', $method, $this->model->entity()));
        }
    }

    /**
     * Adds grouping to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function group($field)
    {
        $field = $this->resolveField($field);

        $this->builder->group($field->container() . BuilderInterface::SEPARATOR . $field->mapping());

        return $this;
    }

    /**
     * Sets field names which values will be written
     *
     * @param array $fields
     *
     * @return $this
     */
    public function values($fields = array())
    {
        $this->builder->values(array());
        $this->binds = array();

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->assignValue($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->assignValue($this->resolveField($field));
        }

        return $this;
    }

    /**
     * Adds field which value will be written
     *
     * @param string $field
     *
     * @return $this
     */
    public function value($field)
    {
        $this->assignValue($this->resolveField($field));

        return $this;
    }


    protected function assignValue(FieldInterface $field)
    {
        if ($field->container() != $this->model->container()) {
            return;
        }

        $value = $this->accessProperty($this->instance, $field->name());

        if ($this->operation === self::OPERATION_INSERT && $value === null && $field->attribute(ModelInterface::ATTRIBUTE_AUTO)) {
            return;
        }

        $this->builder->value(
                      $field->mapping(),
                      $this->bind('value', $field, $value)
        );
    }

    /**
     * Adds inner join with set container
     *
     * @param string $entity
     *
     * @return $this
     */
    public function innerJoin($entity)
    {
        $this->join(BuilderInterface::JOIN_INNER, $entity);

        return $this;
    }

    /**
     * Adds left join with set container
     *
     * @param string $entity
     *
     * @return $this
     */
    public function leftJoin($entity)
    {
        $this->join(BuilderInterface::JOIN_LEFT, $entity);

        return $this;
    }

    /**
     * Adds right join with set container
     *
     * @param string $entity
     *
     * @return $this
     */
    public function rightJoin($entity)
    {
        $this->join(BuilderInterface::JOIN_RIGHT, $entity);

        return $this;
    }


    /**
     * Adds join to query
     *
     * @param string $type
     * @param string $entity
     *
     * @return $this
     * @throws QueryException
     */
    public function join($type, $entity)
    {
        if (!$this->model->hasRelation($entity)) {
            throw new QueryException(sprintf('Unable to join "%s" in query "%s"', $entity, $this->model->entity()));
        }

        $relation = $this->model->relation($entity);
        $this->builder->join(
                      $type,
                      $relation->container(),
                      $relation->keys()
        );

        foreach ($relation->localValues() as $field => $value) {
            $this->where($field, $value);
        }

        foreach ($relation->foreignValues() as $field => $value) {
            $this->where($relation->container() . BuilderInterface::SEPARATOR . $field, $value);
        }

        return $this;
    }

    /**
     * Adds where condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = BuilderInterface::COMPARISON_EQUAL, $logical = BuilderInterface::LOGICAL_AND)
    {
        $this->assertComparison($comparison);
        $this->assertLogical($logical);

        list($field, $value) = $this->condition($field, $value, $comparison, $logical);
        $this->builder->where($field, $value, $comparison, $logical);

        return $this;
    }

    /**
     * Adds having condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function having($field, $value, $comparison = BuilderInterface::COMPARISON_EQUAL, $logical = BuilderInterface::LOGICAL_AND)
    {
        $this->assertComparison($comparison);
        $this->assertLogical($logical);

        list($field, $value) = $this->condition($field, $value, $comparison, $logical);
        $this->builder->having($field, $value, $comparison, $logical);

        return $this;
    }

    protected function assertComparison($operator)
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
        if (!in_array($operator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $operator, $this->model->entity()));
        }
    }

    protected function assertLogical($operator)
    {
        $comparisonOperators = array(
            BuilderInterface::LOGICAL_OR,
            BuilderInterface::LOGICAL_AND,
        );
        if (!in_array($operator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $operator, $this->model->entity()));
        }
    }

    protected function condition($field, $value)
    {
        $fields = array();
        $values = array();

        if (!is_array($field) && is_array($value)) {
            for ($i = 0, $l = count($value); $i < $l; $i++) {
                $f = $this->resolveField($field);
                $fields[] = $f->container() . BuilderInterface::SEPARATOR . $f->mapping();
                $values[] = !array_key_exists($i, $value) || $value[$i] === null ? null : $this->bindValues($f, $value[$i]);
            }
        } else {
            foreach ((array) $field as $i => $f) {
                if (!is_scalar($f)) {
                    throw new QueryException(sprintf('Expected field name, got "%s" in query "%s"', is_object($f) ? get_class($f) : gettype($f), $this->model->entity()));
                }

                $f = $this->resolveField($f);
                $fields[] = $f->container() . BuilderInterface::SEPARATOR . $f->mapping();
                $values[] = $value === null ? null : $this->bindValues($f, is_array($value) ? $value[$i] : $value);
            }
        }

        return array(
            $fields,
            $values,
        );
    }

    protected function bindValues($field, $values)
    {
        if (!is_array($values)) {
            return $this->bind('condition', $field, $values);
        }

        foreach ($values as $key => $value) {
            $values[$key] = $this->bindValues($field, $value);
        }

        return $values;
    }

    /**
     * Adds sorting to query
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     * @throws QueryException
     */
    public function order($field, $order = BuilderInterface::ORDER_DESC)
    {
        $this->assertOrder($order);

        $field = $this->resolveField($field);

        if (!is_array($order) && !in_array($order, array(BuilderInterface::ORDER_ASC, BuilderInterface::ORDER_DESC))) {
            throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', is_scalar($order) ? $order : gettype($order), $this->model->entity()));
        }

        if (is_array($order)) {
            foreach ($order as $i => &$o) {
                $order[$i] = $this->bind('order', $field, (string) $o);
            }
        }

        $this->builder->order(
                      $field->mapping(),
                      $order
        );

        return $this;
    }

    protected function assertOrder($order)
    {
        if (!is_array($order) && !in_array($order, array(BuilderInterface::ORDER_ASC, BuilderInterface::ORDER_DESC))) {
            throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', is_scalar($order) ? $order : gettype($order), $this->model->entity()));
        }
    }

    /**
     * Sets limits to query
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->builder->limit(
                      (int) $limit,
                      $offset ? (int) $offset : null
        );

        return $this;
    }

    /**
     * Adds relation to query
     *
     * @param string $relation
     * @param bool   $transparent
     *
     * @return $this
     */
    public function relation($relation, $transparent = false)
    {
        list($relation, $furtherRelations) = $this->splitRelationName($relation);

        $definition = $this->model->relation($relation);
        $query = new self($this->driver, $this->builder, $this->models);

        switch ($definition->type()) {
            case RelationInterface::RELATION_ONE:
                $relation = new One($query, $definition);
                break;
            case RelationInterface::RELATION_MANY:
                $relation = new Many($query, $definition);
                break;
            default:
                throw new QueryException(sprintf('Invalid relation type "%s" in relation "%s" for "%s"', $definition->type(), $relation, $this->model->entity()));
        }

        $relation->transparent($transparent);

        if ($furtherRelations) {
            $definition->relation($furtherRelations, $transparent);
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
        switch ($this->operation) {
            case self::OPERATION_COUNT:
                $result = $this->executeCount();
                break;
            case self::OPERATION_READ_ONE:
                $result = $this->executeReadOne();
                break;
            case self::OPERATION_READ:
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

    protected function executeCount()
    {
        $query = $this->builder->build();

        return $this->driver
            ->prepare($query)
            ->execute($this->binds)
            ->affectedRows();
    }

    protected function executeReadOne()
    {
        $result = $this->executeRead();

        if (!count($result)) {
            throw new QueryException(sprintf('Result out of range or does not exists for "%s"', $this->model->entity()));
        }

        return array_shift($result);
    }

    protected function executeRead()
    {
        $query = $this->builder->build();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        $result = $this->driver->fetchAll($this->model->entity(), $this->casts);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }

        return $result;
    }

    protected function executeInsert()
    {
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

    protected function executeUpdate()
    {
        $query = $this->builder->build();

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    protected function executeDelete()
    {
        $query = $this->builder->build();

        foreach ($this->relations as $relation) {
            $relation->delete($this->instance);
        }

        $this->driver
            ->prepare($query)
            ->execute($this->binds);

        $this->identifyEntity(null);

        return $this->instance;
    }

    protected function executeClear()
    {
        $query = $this->builder->build();

        foreach ($this->relations as $relation) {
            $relation->clear();
        }

        $this->driver
            ->prepare($query)
            ->execute();

        return true;
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

    /**
     * Returns current query string
     *
     * @return string
     */
    public function queryString()
    {
        return array(
            $this->builder->build(),
            $this->binds
        );
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->model = null;

        $this->reflection = null;
        $this->instance = null;

        $this->operation = null;

        $this->binds = array();
        $this->casts = array();

        $this->driver->reset();
        $this->builder->reset();

        return $this;
    }
}