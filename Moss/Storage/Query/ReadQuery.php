<?php

/*
* This file is part of the moss-storage package
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
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Join\JoinFactory;
use Moss\Storage\Query\Join\JoinInterface;
use Moss\Storage\Query\Relation\RelationFactory;
use Moss\Storage\Query\Relation\RelationInterface;

class ReadQuery implements ReadInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var QueryBuilderInterface
     */
    protected $builder;

    /**
     * @var ModelBag
     */
    protected $models;

    /**
     * @var ModelInterface
     */
    protected $model;

    private $fields = [];
    private $aggregates = [];
    private $group = [];

    private $where = [];
    private $having = [];

    private $order = [];

    private $limit = null;
    private $offset = null;

    private $binds = [];
    private $casts = [];

    /**
     * @var JoinInterface[]
     */
    private $joins = [];

    /**
     * @var JoinFactory
     */
    private $joinFactory;

    /**
     * @var RelationInterface[]
     */
    private $relations = [];

    /**
     * @var RelationFactory
     */
    private $relationFactory;

    /**
     * Constructor
     *
     * @param DriverInterface       $driver
     * @param QueryBuilderInterface $builder
     * @param ModelBag              $models
     * @param string                $entity
     */
    public function __construct(DriverInterface $driver, QueryBuilderInterface $builder, ModelBag $models, $entity)
    {
        $this->driver = &$driver;
        $this->builder = &$builder;
        $this->models = &$models;

        $this->joinFactory = new JoinFactory($this->models);
        $this->relationFactory = new RelationFactory($this, $this->models);

        $this->model = $this->models->get($entity);

        $this->fields();
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
        $key = ':' . implode('_', [$operation, count($this->binds), $field]);
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
        $relation = $this->model->entity();
        if (strpos($field, QueryBuilderInterface::SEPARATOR) !== false) {
            list($relation, $field) = explode(QueryBuilderInterface::SEPARATOR, $field, 2);
        }
        $relation = trim($relation, '\\');

        if ($this->model->isNamed($relation) && $this->model->hasField($field)) {
            return $this->model->field($field);
        }

        foreach ($this->joins as $join) {
            if ($join->isNamed($relation)) {
                return $join->field($field);
            }
        }

        foreach ($this->aggregates as $node) {
            if ($node[2] === $field) {
                return [
                    'table' => null,
                    'mapping' => $field,
                    'type' => 'decimal'
                ];
            }
        }

        throw new QueryException(sprintf('Unable to access field "%s.%s" in query "%s" or in joined models', $relation, $field, $this->model->entity()));
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
    public function fields($fields = [])
    {
        $this->fields = [];
        $this->casts = [];

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
        $this->fields[] = [
            $this->buildField($field->name(), $field->table()),
            $field->name() == $field->mapping() ? null : $field->mapping()
        ];

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

        $this->aggregates[] = [
            $method,
            $this->buildField($field->mapping(), $field->table()),
            $alias
        ];

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
        $aggregateMethods = ['distinct', 'count', 'average', 'max', 'min', 'sum'];

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
     */
    public function join($type, $entity)
    {
        $this->joins[] = $this->joinFactory->create($this->model->entity(), $type, $entity);

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
     * @throws QueryException
     */
    public function where($field, $value, $comparison = '=', $logical = 'and')
    {
        $this->where[] = $this->condition($field, $value, $comparison, $logical);

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
        $this->having[] = $this->condition($field, $value, $comparison, $logical);

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
     * @throws QueryException
     */
    public function condition($field, $value, $comparison, $logical)
    {
        $this->assertComparison($comparison);
        $this->assertLogical($logical);

        if (!is_array($field)) {
            list($fields, $values) = $this->buildSingularFieldCondition($field, $value);
        } else {
            list($fields, $values) = $this->buildMultipleFieldsCondition($field, $value);
        }

        return [$fields, $values, $comparison, $logical];
    }

    /**
     * Builds condition for singular field
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $resultFields
     * @param array  $resultValues
     *
     * @return array
     */
    protected function buildSingularFieldCondition($field, $value, $resultFields = [], $resultValues = [])
    {
        $this->assertFieldName($field);
        $f = $this->resolveFieldAsArray($this->resolveField($field));

        if (!is_array($value)) {
            $resultFields[] = $this->buildField($f['mapping'], $f['table']);
            $resultValues[] = $value === null ? null : $this->bindValues($f['mapping'], $f['type'], $value);
        } else {
            foreach ($value as $i => $v) {
                $resultFields[$i] = $this->buildField($f['mapping'], $f['table']);
                $resultValues[] = $v === null ? null : $this->bindValues($f['mapping'], $f['type'], $v);
            }
        }

        return [
            $resultFields,
            $resultValues
        ];
    }

    /**
     * Builds conditions for multiple fields
     *
     * @param array $field
     * @param mixed $value
     * @param array $resultFields
     * @param array $resultValues
     *
     * @return array
     */
    protected function buildMultipleFieldsCondition($field, $value, $resultFields = [], $resultValues = [])
    {
        foreach ((array) $field as $i => $f) {
            $this->assertFieldName($f);
            $f = $this->resolveFieldAsArray($this->resolveField($f));

            $resultFields[] = $this->buildField($f['mapping'], $f['table']);
            if ($value === null || $value === []) {
                $resultValues[] = null;
            } else {
                $resultValues[] = $this->bindValues($f['mapping'], $f['type'], is_array($value) ? $value[$i] : $value);
            }
        }

        return [
            $resultFields,
            $resultValues
        ];
    }

    /**
     * Translates field into array
     *
     * @param mixed $field
     *
     * @return array
     */
    protected function resolveFieldAsArray($field)
    {
        if (is_array($field)) {
            return $field;
        }

        return [
            'table' => $field->table(),
            'mapping' => $field->mapping(),
            'type' => $field->type(),
        ];
    }

    /**
     * Asserts correct field name
     *
     * @param $field
     *
     * @throws QueryException
     */
    protected function assertFieldName($field)
    {
        if (!is_scalar($field)) {
            throw new QueryException(sprintf('Expected field name, got "%s" in query "%s"', gettype($field), $this->model->entity()));
        }
    }

    /**
     * Asserts correct comparison operator
     *
     * @param string $operator
     *
     * @throws QueryException
     */
    protected function assertComparison($operator)
    {
        $comparisonOperators = ['=', '!=', '<', '<=', '>', '>=', 'like', 'regex']; // TODO - get this from query builder

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
    protected function assertLogical($operator)
    {
        $comparisonOperators = ['or', 'and']; // TODO - get this from query builder

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
    protected function bindValues($name, $type, $values)
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

        $this->order[] = [
            $field->mapping(),
            $order
        ];

        return $this;
    }

    /**
     * Asserts correct order
     *
     * @param string|array $order
     *
     * @throws QueryException
     */
    private function assertOrder($order)
    {
        if (!is_array($order) && !in_array($order, ['asc', 'desc'])) {
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
    public function with($relation, array $conditions = [], array $order = [])
    {
        if (!$this->model) {
            throw new QueryException('Unable to create relation, missing entity model');
        }

        foreach ($this->relationFactory->create($this->model, $relation, $conditions, $order) as $rel) {
            $this->relations[] = $rel;
        }

        return $this;
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
        list($relation, $furtherRelations) = $this->relationFactory->splitRelationName($relation);

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
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute()
    {
        $this->driver
            ->prepare($this->queryString())
            ->execute($this->binds);

        $result = $this->driver->fetchAll($this->model->entity(), $this->casts);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
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
        $this->builder->reset();
        $this->builder->select($this->model->table());

        foreach ($this->joins as $join) {
            foreach ($join->joints() as $node) {
                $this->builder->join($node[0], $node[1], $node[2]);
            }

            foreach ($join->conditions() as $node) {
                $this->builder->where($node[0], $node[1], $node[2], $node[3]);
            }
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
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->joins = [];

        $this->fields = [];
        $this->aggregates = [];
        $this->group = [];

        $this->where = [];
        $this->having = [];

        $this->order = [];

        $this->limit = null;
        $this->offset = null;

        $this->binds = [];
        $this->casts = [];

        $this->driver->reset();
        $this->builder->reset();

        $this->relations = [];

        return $this;
    }
}
