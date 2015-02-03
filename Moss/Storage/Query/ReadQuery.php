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
use Moss\Storage\Converter\ConverterInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;


/**
 * Query used to read data from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ReadQuery extends AbstractConditionalQuery implements ReadQueryInterface
{
    const AGGREGATE_DISTINCT = 'distinct';
    const AGGREGATE_COUNT = 'count';
    const AGGREGATE_AVERAGE = 'average';
    const AGGREGATE_MAX = 'max';
    const AGGREGATE_MIN = 'min';
    const AGGREGATE_SUM = 'sum';

    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    /**
     * @var array
     */
    protected $casts = [];

    /**
     * Constructor
     *
     * @param Connection               $connection
     * @param ModelInterface           $model
     * @param ConverterInterface       $converter
     * @param RelationFactoryInterface $factory
     */
    public function __construct(Connection $connection, ModelInterface $model, ConverterInterface $converter, RelationFactoryInterface $factory)
    {
        $this->connection = $connection;
        $this->model = $model;
        $this->converter = $converter;
        $this->factory = $factory;

        $this->setQuery();
        $this->fields();
    }

    /**
     * Sets query instance with delete operation and table
     */
    protected function setQuery()
    {
        $this->query = $this->connection->createQueryBuilder();
        $this->query->select();
        $this->query->from($this->connection->quoteIdentifier($this->model->table()));
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
     * Sets field names which will be read
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields($fields = [])
    {
        $this->query->select([]);
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
        if ($field->mapping()) {
            $this->query->addSelect(
                sprintf(
                    '%s AS %s',
                    $this->connection->quoteIdentifier($field->mapping()),
                    $this->connection->quoteIdentifier($field->name())
                )
            );
        } else {
            $this->query->addSelect($this->connection->quoteIdentifier($field->name()));
        }

        $this->casts[$field->name()] = $field->type();
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
        $this->aggregate(self::AGGREGATE_DISTINCT, $field, $alias);

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
        $this->aggregate(self::AGGREGATE_COUNT, $field, $alias);

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
        $this->aggregate(self::AGGREGATE_AVERAGE, $field, $alias);

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
        $this->aggregate(self::AGGREGATE_MAX, $field, $alias);

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
        $this->aggregate(self::AGGREGATE_MIN, $field, $alias);

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
        $this->aggregate(self::AGGREGATE_SUM, $field, $alias);

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

        $field = $this->model->field($field);
        $alias = $alias ?: strtolower($method);

        $this->query->addSelect(
            sprintf(
                '%s(%s) AS %s',
                $method,
                $this->connection->quoteIdentifier($field->mapping() ? $field->mapping() : $field->name()),
                $this->connection->quoteIdentifier($alias)
            )
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
    protected function assertAggregate($method)
    {
        $aggregateMethods = [
            self::AGGREGATE_DISTINCT,
            self::AGGREGATE_COUNT,
            self::AGGREGATE_AVERAGE,
            self::AGGREGATE_MIN,
            self::AGGREGATE_MAX,
            self::AGGREGATE_SUM
        ];

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
        $field = $this->model->field($field);

        $this->query->addGroupBy($this->connection->quoteIdentifier($field->mapping() ? $field->mapping() : $field->name()));

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
     * Adds having condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function having($field, $value, $comparison = '==', $logical = 'and')
    {
        $condition = $this->condition($field, $value, $comparison, $logical);

        if ($logical === self::LOGICAL_OR) {
            $this->query->orHaving($condition);

            return $this;
        }

        $this->query->andHaving($condition);

        return $this;
    }

    /**
     * Adds sorting to query
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = self::ORDER_DESC)
    {
        $field = $this->model->field($field);

        $this->assertOrder($order);

        $field = $field->mapping() ? $field->mapping() : $field->name();
        $this->query->addOrderBy($this->connection->quoteIdentifier($field), $order);

        return $this;
    }

    /**
     * Asserts correct order
     *
     * @param string|array $order
     *
     * @throws QueryException
     */
    protected function assertOrder($order)
    {
        if (!in_array($order, [self::ORDER_ASC, self::ORDER_DESC])) {
            throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', is_scalar($order) ? $order : gettype($order), $this->model->entity()));
        }
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute()
    {
        $stmt = $this->connection->prepare($this->queryString());
        $stmt->execute($this->binds);

        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, $this->model->entity());

        $ref = new \ReflectionClass($this->model->entity());
        foreach ($result as $entity) {
            $this->restoreObject($entity, $this->casts, $ref);
        }

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
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

                $entity[$field] = $this->converter->restore($entity[$field], $type);
                continue;
            }

            if (!$ref->hasProperty($field)) {
                if (!isset($entity->$field)) {
                    continue;
                }

                $entity->$field = $this->converter->restore($entity->$field, $type);
                continue;
            }

            $prop = $ref->getProperty($field);
            $prop->setAccessible(true);

            $value = $prop->getValue($entity);
            $value = $this->converter->restore($value, $type);
            $prop->setValue($entity, $value);
        }

        return $entity;
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
        $this->casts = [];
        $this->binds = [];

        $this->setQuery();
        $this->fields();

        return $this;
    }
}
