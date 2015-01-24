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
 * Query used to clear table form any data
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ClearQuery implements ClearInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ModelInterface
     */
    protected $model;

    /**
     * @var RelationFactoryInterface
     */
    protected $factory;

    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * @var array|RelationInterface[]
     */
    protected $relations = [];

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
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute()
    {
        foreach ($this->relations as $relation) {
            $relation->clear();
        }

        $this->connection
            ->prepare($this->queryString())
            ->execute();

        return true;
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
        return [];
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

        $this->setQuery();

        return $this;
    }


}
