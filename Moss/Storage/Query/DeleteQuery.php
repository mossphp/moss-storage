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
use Moss\Storage\GetTypeTrait;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Accessor\Accessor;
use Moss\Storage\Query\Relation\RelationFactoryInterface;

/**
 * Query used to delete data from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class DeleteQuery extends AbstractQuery implements DeleteQueryInterface
{
    use GetTypeTrait;

    protected $instance;

    /**
     * Constructor
     *
     * @param Connection               $connection
     * @param mixed                    $entity
     * @param ModelInterface           $model
     * @param RelationFactoryInterface $factory
     */
    public function __construct(Connection $connection, $entity, ModelInterface $model, RelationFactoryInterface $factory)
    {
        $this->connection = $connection;
        $this->model = $model;
        $this->factory = $factory;
        $this->accessor = new Accessor();

        $this->assertEntityInstance($entity);
        $this->instance = $entity;

        $this->setQuery();
        $this->setPrimaryConditions();
    }

    /**
     * Sets query instance with delete operation and table
     */
    protected function setQuery()
    {
        $this->builder = $this->connection->createQueryBuilder();
        $this->builder->delete($this->connection->quoteIdentifier($this->model->table()));
    }

    /**
     * Assigns primary condition
     *
     * @throws QueryException
     */
    protected function setPrimaryConditions()
    {
        foreach ($this->model->primaryFields() as $field) {
            $value = $this->accessor->getPropertyValue($this->instance, $field->name());
            $this->builder->andWhere(
                sprintf(
                    '%s = %s',
                    $this->connection->quoteIdentifier($field->name()),
                    $this->bind('condition', $field->name(), $field->type(), $value)
                )
            );
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
        foreach ($this->relations as $relation) {
            $relation->delete($this->instance);
        }

        $this->builder->execute();
        $this->accessor->identifyEntity($this->model, $this->instance, null);

        return $this->instance;
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
        $this->resetBinds();

        $this->setQuery();
        $this->setPrimaryConditions();

        return $this;
    }
}
