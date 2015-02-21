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
class UpdateQuery extends AbstractConditionalQuery implements UpdateQueryInterface
{
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
        $this->values();
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
            throw new QueryException(sprintf('Missing required entity for updating class "%s"', $entityClass));
        }

        if (!is_array($entity) && !$entity instanceof $entityClass) {
            throw new QueryException(sprintf('Entity for updating must be an instance of "%s" or array got "%s"', $entityClass, $this->getType($entity)));
        }
    }

    /**
     * Sets query instance with delete operation and table
     */
    protected function setQuery()
    {
        $this->query = $this->connection->createQueryBuilder();
        $this->query->update($this->connection->quoteIdentifier($this->model->table()));
    }

    /**
     * Assigns primary condition
     *
     * @throws QueryException
     */
    protected function setPrimaryConditions()
    {
        foreach ($this->model->primaryFields() as $field) {
            $value = $this->getPropertyValue($this->instance, $field->name());
            $this->where($field->name(), $value, self::COMPARISON_EQUAL, self::LOGICAL_AND);
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
     * Sets field names which values will be written
     *
     * @param array $fields
     *
     * @return $this
     */
    public function values($fields = [])
    {
        $this->query->resetQueryPart('set');
        $this->resetBinds('value');

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->assignValue($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->assignValue($this->model->field($field));
        }

        return $this;
    }

    /**
     * Adds field which value will be written
     *
     * @param string $field
     *
     * @return $this
     */
    public function value($field)
    {
        $this->assignValue($this->model->field($field));

        return $this;
    }

    /**
     * Assigns value to query
     *
     * @param FieldInterface $field
     */
    protected function assignValue(FieldInterface $field)
    {
        $value = $this->getPropertyValue($this->instance, $field->name());
        if ($value === null) {
            $references = $this->model->referredIn($field->name());
            foreach ($references as $foreign => $reference) {
                $entity = $this->getPropertyValue($this->instance, $reference->container());
                if ($entity === null) {
                    continue;
                }

                $value = $this->getPropertyValue($entity, $foreign);
                $this->setPropertyValue($this->instance, $field->name(), $value);
                break;
            }
        }

        $this->query->set(
            $this->connection->quoteIdentifier($field->mapping() ? $field->mapping() : $field->name()),
            $this->bind('value', $field->name(), $field->type(), $value)
        );
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
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute()
    {
        $this->connection
            ->prepare($this->queryString())
            ->execute($this->binds);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
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
        $this->resetBinds();

        $this->setQuery();
        $this->values();
        $this->setPrimaryConditions();

        return $this;
    }
}
