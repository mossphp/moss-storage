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
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;

/**
 * Abstract Entity Query
 * For queries that expect require entity instance
 * Ensures that entity is of expected type
 *
 * @package Moss\Storage\Query\OperationTraits
 */
abstract class AbstractEntityQuery extends AbstractQuery
{
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
        parent::__construct($connection, $model, $factory);
        $this->assignEntity($entity);
    }

    /**
     * Assigns entity instance
     * Asserts if entity instance is of expected type
     *
     * @param array|object $entity
     *
     * @throws QueryException
     */
    protected function assignEntity($entity)
    {
        $entityClass = $this->model->entity();

        if ($entity === null) {
            throw new QueryException(sprintf('Missing required entity of class "%s"', $entityClass));
        }

        if (!is_array($entity) && !$entity instanceof $entityClass) {
            throw new QueryException(sprintf('Entity must be an instance of "%s" or array got "%s"', $entityClass, $this->getType($entity)));
        }

        $this->instance = $entity;
    }

    /**
     * Assigns primary condition
     *
     * @throws QueryException
     */
    protected function setPrimaryKeyConditions()
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
}
