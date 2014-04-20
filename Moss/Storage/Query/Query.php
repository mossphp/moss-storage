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

use Moss\Storage\Builder\QueryBuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\Definition\RelationInterface as RelationDefinitionInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\ManyRelation;
use Moss\Storage\Query\Relation\ManyTroughRelation;
use Moss\Storage\Query\Relation\OneRelation;
use Moss\Storage\Query\Relation\OneTroughRelation;
use Moss\Storage\Query\Relation\RelationInterface;

/**
 * Query used to create and execute CRUD operations on entities
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Query implements QueryInterface
{
    /** @var DriverInterface */
    protected $driver;

    /** @var QueryBuilderInterface */
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

    /**
     * Constructor
     *
     * @param DriverInterface  $driver
     * @param QueryBuilderInterface $builder
     * @param ModelBag         $models
     */
    public function __construct(DriverInterface $driver, QueryBuilderInterface $builder, ModelBag $models)
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
     * @return QueryBuilderInterface
     */
    public function builder()
    {
        return $this->builder;
    }

    /**
     * Sets counting operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function num($entity)
    {
        return $this->operation('num', $entity);
    }

    /**
     * Sets read operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function read($entity)
    {
        return $this->operation('read', $entity);
    }

    /**
     * Sets read one operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function readOne($entity)
    {
        return $this->operation('readOne', $entity);
    }

    /**
     * Sets write operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function write($entity, $instance)
    {
        return $this->operation('write', $entity, $instance);
    }

    /**
     * Sets insert operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function insert($entity, $instance)
    {
        return $this->operation('insert', $entity, $instance);
    }

    /**
     * Sets update operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function update($entity, $instance)
    {
        return $this->operation('update', $entity, $instance);
    }

    /**
     * Sets delete operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function delete($entity, $instance)
    {
        return $this->operation('delete', $entity, $instance);
    }

    /**
     * Sets clear operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function clear($entity)
    {
        return $this->operation('clear', $entity);
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

        if ($this->operation == 'write') {
            $this->operation = $this->checkIfEntityExists($entity, $instance) ? 'update' : 'insert';
        }

        switch ($this->operation) {
            case 'num':
                foreach ($this->model->primaryFields() as $field) {
                    $this->assignField($field);
                }
                break;
            case 'read':
                $this->fields();
                break;
            case 'readOne':
                $this->fields();
                $this->limit(1);
                break;
            case 'insert':
                $this->assertEntity($instance);
                $this->instance = & $instance;
                $this->values();
                break;
            case 'update':
                $this->assertEntity($instance);
                $this->instance = & $instance;
                $this->values();

                foreach ($this->model->primaryFields() as $field) {
                    $value = $this->accessProperty($this->instance, $field->name());
                    $value = $this->bind('condition', $field->name(), $field->type(), $value);

                    $this->where[] = array($field->mapping(), $value, '=', 'and');
                }
                break;
            case 'delete':
                $this->assertEntity($instance);
                $this->instance = & $instance;

                foreach ($this->model->primaryFields() as $field) {
                    $value = $this->accessProperty($this->instance, $field->name());
                    $value = $this->bind('condition', $field->name(), $field->type(), $value);

                    $this->where[] = array($field->mapping(), $value, '=', 'and');
                }
                break;
            case 'clear':
                break;
            default:
                throw new QueryException(sprintf('Unknown operation "%s" in query "%s"', $this->operation, $this->model->entity()));
        }

        return $this;
    }

    /**
     * Returns true if entity exists database
     *
     * @param string       $entity
     * @param array|object $instance
     *
     * @return bool
     */
    protected function checkIfEntityExists($entity, $instance)
    {
        $this->assertEntity($instance);

        $query = new self($this->driver, $this->builder, $this->models);
        $query->operation('num', $entity, $instance);

        foreach ($this->model->primaryFields() as $field) {
            $value = $this->accessProperty($instance, $field->name());

            if ($value === null) {
                return false;
            }

            $query->where($field->name(), $value);
        }

        return $query->execute() > 0;
    }

    /**
     * Asserts entity instance
     *
     * @param array|object $instance
     *
     * @throws QueryException
     */
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
     * @param string $operation
     * @param string $field
     * @param string $type
     * @param mixed  $value
     *
     * @return string
     */
    protected function bind($operation, $field, $type, $value)
    {
        $key = ':' . implode('_', array($operation, count($this->binds), $field));
        $this->binds[$key] = $this->driver->store($value, $type);

        return $key;
    }

    /**
     * Resolves field from current joined models
     *
     * @param string $field
     *
     * @return FieldInterface
     * @throws QueryException
     */
    private function resolveField($field)
    {
        $relation = $this->model->table();
        if (strpos($field, QueryBuilderInterface::SEPARATOR) !== false) {
            list($relation, $field) = explode(QueryBuilderInterface::SEPARATOR, $field, 2);
        }

        if ($this->model->table() === $relation && $this->model->hasField($field)) {
            return $this->model->field($field);
        }

        if ($this->models->has($relation)) {
            if ($this->model == $this->models->get($relation) && $this->model->hasField($field)) {
                return $this->model->field($field);
            }

            $model = $this->models->get($relation);
            if ($this->model->hasRelation($relation) && $model->hasField($field)) {
                return $model->field($field);
            }
        }

        throw new QueryException(sprintf('Unable to access field "%s" in query "%s"', $field, $this->model->entity()));
    }

    /**
     * Builds field name prefixed with optional table name
     *
     * @param string      $field
     * @param null|string $table
     *
     * @return string
     */
    private function buildField($field, $table)
    {
        if ($table === null) {
            return $field;
        }

        return $table . QueryBuilderInterface::SEPARATOR . $field;
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

    /**
     * Assigns field to query
     *
     * @param FieldInterface $field
     */
    private function assignField(FieldInterface $field)
    {
        $this->fields[] = array(
            $this->buildField($field->name(), $field->table()),
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
        $this->aggregate('distinct', $field, $alias);

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
        $this->aggregate('count', $field, $alias);

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
        $this->aggregate('average', $field, $alias);

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
        $this->aggregate('max', $field, $alias);

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
        $this->aggregate('min', $field, $alias);

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
        $this->aggregate('sum', $field, $alias);

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
            $this->buildField($field->mapping(), $field->table()),
            $alias
        );

        return $this;
    }

    /**
     * Asserts if aggregate method is supported
     *
     * @param string $method
     *
     * @throws QueryException
     */
    private function assertAggregate($method)
    {
        $aggregateMethods = array('distinct', 'count', 'average', 'max', 'min', 'sum');

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

        $this->group[] = $this->buildField($field->mapping(), $field->table());

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

    /**
     * Assigns value to query
     *
     * @param FieldInterface $field
     */
    private function assignValue(FieldInterface $field)
    {
        if ($field->table() != $this->model->table()) {
            return;
        }

        $value = $this->accessProperty($this->instance, $field->name());

        if ($this->operation === 'insert' && $value === null && $field->attribute('auto_increment')) {
            return;
        }

        $this->values[] = array(
            $field->mapping(),
            $this->bind('value', $field->name(), $field->type(), $value)
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
        $this->join('inner', $entity);

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
        $this->join('left', $entity);

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
        $this->join('right', $entity);

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

        switch ($relation->type()) {
            case 'one':
            case 'many':
                $this->joins[] = array(
                    $type,
                    $this->models->get($entity)
                        ->table(),
                    $relation->keys()
                );

                foreach ($relation->localValues() as $field => $value) {
                    $this->where($field, $value);
                }

                foreach ($relation->foreignValues() as $field => $value) {
                    $this->where($this->buildField($field, $relation->container()), $value);
                }
                break;
            case 'oneTrough':
            case 'manyTrough':
                $mediator = $this->models->get($relation->mediator())
                    ->table();

                $entity = $this->models->get($relation->entity())
                    ->table();

                $this->joins[] = array(
                    $type,
                    $mediator,
                    $this->prefixKeys($relation->localKeys(), $this->model->table(), $mediator)
                );

                $this->joins[] = array(
                    $type,
                    $entity,
                    $this->prefixKeys($relation->foreignKeys(), $mediator, $entity)
                );

                foreach ($relation->localValues() as $field => $value) {
                    $this->where($this->buildField($field, $entity), $value);
                }

                foreach ($relation->foreignValues() as $field => $value) {
                    $this->where($this->buildField($field, $relation->container()), $value);
                }

                break;
        }

        return $this;
    }

    /**
     * Prefixes local/foreign keys with table name
     *
     * @param array $keys
     * @param       $localPrefix
     * @param       $foreignPrefix
     *
     * @return array
     */
    private function prefixKeys(array $keys, $localPrefix, $foreignPrefix)
    {
        $result = array();
        foreach ($keys as $local => $foreign) {
            $result[$this->buildField($local, $localPrefix)] = $this->buildField($foreign, $foreignPrefix);
        }

        return $result;
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
     * @throws QueryException
     */
    public function where($field, $value, $comparison = '=', $logical = 'and')
    {
        $this->assertComparison($comparison);
        $this->assertLogical($logical);


        $fields = array();
        $values = array();

        if (!is_array($field) && is_array($value)) {
            $f = $this->resolveField($field);
            foreach ($value as $i => $v) {
                $fields[$i] = $this->buildField($f->mapping(), $f->table());
                $values[] = $v === null ? null : $this->bindValues($f->mapping(), $f->type(), $v);
            }
        } else {
            foreach ((array) $field as $i => $f) {
                $this->assertFieldName($f);

                $f = $this->resolveField($f);
                $fields[] = $this->buildField($f->mapping(), $f->table());
                if ($value === null || $value === array()) {
                    $values[] = null;
                } else {
                    $values[] = $this->bindValues($f->mapping(), $f->type(), is_array($value) ? $value[$i] : $value);
                }
            }
        }

        $this->where[] = array($fields, $values, $comparison, $logical);

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
     * @throws QueryException
     */
    public function having($field, $value, $comparison = '=', $logical = 'and')
    {
        $this->assertComparison($comparison);
        $this->assertLogical($logical);

        $fields = array();
        $values = array();

        if (!is_array($field) && is_array($value)) {
            $f = $this->resolveHavingField($field);
            foreach ($value as $i => $v) {
                $fields[$i] = $this->buildField($f['mapping'], $f['table']);
                $values[] = $v === null ? null : $this->bindValues($f['mapping'], $f['type'], $v);
            }
        } else {
            foreach ((array) $field as $i => $f) {
                $this->assertFieldName($f);

                $f = $this->resolveHavingField($f);
                $fields[] = $this->buildField($f['mapping'], $f['table']);

                if ($value === null || $value === array()) {
                    $values[] = null;
                } else {
                    $values[] = $this->bindValues($f['mapping'], $f['type'], is_array($value) ? $value[$i] : $value);
                }
            }
        }

        $this->having[] = array($fields, $values, $comparison, $logical);

        return $this;
    }

    /**
     * Asserts correct field name
     *
     * @param $field
     *
     * @throws QueryException
     */
    private function assertFieldName($field)
    {
        if (!is_scalar($field)) {
            throw new QueryException(sprintf('Expected field name, got "%s" in query "%s"', $this->getType($field), $this->model->entity()));
        }
    }

    /**
     * Resolves field name for having conditions
     *
     * @param string $field
     *
     * @return array
     */
    private function resolveHavingField($field)
    {
        foreach ($this->aggregates as $node) {
            if ($node[2] === $field) {
                return array(
                    'table' => null,
                    'mapping' => $field,
                    'type' => 'decimal'
                );
            }
        }

        $field = $this->resolveField($field);

        return array(
            'table' => $field->table(),
            'mapping' => $field->mapping(),
            'type' => $field->type()
        );
    }

    /**
     * Asserts correct comparison operator
     *
     * @param string $operator
     *
     * @throws QueryException
     */
    private function assertComparison($operator)
    {
        $comparisonOperators = array('=', '!=', '<', '<=', '>', '>=', 'like', 'regex');

        if (!in_array($operator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $operator, $this->model->entity()));
        }
    }

    /**
     * Asserts correct logical operation
     *
     * @param string $operator
     *
     * @throws QueryException
     */
    private function assertLogical($operator)
    {
        $comparisonOperators = array('or', 'and');

        if (!in_array($operator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $operator, $this->model->entity()));
        }
    }

    /**
     * Binds condition value to key
     *
     * @param $name
     * @param $type
     * @param $values
     *
     * @return array|string
     */
    private function bindValues($name, $type, $values)
    {
        if (!is_array($values)) {
            return $this->bind('condition', $name, $type, $values);
        }

        foreach ($values as $key => $value) {
            $values[$key] = $this->bindValues($name, $type, $value);
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
    public function order($field, $order = 'desc')
    {
        $this->assertOrder($order);

        $field = $this->resolveField($field);

        $this->assertOrder($order);

        if (is_array($order)) {
            foreach ($order as $i => &$o) {
                $order[$i] = $this->bind('order', $field->name(), $field->type(), (string) $o);
            }
        }

        $this->order[] = array(
            $field->mapping(),
            $order
        );

        return $this;
    }

    /**
     * Asserts correct order
     *
     * @param string $order
     *
     * @throws QueryException
     */
    private function assertOrder($order)
    {
        if (!is_array($order) && !in_array($order, array('asc', 'desc'))) {
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
                isset($conditions[$i]) ? $conditions[$i] : array(),
                isset($order[$i]) ? $order[$i] : array()
            );
        }

        return $this;
    }

    /**
     * Assigns relation to query
     *
     * @param string $relation
     * @param array  $conditions
     * @param array  $order
     *
     * @throws QueryException
     */
    private function assignRelation($relation, array $conditions = array(), array $order = array())
    {
        list($relation, $furtherRelations) = $this->splitRelationName($relation);

        $definition = $this->model->relation($relation);

        $query = new self($this->driver, $this->builder, $this->models);
        $query->operation('read', $definition->entity());

        $instance = $this->buildRelationInstance($relation, $definition, $query);

        foreach ($conditions as $node) {
            if (!is_array($node)) {
                throw new QueryException(sprintf('Invalid condition, must be an array, got %s', gettype($node)));
            }

            $instance->query()
                ->where($node[0], $node[1], isset($node[2]) ? $node[2] : '=', isset($node[3]) ? $node[3] : 'and');
        }

        foreach ($order as $node) {
            if (!is_array($node)) {
                throw new QueryException(sprintf('Invalid order, must be an array, got %s', gettype($node)));
            }

            $instance->query()
                ->order($node[0], isset($node[1]) ? $node[1] : 'desc');
        }

        if ($furtherRelations) {
            $instance->with($furtherRelations);
        }

        $this->relations[$relation] = $instance;
    }

    /**
     * Builds relation instance
     *
     * @param $relation
     * @param $definition
     * @param $query
     *
     * @return ManyRelation|ManyTroughRelation|OneRelation|OneTroughRelation
     * @throws QueryException
     */
    private function buildRelationInstance($relation, RelationDefinitionInterface $definition, $query)
    {
        switch ($definition->type()) {
            case 'one':
                return new OneRelation($query, $definition, $this->models);
            case 'many':
                return new ManyRelation($query, $definition, $this->models);
            case 'oneTrough':
                return new OneTroughRelation($query, $definition, $this->models);
            case 'manyTrough':
                return new ManyTroughRelation($query, $definition, $this->models);
            default:
                throw new QueryException(sprintf('Invalid relation type "%s" in relation "%s" for "%s"', $definition->type(), $relation, $this->model->entity()));
        }
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

    /**
     * Splits relation name
     *
     * @param string $relationName
     *
     * @return array
     */
    private function splitRelationName($relationName)
    {
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
            case 'num':
                $result = $this->executeNumber();
                break;
            case 'readOne':
                $result = $this->executeReadOne();
                break;
            case 'read':
                $result = $this->executeRead();
                break;
            case 'insert':
                $result = $this->executeInsert();
                break;
            case 'update':
                $result = $this->executeUpdate();
                break;
            case 'delete':
                $result = $this->executeDelete();
                break;
            case 'clear':
                $result = $this->executeClear();
                break;
            default:
                $result = false;
        }

        $this->reset();

        return $result;
    }

    /**
     * Executes counting operation
     *
     * @return int
     */
    private function executeNumber()
    {
        return $this->driver
            ->prepare($this->buildNumber())
            ->execute($this->binds)
            ->affectedRows();
    }

    /**
     * Builds counting query
     *
     * @return string
     */
    private function buildNumber()
    {
        $this->builder->reset()
            ->select($this->model->table());

        foreach ($this->fields as $field) {
            $this->builder->field($field[0], $field[1]);
        }

        foreach ($this->where as $node) {
            $this->builder->where($node[0], $node[1], $node[2], $node[3]);
        }

        return $this->builder->build();
    }

    /**
     * Executes reading one entity operation
     *
     * @return array|object
     * @throws QueryException
     */
    private function executeReadOne()
    {
        $result = $this->executeRead();

        if (!count($result)) {
            throw new QueryException(sprintf('Result out of range or does not exists for "%s"', $this->model->entity()));
        }

        return array_shift($result);
    }

    /**
     * Executes read operation
     *
     * @return array
     */
    private function executeRead()
    {
        $this->driver
            ->prepare($this->buildRead())
            ->execute($this->binds);

        $result = $this->driver->fetchAll($this->model->entity(), $this->casts);

        $this->executeReadRelations($result);

        return $result;
    }

    /**
     * Builds reading operation
     *
     * @return string
     */
    private function buildRead()
    {
        $this->builder->reset()
            ->select($this->model->table());

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

    /**
     * Executes insert operation
     *
     * @return array|object
     */
    private function executeInsert()
    {
        $result = $this->driver
            ->prepare($this->buildInsert())
            ->execute($this->binds)
            ->lastInsertId();

        $this->identifyEntity($this->instance, $result);

        $this->executeWriteRelations();

        return $this->instance;
    }

    /**
     * Builds inserting query
     *
     * @return string
     */
    private function buildInsert()
    {
        $this->builder->reset()
            ->insert($this->model->table());

        foreach ($this->values as $node) {
            $this->builder->value($node[0], $node[1]);
        }

        return $this->builder->build();
    }

    /**
     * Executes update operation
     *
     * @return array|object
     */
    private function executeUpdate()
    {
        $this->driver
            ->prepare($this->buildUpdate())
            ->execute($this->binds);

        $this->executeWriteRelations();

        return $this->instance;
    }

    /**
     * Builds updating query
     *
     * @return string
     */
    private function buildUpdate()
    {
        $this->builder->reset()
            ->update($this->model->table());

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

    /**
     * Executes deleting operation
     *
     * @return array|object
     */
    private function executeDelete()
    {
        $this->executeDeleteRelations();

        $this->driver
            ->prepare($this->buildDelete())
            ->execute($this->binds);

        $this->identifyEntity($this->instance, null);

        return $this->instance;
    }

    /**
     * Builds delete query
     *
     * @return string
     */
    private function buildDelete()
    {
        $this->builder->reset()
            ->delete($this->model->table());

        foreach ($this->where as $node) {
            $this->builder->where($node[0], $node[1], $node[2], $node[3]);
        }

        if ($this->limit) {
            $this->builder->limit($this->limit, $this->offset);
        }

        return $this->builder->build();
    }

    /**
     * Executes clearing operation
     *
     * @return bool
     */
    private function executeClear()
    {
        $this->executeClearRelations();

        $this->driver
            ->prepare($this->buildClear())
            ->execute();

        return true;
    }

    /**
     * Builds clear query
     *
     * @return string
     */
    private function buildClear()
    {
        return $this->builder->reset()
            ->clear($this->model->table())
            ->build();
    }

    /**
     * Executes reading relations
     *
     * @param $result
     */
    private function executeReadRelations(&$result)
    {
        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }
    }

    /**
     * Executes writing (insert/update) relations
     */
    private function executeWriteRelations()
    {
        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }
    }

    /**
     * Executes deleting relations
     */
    private function executeDeleteRelations()
    {
        foreach ($this->relations as $relation) {
            $relation->delete($this->instance);
        }
    }

    /**
     * Executes clearing relations
     */
    private function executeClearRelations()
    {
        foreach ($this->relations as $relation) {
            $relation->clear();
        }
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
    private function identifyEntity($entity, $identifier)
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
    private function accessProperty($entity, $field)
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
            case 'num':
                $queryString = $this->buildNumber();
                break;
            case 'readOne':
            case 'read':
                $queryString = $this->buildRead();
                break;
            case 'insert':
                $queryString = $this->buildInsert();
                break;
            case 'update':
                $queryString = $this->buildUpdate();
                break;
            case 'delete':
                $queryString = $this->buildDelete();
                break;
            case 'clear':
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