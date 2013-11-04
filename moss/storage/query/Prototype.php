<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 04.11.13
 * Time: 09:39
 */

namespace moss\storage\query;

use moss\storage\builder\BuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelInterface;
use moss\storage\query\relation\RelationInterface;

class Prototype
{
    /** @var DriverInterface */
    protected $driver;

    /** @var BuilderInterface */
    protected $builder;

    /** @var ModelInterface */
    protected $model;

    /** @var \ReflectionObject */
    protected $reflection;

    protected $instance = null;

    protected $operation = null;

    /** @var RelationInterface[] */
    protected $relations = array();

    protected $binds = array();
    protected $casts = array();

    /**
     * Returns adapter instance used in query
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns builder instance used in query
     *
     * @return BuilderInterface
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Returns model instance used in query
     *
     * @return ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Binds value to unique key and returns it
     *
     * @param string $operation
     * @param string $field
     * @param mixed  $value
     *
     * @return string
     */
    protected function bind($operation, $field, $value)
    {
        $key = ':' . implode('_', array($operation, count($this->binds), $field));
        $type = $this->model
            ->field($field)
            ->type();

        $this->binds[$key] = $this->driver->cast($value, $type);

        return $key;
    }

    /**
     * Adds relation to query
     *
     * @param RelationInterface $relation
     *
     * @return $this
     */
    public function setRelation(RelationInterface $relation)
    {
        $this->relations[$relation->name()] = $relation;

        return $this;
    }

    /**
     * Returns relation in query
     *
     * @param string $relationName
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function getRelation($relationName)
    {
        if (!isset($this->relations[$relationName])) {
            throw new QueryException(sprintf('Relation "%s" not found in query "%s"', $relationName, $this->model->entity()));
        }

        return $this->relations[$relationName];
    }

    /**
     * Assigns passed identifier to primary key
     * Possible only when entity has one primary key
     *
     * @param int|string $identifier
     *
     * @return void
     */
    protected function identifyEntity($identifier)
    {
        $primaryKeys = $this->model->primaryFields();
        if (count($primaryKeys) !== 1) {
            return;
        }

        $field = reset($primaryKeys);

        if (!$this->reflection->hasProperty($field)) {
            $this->instance->$field = $identifier;

            return;
        }

        $prop = $this->reflection->getProperty($field);
        $prop->setAccessible(true);
        $prop->setValue($this->instance, $identifier);
    }

    /**
     * Returns property value
     *
     * @param object $entity
     * @param string $field
     *
     * @return mixed
     */
    protected function accessProperty($entity, $field)
    {
        if (!$this->reflection->hasProperty($field)) {
            return null;
        }

        $prop = $this->reflection->getProperty($field);
        $prop->setAccessible(true);

        return $prop->getValue($entity);
    }
} 