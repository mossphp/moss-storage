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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Converter\ConverterInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;
use Moss\Storage\Query\Relation\RelationInterface;


/**
 * Query used to delete data from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class DeleteQuery implements DeleteInterface
{

    const COMPARISON_EQUAL = '=';
    const COMPARISON_NOT_EQUAL = '!=';
    const COMPARISON_LESS = '<';
    const COMPARISON_LESS_OR_EQUAL = '<=';
    const COMPARISON_GREATER = '>';
    const COMPARISON_GREATER_OR_EQUAL = '>=';
    const COMPARISON_LIKE = 'like';
    const COMPARISON_REGEXP = 'regexp';

    const LOGICAL_AND = 'and';
    const LOGICAL_OR = 'or';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ModelInterface
     */
    protected $model;

    /**
     * @var ConverterInterface
     */
    protected $converter;

    /**
     * @var RelationFactoryInterface
     */
    protected $factory;

    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * @var array|object
     */
    protected $instance;

    /**
     * @var array|RelationInterface[]
     */
    protected $relations = [];

    /**
     * @var array
     */
    protected $binds = [];

    /**
     * Constructor
     *
     * @param Connection               $connection
     * @param mixed                    $entity
     * @param ModelInterface           $model
     * @param ConverterInterface       $converter
     * @param RelationFactoryInterface $factory
     */
    public function __construct(Connection $connection, $entity, ModelInterface $model, ConverterInterface $converter, RelationFactoryInterface $factory)
    {
        $this->connection = $connection;
        $this->model = $model;
        $this->converter = $converter;
        $this->factory = $factory;

        $this->assertEntityInstance($entity);
        $this->instance = $entity;

        $this->setQuery();
        $this->setPrimaryConditions();
    }

    /**
     * Asserts entity instance
     *
     * @param array|object $entity
     *
     * @throws QueryException
     */
    protected function assertEntityInstance($entity)
    {
        $entityClass = $this->model->entity();

        if ($entity === null) {
            throw new QueryException(sprintf('Missing required entity for deleting class "%s"', $entityClass));
        }

        if (!is_array($entity) && !$entity instanceof $entityClass) {
            throw new QueryException(sprintf('Entity for deleting must be an instance of "%s" or array got "%s"', $entityClass, is_object($entity) ? get_class($entity) : gettype($entity)));
        }
    }

    /**
     * Sets query instance with delete operation and table
     */
    protected function setQuery()
    {
        $this->query = $this->connection->createQueryBuilder();
        $this->query->delete($this->connection->quoteIdentifier($this->model->table()));
    }

    /**
     * Assigns primary condition
     *
     * @throws QueryException
     */
    protected function setPrimaryConditions() {
        foreach ($this->model->primaryFields() as $field) {
            $value = $this->getPropertyValue($this->instance, $field->name());
            $this->where($field->name(), $value, self::COMPARISON_EQUAL, self::LOGICAL_AND);
        }
    }

    /**
     * Returns property value
     *
     * @param null|array|object $entity
     * @param string            $field
     *
     * @return mixed
     * @throws QueryException
     */
    protected function getPropertyValue($entity, $field)
    {
        if (!$entity) {
            throw new QueryException('Unable to access entity properties, missing instance');
        }

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
     * Returns connection
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
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
    public function where($field, $value, $comparison = self::COMPARISON_EQUAL, $logical = self::LOGICAL_AND)
    {
        $condition = $this->condition($field, $value, $comparison, $logical);

        if ($logical === self::LOGICAL_OR) {
            $this->query->orWhere($condition);

            return $this;
        }

        $this->query->andWhere($condition);

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
        $comparison = strtolower($comparison);
        $logical = strtolower($logical);

        $this->assertComparison($comparison);
        $this->assertLogical($logical);

        if (!is_array($field)) {
            return $this->buildSingularFieldCondition($field, $value, $comparison);
        }

        return $this->buildMultipleFieldsCondition($field, $value, $comparison, $logical);
    }

    /**
     * Builds condition for singular field
     *
     * @param string $field
     * @param mixed  $value
     * @param string $comparison
     *
     * @return array
     */
    protected function buildSingularFieldCondition($field, $value, $comparison)
    {
        $f = $this->model->field($field);

        $fieldName = $f->mapping() ? $f->mapping() : $f->name();

        return $this->buildConditionString(
            $this->connection->quoteIdentifier($fieldName),
            $value === null ? null : $this->bindValues($fieldName, $f->type(), $value),
            $comparison
        );
    }

    /**
     * Builds conditions for multiple fields
     *
     * @param array  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return array
     */
    protected function buildMultipleFieldsCondition($field, $value, $comparison, $logical)
    {
        $conditions = [];
        foreach ((array) $field as $i => $f) {
            $f = $this->model->field($f);

            $fieldName = $f->mapping() ? $f->mapping() : $f->name();
            $conditions[] = $this->buildConditionString(
                $this->connection->quoteIdentifier($fieldName),
                $value === null ? null : $this->bindValues($fieldName, $f->type(), $value),
                $comparison
            );

            $conditions[] = $logical;
        }

        array_pop($conditions);

        return '(' . implode(' ', $conditions) . ')';
    }

    /**
     * Builds condition string
     *
     * @param string       $field
     * @param string|array $bind
     * @param string       $operator
     *
     * @return string
     */
    protected function buildConditionString($field, $bind, $operator)
    {
        if (is_array($bind)) {
            foreach ($bind as &$val) {
                $val = $this->buildConditionString($field, $val, $operator);
                unset($val);
            }

            $operator = $operator === self::COMPARISON_NOT_EQUAL ? 'and' : 'or';

            return '(' . implode(sprintf(' %s ', $operator), $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        if ($operator === self::COMPARISON_REGEXP) {
            return sprintf('%s regexp %s', $field, $bind);
        }

        return $field . ' ' . $operator . ' ' . $bind;
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
        $comparisonOperators = [
            self::COMPARISON_EQUAL,
            self::COMPARISON_NOT_EQUAL,
            self::COMPARISON_LESS,
            self::COMPARISON_LESS_OR_EQUAL,
            self::COMPARISON_GREATER,
            self::COMPARISON_GREATER_OR_EQUAL,
            self::COMPARISON_LIKE,
            self::COMPARISON_REGEXP
        ];

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
        $comparisonOperators = [
            self::LOGICAL_AND,
            self::LOGICAL_OR
        ];

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
        $this->binds[$key] = $this->converter->store($value, $type);

        return $key;
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
        if ($offset) {
            $this->query->setFirstResult((int) $offset);
        }

        $this->query->setMaxResults((int) $limit);

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
        $instance = $this->factory->create($this->model, $relation, $conditions, $order);
        $this->relations[$instance->name()] = $instance;

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
        list($relation, $furtherRelations) = $this->factory->splitRelationName($relation);

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
        foreach ($this->relations as $relation) {
            $relation->delete($this->instance);
        }

        $this->connection
            ->prepare($this->queryString())
            ->execute($this->binds);

        $this->identifyEntity($this->instance, null);

        return $this->instance;
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

        $field = reset($primaryKeys)->name();

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
     * Returns current query string
     *
     * @return string
     */
    public function queryString()
    {
        return (string) $this->query->getSQL();
    }

    /**
     * Returns array with bound values and their placeholders as keys
     *
     * @return array
     */
    public function binds()
    {
        return $this->binds;
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->query->resetQueryParts();
        $this->relations = [];
        $this->binds = [];

        $this->setQuery();
        $this->setPrimaryConditions();

        return $this;
    }
}
