<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Query;

use Moss\Storage\Builder\QueryInterface as BuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\Many;
use Moss\Storage\Query\Relation\ManyTrough;
use Moss\Storage\Query\Relation\One;
use Moss\Storage\Query\Relation\OneTrough;
use Moss\Storage\Query\Relation\RelationInterface;

/**
 * Query used to create and execute CRUD operations on entities
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Query
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

    private $instance;

    private $operation;

    private $joins = array();

    private $fields = array();
    private $aggregates = array();
    private $group = array();

    private $values = array();

    private $where = array();
    private $having = array();

    private $order = array();

    private $limit = null;
    private $offset = null;

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
     * Returns var type
     *
     * @param mixed $var
     *
     * @return string
     */
    private function getType($var)
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }

    /**
     * Sets query operation
     *
     * @param string $operation
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     * @throws QueryException
     */
    public function operation($operation, $entity, $instance = null)
    {
        if (!is_string($entity)) {
            throw new QueryException(sprintf('Entity must be a namespaced class name, its alias or object, got "%s"', gettype($entity)));
        }

        $this->operation = $operation;
        $this->model = $this->models->get($entity);

        if ($this->operation == self::OPERATION_WRITE) {
            $this->operation = $this->checkIfEntityExists($entity, $instance) ? self::OPERATION_UPDATE : self::OPERATION_INSERT;
        }

        switch ($this->operation) {
            case self::OPERATION_COUNT:
                foreach ($this->model->primaryFields() as $field) {
                    $this->assignField($field);
                }
                break;
            case self::OPERATION_READ:
                $this->fields();
                break;
            case self::OPERATION_READ_ONE:
                $this->fields();
                $this->limit(1);
                break;
            case self::OPERATION_INSERT:
                $this->assertEntity($instance);
                $this->instance = & $instance;
                $this->values();
                break;
            case self::OPERATION_UPDATE:
                $this->assertEntity($instance);
                $this->instance = & $instance;
                $this->values();

                foreach ($this->model->primaryFields() as $field) {
                    $value = $this->accessProperty($this->instance, $field->name());
                    $value = $this->bind('condition', $field, $value);

                    $this->where[] = array(
                        $field->mapping(),
                        $value,
                        BuilderInterface::COMPARISON_EQUAL,
                        BuilderInterface::LOGICAL_AND
                    );
                }
                break;
            case self::OPERATION_DELETE:
                $this->assertEntity($instance);
                $this->instance = & $instance;

                foreach ($this->model->primaryFields() as $field) {
                    $value = $this->accessProperty($this->instance, $field->name());
                    $value = $this->bind('condition', $field, $value);

                    $this->where[] = array(
                        $field->mapping(),
                        $value,
                        BuilderInterface::COMPARISON_EQUAL,
                        BuilderInterface::LOGICAL_AND
                    );
                }
                break;
            case self::OPERATION_CLEAR:
                break;
            default:
                throw new QueryException(sprintf('Unknown operation "%s" in query "%s"', $this->operation, $this->model->entity()));
        }

        return $this;
    }

    protected function checkIfEntityExists($entity, $instance)
    {
        $this->assertEntity($instance);

        $query = new self($this->driver, $this->builder, $this->models);
        $query->operation(self::OPERATION_COUNT, $entity, $instance);

        foreach ($this->model->primaryFields() as $field) {
            $value = $this->accessProperty($instance, $field->name());

            if ($value === null) {
                return false;
            }

            $query->where($field->name(), $value);
        }

        return $query->execute() > 0;
    }

    protected function assertEntity($instance)
    {
        if ($instance === null) {
            throw new QueryException(sprintf('Missing required entity for operation "%s" in query "%s"', $this->operation, $this->model->entity()));
        }

        if (!is_object($instance) && !is_array($instance)) {
            throw new QueryException(sprintf('Entity for operation "%s" must be an instance of "%s" or array got "%s"', $this->operation, $this->model->entity(), $this->getType($instance)));
        }

        // todo - if array - check if has primary fields
        // todo - if object - check if it is instance of required entity
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

        $this->binds[$key] = $this->driver->store($value, $type);

        return $key;
    }

    protected function resolveField($field)
    {
        $relation = $this->model->table();
        if (strpos($field, BuilderInterface::SEPARATOR) !== false) {
            list($relation, $field) = explode(BuilderInterface::SEPARATOR, $field, 2);
        }

        if ($this->model->table() === $relation) {
            if (!$this->model->hasField($field)) {
                throw new QueryException(sprintf('Unable to access field "%s", from local model in query "%s"', $field, $this->model->entity()));
            }

            return $this->model->field($field);
        }

        if (!$this->model->hasRelation($relation)) {
            throw new QueryException(sprintf('Unable to access field "%s", from foreign model in query "%s"', $field, $this->model->entity()));
        }

        $entity = $this->model->relation($relation)
            ->entity();

        return $this->models->get($entity)
            ->field($field);
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
        $this->fields = array();
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
        $this->fields[] = array(
            $field->table() . BuilderInterface::SEPARATOR . $field->name(),
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

        $this->aggregates[] = array(
            $method,
            $field->table() . BuilderInterface::SEPARATOR . $field->mapping(),
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

        $this->group[] = $field->table() . BuilderInterface::SEPARATOR . $field->mapping();

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
        $this->values = array();
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
        if ($field->table() != $this->model->table()) {
            return;
        }

        $value = $this->accessProperty($this->instance, $field->name());

        if ($this->operation === self::OPERATION_INSERT && $value === null && $field->attribute(ModelInterface::ATTRIBUTE_AUTO)) {
            return;
        }

        $this->values[] = array(
            $field->mapping(),
            $this->bind('value', $field, $value)
        );
    }

    /**
     * Adds inner join with set table
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
     * Adds left join with set table
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
     * Adds right join with set table
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
        // todo - join `trough` relations

        if (!$this->model->hasRelation($entity)) {
            throw new QueryException(sprintf('Unable to join "%s" in query "%s"', $entity, $this->model->entity()));
        }

        $relation = $this->model->relation($entity);
        $this->joins[] = array(
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

        list($field, $value) = $this->condition($field, $value);
        $this->where[] = array($field, $value, $comparison, $logical);

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

        list($field, $value) = $this->condition($field, $value);
        $this->having[] = array($field, $value, $comparison, $logical);

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
                $fields[] = $f->table() . BuilderInterface::SEPARATOR . $f->mapping();
                $values[] = !array_key_exists($i, $value) || $value[$i] === null ? null : $this->bindValues($f, $value[$i]);
            }
        } else {
            foreach ((array) $field as $i => $f) {
                if (!is_scalar($f)) {
                    throw new QueryException(sprintf('Expected field name, got "%s" in query "%s"', $this->getType($f), $this->model->entity()));
                }

                $f = $this->resolveField($f);
                $fields[] = $f->table() . BuilderInterface::SEPARATOR . $f->mapping();
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
            throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', $this->getType($order), $this->model->entity()));
        }

        if (is_array($order)) {
            foreach ($order as $i => &$o) {
                $order[$i] = $this->bind('order', $field, (string) $o);
            }
        }

        $this->order[] = array(
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
        $this->limit = (int) $limit;
        $this->offset = $offset ? (int) $offset : null;

        return $this;
    }

    /**
     * Adds relation to query with optional conditions and sorting (as key value pairs)
     *
     * @param string|array $relation
     * @param array        $conditions
     * @param array        $order
     *
     * @return $this
     * @throws QueryException
     */
    public function with($relation, array $conditions = array(), array $order = array())
    {
        if (!is_array($relation)) {
            $this->assignRelation($relation, $conditions, $order);

            return $this;
        }

        foreach (array_keys($relation) as $i) {
            $this->assignRelation(
                $relation[$i],
                isset($conditions[$i]) ? $conditions[$i] : null,
                isset($order[$i]) ? $order[$i] : null
            );
        }

        return $this;
    }

    protected function assignRelation($relation, array $conditions = array(), array $order = array())
    {
        list($relation, $furtherRelations) = $this->splitRelationName($relation);

        $definition = $this->model->relation($relation);

        $query = new self($this->driver, $this->builder, $this->models);
        $query->operation(self::OPERATION_READ, $definition->entity());

        switch ($definition->type()) {
            case RelationInterface::RELATION_ONE:
                $instance = new One($query, $definition, $this->models);
                break;
            case RelationInterface::RELATION_MANY:
                $instance = new Many($query, $definition, $this->models);
                break;
            case RelationInterface::RELATION_ONE_TROUGH:
                $instance = new OneTrough($query, $definition, $this->models);
                break;
            case RelationInterface::RELATION_MANY_TROUGH:
                $instance = new ManyTrough($query, $definition, $this->models);
                break;
            default:
                throw new QueryException(sprintf('Invalid relation type "%s" in relation "%s" for "%s"', $definition->type(), $relation, $this->model->entity()));
        }

        foreach ($conditions as $node) {
            // todo - check if node is array
            $instance->query()
                ->where($node[0], $node[1], isset($node[2]) ? $node[2] : BuilderInterface::COMPARISON_EQUAL, isset($node[3]) ? $node[3] : BuilderInterface::LOGICAL_AND);
        }

        foreach ($order as $node) {
            // todo - check if node is array
            $instance->query()
                ->order($node[0], isset($node[1]) ? $node[1] : BuilderInterface::ORDER_DESC);
        }

        if ($furtherRelations) {
            $instance->with($furtherRelations);
        }

        $this->relations[$relation] = $instance;
    }

    /**
     * Returns relation instance
     *
     * @param string $relation
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function relation($relation)
    {
        list($relation, $furtherRelations) = $this->splitRelationName($relation);

        if (!isset($this->relations[$relation])) {
            throw new QueryException(sprintf('Unable to retrieve relation "%s" query, relation does not exists in query "%s"', $relation, $this->model->entity()));
        }

        $instance = $this->relations[$relation];

        if ($furtherRelations) {
            return $instance->relation($furtherRelations);
        }

        return $instance;
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
        return $this->driver
            ->prepare($this->buildCount())
            ->execute($this->binds)
            ->affectedRows();
    }

    protected function buildCount()
    {
        $this->builder->reset()
            ->operation(BuilderInterface::OPERATION_SELECT)
            ->table($this->model->table());

        foreach ($this->fields as $field) {
            $this->builder->field($field[0], $field[1]);
        }

        foreach ($this->aggregates as $node) {
            $this->builder->aggregate($node[0], $node[1], $node[2]);
        }

        foreach ($this->group as $node) {
            $this->builder->group($node);
        }

        foreach ($this->where as $node) {
            $this->builder->where($node[0], $node[1], $node[2], $node[3]);
        }

        foreach ($this->having as $node) {
            $this->builder->having($node[0], $node[1], $node[2], $node[3]);
        }

        return $this->builder->build();
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
        $this->driver
            ->prepare($this->buildRead())
            ->execute($this->binds);

        $result = $this->driver->fetchAll($this->model->entity(), $this->casts);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }

        return $result;
    }

    protected function buildRead()
    {
        $this->builder->reset()
            ->operation(BuilderInterface::OPERATION_SELECT)
            ->table($this->model->table());

        foreach ($this->joins as $node) {
            $this->builder->join($node[0], $node[1], $node[2]);
        }

        foreach ($this->fields as $node) {
            $this->builder->field($node[0], $node[1]);
        }

        foreach ($this->aggregates as $node) {
            $this->builder->aggregate($node[0], $node[1], $node[2]);
        }

        foreach ($this->group as $node) {
            $this->builder->group($node);
        }

        foreach ($this->where as $node) {
            $this->builder->where($node[0], $node[1], $node[2], $node[3]);
        }

        foreach ($this->having as $node) {
            $this->builder->having($node[0], $node[1], $node[2], $node[3]);
        }

        foreach ($this->order as $node) {
            $this->builder->order($node[0], $node[1]);
        }

        if ($this->limit) {
            $this->builder->limit($this->limit, $this->offset);
        }

        return $this->builder->build();
    }

    protected function executeInsert()
    {
        $result = $this->driver
            ->prepare($this->buildInsert())
            ->execute($this->binds)
            ->lastInsertId();

        $this->identifyEntity($this->instance, $result);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    protected function buildInsert()
    {
        $this->builder->reset()
            ->operation(BuilderInterface::OPERATION_INSERT)
            ->table($this->model->table());

        foreach ($this->values as $node) {
            $this->builder->value($node[0], $node[1]);
        }

        return $this->builder->build();
    }

    protected function executeUpdate()
    {
        $this->driver
            ->prepare($this->buildUpdate())
            ->execute($this->binds);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    protected function buildUpdate()
    {
        $this->builder->reset()
            ->operation(BuilderInterface::OPERATION_UPDATE)
            ->table($this->model->table());

        foreach ($this->values as $node) {
            $this->builder->value($node[0], $node[1]);
        }

        foreach ($this->where as $node) {
            $this->builder->where($node[0], $node[1], $node[2], $node[3]);
        }

        if ($this->limit) {
            $this->builder->limit($this->limit, $this->offset);
        }

        return $this->builder->build();
    }

    protected function executeDelete()
    {
        foreach ($this->relations as $relation) {
            $relation->delete($this->instance);
        }

        $this->driver
            ->prepare($this->buildDelete())
            ->execute($this->binds);

        $this->identifyEntity($this->instance, null);

        return $this->instance;
    }

    protected function buildDelete()
    {
        $this->builder->reset()
            ->operation(BuilderInterface::OPERATION_DELETE)
            ->table($this->model->table());

        foreach ($this->where as $node) {
            $this->builder->where($node[0], $node[1], $node[2], $node[3]);
        }

        if ($this->limit) {
            $this->builder->limit($this->limit, $this->offset);
        }

        return $this->builder->build();
    }

    protected function executeClear()
    {
        foreach ($this->relations as $relation) {
            $relation->clear();
        }

        $this->driver
            ->prepare($this->buildClear())
            ->execute();

        return true;
    }

    protected function buildClear()
    {
        return $this->builder->reset()
            ->operation(BuilderInterface::OPERATION_CLEAR)
            ->table($this->model->table())
            ->build();
    }

    /**
     * Assigns passed identifier to primary key
     * Possible only when entity has one primary key
     *
     * @param array|object $entity
     * @param int|string   $identifier
     *
     * @return void
     */
    protected function identifyEntity($entity, $identifier)
    {
        $primaryKeys = $this->model->primaryFields();
        if (count($primaryKeys) !== 1) {
            return;
        }

        $field = reset($primaryKeys);
        $field = $field->name();

        if (is_array($entity) || $entity instanceof \ArrayAccess) {
            $entity[$field] = $identifier;

            return;
        }

        $ref = new \ReflectionObject($entity);
        if (!$ref->hasProperty($field)) {
            $entity->$field = $identifier;

            return;
        }

        $prop = $ref->getProperty($field);
        $prop->setAccessible(true);
        $prop->setValue($entity, $identifier);
    }

    /**
     * Returns property value
     *
     * @param array|object $entity
     * @param string       $field
     *
     * @return mixed
     */
    protected function accessProperty($entity, $field)
    {
        if (is_array($entity) || $entity instanceof \ArrayAccess) {
            return isset($entity[$field]) ? $entity[$field] : null;
        }

        $ref = new \ReflectionObject($entity);
        if (!$ref->hasProperty($field)) {
            return null;
        }

        $prop = $ref->getProperty($field);
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
        switch ($this->operation) {
            case self::OPERATION_COUNT:
                $queryString = $this->buildCount();
                break;
            case self::OPERATION_READ_ONE:
            case self::OPERATION_READ:
                $queryString = $this->buildRead();
                break;
            case self::OPERATION_INSERT:
                $queryString = $this->buildInsert();
                break;
            case self::OPERATION_UPDATE:
                $queryString = $this->buildUpdate();
                break;
            case self::OPERATION_DELETE:
                $queryString = $this->buildDelete();
                break;
            case self::OPERATION_CLEAR:
                $queryString = $this->buildClear();
                break;
            default:
                $queryString = null;
        }

        return array(
            $queryString,
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

        $this->instance = null;

        $this->operation = null;

        $this->joins = array();

        $this->fields = array();
        $this->aggregates = array();
        $this->group = array();

        $this->values = array();

        $this->where = array();
        $this->having = array();

        $this->order = array();

        $this->limit = null;
        $this->offset = null;

        $this->binds = array();
        $this->casts = array();

        $this->driver->reset();
        $this->builder->reset();

        $this->relations = array();

        return $this;
    }
}