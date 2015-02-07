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
class CountQuery extends AbstractConditionalQuery implements CountQueryInterface
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
        $this->setPrimaryFields();
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
     * Adds primary fields for read
     *
     * @throws QueryException
     */
    protected function setPrimaryFields()
    {
        $this->query->select([]);
        foreach ($this->model->primaryFields() as $field) {
            $this->query->addSelect($this->connection->quoteIdentifier($field->mapping() ? $field->mapping() : $field->name()));
        }
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
     * Adds where condition to query
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
     * Adds having condition to query
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
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute()
    {
        $stmt = $this->connection->prepare($this->queryString());
        $stmt->execute($this->binds);

        return $stmt->rowCount();
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
        $this->setPrimaryFields();

        return $this;
    }
}
