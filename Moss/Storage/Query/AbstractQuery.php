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
use Moss\Storage\Converter\ConverterInterface;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;
use Moss\Storage\Query\Relation\RelationInterface;

/**
 * Abstract base class with common methods
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class AbstractQuery
{
    use PropertyAccessorTrait;

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
     * Returns connection
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
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

        $this->setPropertyValue($entity, $field, $identifier);
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
     * Returns array with bound values and their placeholders as keys
     *
     * @return array
     */
    public function binds()
    {
        return $this->binds;
    }
}
