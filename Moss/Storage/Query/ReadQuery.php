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
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Type;
use Moss\Storage\GetTypeTrait;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Accessor\AccessorInterface;
use Moss\Storage\Query\EventDispatcher\EventDispatcherInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;

/**
 * Query used to read data from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ReadQuery extends AbstractQuery implements ReadQueryInterface
{
    const EVENT_BEFORE = 'read.before';
    const EVENT_AFTER = 'read.after';

    use GetTypeTrait;

    protected $queryString;
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $casts = [];

    /**
     * Constructor
     *
     * @param Connection               $connection
     * @param ModelInterface           $model
     * @param RelationFactoryInterface $factory
     * @param AccessorInterface        $accessor
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Connection $connection, ModelInterface $model, RelationFactoryInterface $factory, AccessorInterface $accessor, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($connection, $model, $factory, $accessor, $dispatcher);

        $this->setQuery();
        $this->fields();
    }

    /**
     * Sets query instance with delete operation and table
     */
    protected function setQuery()
    {
        $this->builder = $this->connection->createQueryBuilder();
        $this->builder->select([]);
        $this->builder->from($this->connection->quoteIdentifier($this->model->table()));
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
        $this->builder->select([]);
        $this->casts = [];

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->assignField($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->assignField($this->model->field($field));
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
        $this->assignField($this->model->field($field));

        return $this;
    }

    /**
     * Assigns field to query
     *
     * @param FieldInterface $field
     */
    protected function assignField(FieldInterface $field)
    {
        if ($field->mapping() !== null) {
            $this->builder->addSelect(
                sprintf(
                    '%s AS %s',
                    $this->connection->quoteIdentifier($field->mapping()),
                    $this->connection->quoteIdentifier($field->name())
                )
            );
        } else {
            $this->builder->addSelect($this->connection->quoteIdentifier($field->name()));
        }

        $this->casts[$field->name()] = $field->type();
    }

    /**
     * Adds where condition to query
     *
     * @param string|array $field
     * @param mixed        $value
     * @param string       $comparison
     * @param string       $logical
     *
     * @return $this
     * @throws QueryException
     */
    public function where($field, $value, $comparison = '=', $logical = 'and')
    {
        $condition = $this->condition($field, $value, $comparison, $logical);

        if ($this->normalizeLogical($logical) === 'or') {
            $this->builder()->orWhere($condition);

            return $this;
        }

        $this->builder()->andWhere($condition);

        return $this;
    }

    /**
     * Adds where condition to query
     *
     * @param string|array $field
     * @param mixed        $value
     * @param string       $comparison
     * @param string       $logical
     *
     * @return string
     * @throws QueryException
     */
    public function condition($field, $value, $comparison, $logical)
    {
        $comparison = $this->normalizeComparison($comparison);
        $logical = $this->normalizeLogical($logical);

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
        $field = $this->model()->field($field);

        return $this->buildConditionString(
            $this->connection()->quoteIdentifier($field->mappedName()),
            $value === null ? null : $this->bindValues($field->mappedName(), $field->type(), $value),
            $comparison
        );
    }

    /**
     * Builds conditions for multiple fields
     *
     * @param array  $fields
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return array
     */
    protected function buildMultipleFieldsCondition($fields, $value, $comparison, $logical)
    {
        $conditions = [];
        foreach ((array) $fields as $field) {
            $field = $this->model()->field($field);

            $fieldName = $field->mappedName();
            $conditions[] = $this->buildConditionString(
                $this->connection()->quoteIdentifier($fieldName),
                $value === null ? null : $this->bindValues($fieldName, $field->type(), $value),
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

            $logical = $operator === '!=' ? ' and ' : ' or ';

            return '(' . implode($logical, $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        if ($operator === 'regexp') {
            return sprintf('%s regexp %s', $field, $bind);
        }

        return $field . ' ' . $operator . ' ' . $bind;
    }

    /**
     * Asserts correct comparison operator
     *
     * @param string $operator
     *
     * @return string
     * @throws QueryException
     */
    protected function normalizeComparison($operator)
    {
        switch (strtolower($operator)) {
            case '<':
            case 'lt':
                return '<';
            case '<=':
            case 'lte':
                return '<=';
            case '>':
            case 'gt':
                return '>';
            case '>=':
            case 'gte':
                return '>=';
            case '~':
            case '~=':
            case '=~':
            case 'regex':
            case 'regexp':
                return "regexp";
            // LIKE
            case 'like':
                return "like";
            case '||':
            case 'fulltext':
            case 'fulltext_boolean':
                return 'fulltext';
            case '<>':
            case '!=':
            case 'ne':
            case 'not':
                return '!=';
            case '=':
            case 'eq':
                return '=';
            default:
                throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $operator, $this->model()->entity()));
        }
    }

    /**
     * Asserts correct logical operation
     *
     * @param string $operator
     *
     * @return string
     * @throws QueryException
     */
    protected function normalizeLogical($operator)
    {
        switch (strtolower($operator)) {
            case '&&':
            case 'and':
                return 'and';
            case '||':
            case 'or':
                return 'or';
            default:
                throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $operator, $this->model()->entity()));
        }
    }

    /**
     * Binds condition value to key
     *
     * @param string $name
     * @param string $type
     * @param mixed  $values
     *
     * @return string
     */
    protected function bindValues($name, $type, $values)
    {
        if (!is_array($values)) {
            return $this->builder->createNamedParameter($values, $type);
        }

        foreach ($values as $key => $value) {
            $values[$key] = $this->bindValues($name, $type, $value);
        }

        return $values;
    }

    /**
     * Adds sorting to query
     *
     * @param string $field
     * @param string $order
     *
     * @return $this
     */
    public function order($field, $order = 'desc')
    {
        $field = $this->model->field($field);
        $order = $this->normalizeOrder($order);

        $this->builder->addOrderBy($this->connection->quoteIdentifier($field->mappedName()), $order);

        return $this;
    }

    /**
     * Asserts correct order
     *
     * @param string $order
     *
     * @return string
     * @throws QueryException
     */
    protected function normalizeOrder($order)
    {
        switch (strtolower($order)) {
            case 'asc':
                return 'asc';
            case 'desc':
                return 'desc';
            default:
                throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', $this->getType($order), $this->model->entity()));

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
        if ($offset !== null) {
            $this->builder->setFirstResult((int) $offset);
        }

        $this->builder->setMaxResults((int) $limit);

        return $this;
    }

    /**
     * Returns number of entities that will be read
     *
     * @return int
     */
    public function count()
    {
        if (empty($this->queryString)) {
            $builder = clone $this->builder;
            $builder->resetQueryPart('orderBy');
            $stmt = $builder->execute();
        } else {
            $stmt = $this->connection->executeQuery($this->queryString, $this->queryParams);
        }

        return (int) $stmt->rowCount();
    }

    /**
     * Sets custom query to be executed instead of one based on entity structure
     *
     * @param string $query
     * @param array  $params
     *
     * @return $this
     */
    public function query($query, array $params = [])
    {
        $this->queryString = $query;
        $this->queryParams = $params;

        return $this;
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute()
    {
        $this->dispatcher->fire(self::EVENT_BEFORE);

        $stmt = $this->executeQuery();
        $result = $this->model->entity() ? $this->fetchAsObject($stmt) : $this->fetchAsAssoc($stmt);

        $this->dispatcher->fire(self::EVENT_AFTER, $result);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }


        return $result;
    }

    /**
     * Executes query - from builder or custom
     *
     * @return Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executeQuery()
    {
        if (empty($this->queryString)) {
            return $this->builder->execute();
        }

        return $this->connection->executeQuery($this->queryString, $this->queryParams);
    }

    /**
     * Fetches result as associative array, mostly for pivot tables
     *
     * @param Statement $stmt
     *
     * @return array
     */
    protected function fetchAsAssoc(Statement $stmt)
    {
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches result as entity object
     *
     * @param Statement $stmt
     *
     * @return array
     */
    protected function fetchAsObject(Statement $stmt)
    {
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $this->model->entity());
        $result = $stmt->fetchAll();

        $ref = new \ReflectionClass($this->model->entity());
        foreach ($result as $entity) {
            $this->restoreObject($entity, $this->casts, $ref);
        }

        return $result;
    }

    /**
     * Restores entity values from their stored representation
     *
     * @param object           $entity
     * @param array            $restore
     * @param \ReflectionClass $ref
     *
     * @return mixed
     */
    protected function restoreObject($entity, array $restore, \ReflectionClass $ref)
    {
        foreach ($restore as $field => $type) {
            if (is_array($entity)) {
                if (!isset($entity[$field])) {
                    continue;
                }

                $entity[$field] = $this->convertToPHPValue($entity[$field], $type);
                continue;
            }

            if (!$ref->hasProperty($field)) {
                if (!isset($entity->$field)) {
                    continue;
                }

                $entity->$field = $this->convertToPHPValue($entity->$field, $type);
                continue;
            }

            $prop = $ref->getProperty($field);
            $prop->setAccessible(true);

            $value = $prop->getValue($entity);
            $value = $this->convertToPHPValue($value, $type);
            $prop->setValue($entity, $value);
        }

        return $entity;
    }

    /**
     * Converts read value to its php representation
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function convertToPHPValue($value, $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->connection->getDatabasePlatform());
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->builder->resetQueryParts();
        $this->relations = [];
        $this->queryString = null;
        $this->queryParams = [];
        $this->casts = [];
        $this->resetBinds();

        $this->setQuery();
        $this->fields();

        return $this;
    }
}
