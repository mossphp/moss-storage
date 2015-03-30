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
     * @param RelationFactoryInterface $factory
     */
    public function __construct(Connection $connection, ModelInterface $model, RelationFactoryInterface $factory)
    {
        $this->connection = $connection;
        $this->model = $model;
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
        if ($field->mapping() !== null) {
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
     * Adds sorting to query
     *
     * @param string $field
     * @param string $order
     *
     * @return $this
     */
    public function order($field, $order = self::ORDER_DESC)
    {
        $field = $this->model->field($field);

        $this->assertOrder($order);

        $this->query->addOrderBy($this->connection->quoteIdentifier($field->mappedName()), $order);

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
            throw new QueryException(sprintf('Unsupported sorting method "%s" in query "%s"', $this->getType($order), $this->model->entity()));
        }
    }

    /**
     * Adds relation to query with optional conditions and sorting (as key value pairs)
     *
     * @param string|array $relation
     * @param array        $conditions
     * @param array        $order
     * @param int          $limit
     * @param int          $offset
     *
     * @return $this
     */
    public function with($relation, array $conditions = [], array $order = [], $limit = null, $offset = null)
    {
        foreach ((array) $relation as $node) {
            $this->assignRelation($node, $conditions, $order, $limit, $offset);
        }

        return $this;
    }

    /**
     * Adds relation to query
     *
     * @param string $relation
     * @param array  $conditions
     * @param array  $order
     * @param int    $limit
     * @param int    $offset
     */
    protected function assignRelation($relation, array $conditions, array $order, $limit, $offset)
    {
        $this->factory->reset();
        $this->factory->relation($this->model, $relation);

        foreach ($conditions as $condition) {
            $condition = $this->applyDefaults($condition, [null, null, '=', 'and']);
            $this->factory->where($condition[0], $condition[1], $condition[2], $condition[3]);
        }

        if (!empty($order)) {
            $order = $this->applyDefaults($order, [null, 'asc']);
            $this->factory->order($order[0], $order[1]);
        }

        if ($limit !== null || $offset !== null) {
            $this->factory->limit($limit, $offset);
        }

        $instance = $this->factory->build();

        $this->relations[$instance->name()] = $instance;
    }

    /**
     * Applies default values for missing keys in array
     *
     * @param array $array
     * @param array $defaults
     *
     * @return array
     */
    public function applyDefaults(array $array, array $defaults = [])
    {
        foreach ($defaults as $key => $value) {
            $array[$key] = array_key_exists($key, $array) ? $array[$key] : $value;
        }

        return $array;
    }

    /**
     * Returns number of entities that will be read
     *
     * @return int
     */
    public function count()
    {
        $stmt = $this->bindAndExecuteQuery();

        return (int) $stmt->rowCount();
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute()
    {
        $stmt = $this->bindAndExecuteQuery();
        $result = $this->model->entity() ? $this->fetchAsObject($stmt) : $this->fetchAsAssoc($stmt);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }

        return $result;
    }

    /**
     * Fetches result as associative array, mostly for pivot tables
     *
     * @param Statement $stmt
     *
     * @return array
     */
    protected function fetchAsAssoc(Statement $stmt) {
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
        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, $this->model->entity());

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
        return Type::getType($type)
            ->convertToPHPValue($value, $this->connection->getDatabasePlatform());
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
        $this->resetBinds();

        $this->setQuery();
        $this->fields();

        return $this;
    }
}
