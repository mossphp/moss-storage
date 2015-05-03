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
use Moss\Storage\Query\OperationTraits\AssertEntityTrait;
use Moss\Storage\Query\OperationTraits\IdentifyEntityTrait;
use Moss\Storage\Query\OperationTraits\PropertyAccessorTrait;
use Moss\Storage\Query\OperationTraits\RelationTrait;
use Moss\Storage\Query\Relation\RelationFactoryInterface;

/**
 * Query used to read data from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class WriteQuery extends AbstractQuery implements WriteQueryInterface
{
    use RelationTrait;
    use PropertyAccessorTrait;
    use IdentifyEntityTrait;
    use AssertEntityTrait;
    use GetTypeTrait;

    protected $instance;

    protected $values = [];

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

        $this->assertEntityInstance($entity);
        $this->instance = $entity;
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
        $this->values = [];

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->values[] = $field->name();
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->values[] = $this->model->field($field)
                ->name();
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
        $this->values[] = $this->model->field($field)
            ->name();

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
        $this->buildQuery()
            ->execute();

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    /**
     * Returns current query string
     *
     * @return string
     */
    public function queryString()
    {
        return $this->buildQuery()
            ->queryString();
    }

    /**
     * @return InsertQuery|UpdateQuery
     */
    protected function buildQuery()
    {
        if ($this->checkIfEntityExists()) {
            $query = new UpdateQuery($this->connection, $this->instance, $this->model, $this->factory);
        } else {
            $query = new InsertQuery($this->connection, $this->instance, $this->model, $this->factory);
        }

        $query->values($this->values);

        return $query;
    }

    /**
     * Returns true if entity exists database
     *
     * @return int
     */
    protected function checkIfEntityExists()
    {
        $query = new ReadQuery($this->connection, $this->model, $this->factory);

        foreach ($this->model->primaryFields() as $field) {
            $value = $this->getPropertyValue($this->instance, $field->name());

            if ($value === null) {
                return false;
            }

            $query->where($field->name(), $value, '=', 'and');
        }

        return $query->count() > 0;
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
        $this->values = [];
        $this->relations = [];

        return $this;
    }
}
