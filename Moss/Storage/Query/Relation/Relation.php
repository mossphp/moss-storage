<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Query\Relation;

use Moss\Storage\Model\ModelBag;
use Moss\Storage\Query\QueryInterface;
use Moss\Storage\Model\Definition\RelationInterface as RelationDefinitionInterface;

/**
 * Abstract class for basic relation methods
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Query
 */
abstract class Relation implements RelationInterface
{

    /** @var QueryInterface */
    protected $query;

    /** @var ModelBag */
    protected $models;

    /** @var RelationDefinitionInterface */
    protected $relation;

    /**
     * Constructor
     *
     * @param QueryInterface              $query
     * @param RelationDefinitionInterface $relation
     * @param ModelBag                    $models
     */
    public function __construct(QueryInterface $query, RelationDefinitionInterface $relation, ModelBag $models)
    {
        $this->query = & $query;
        $this->relation = & $relation;
        $this->models = & $models;
    }

    /**
     * Returns relation name
     *
     * @return string
     */
    public function name()
    {
        return $this->relation->name();
    }

    /**
     * Returns relation query instance
     *
     * @return QueryInterface
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Adds sub relation
     *
     * @param string $relation
     *
     * @return $this
     */
    public function with($relation)
    {
        $this->query()
            ->with($relation);

        return $this;
    }

    /**
     * Returns sub relation instance
     *
     * @param string $relation
     *
     * @return QueryInterface
     */
    public function relation($relation)
    {
        return $this->query()
            ->relation($relation);
    }

    /**
     * Returns var type
     *
     * @param mixed $var
     *
     * @return string
     */
    private function getType($var)
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }

    /**
     * Throws exception when entity is not required instance
     *
     * @param mixed $entity
     *
     * @return bool
     * @throws RelationException
     */
    protected function assertInstance($entity)
    {
        $entityClass = $this->relation->entity();
        if (!$entity instanceof $entityClass) {
            throw new RelationException(sprintf('Relation table must be instance of %s, got %s', $entityClass, $this->getType($entity)));
        }

        return true;
    }

    /**
     * Checks if entity fits to relation requirements
     *
     * @param mixed $entity
     *
     * @return bool
     */
    protected function assertEntity($entity)
    {
        foreach ($this->relation->localValues() as $local => $value) {
            if ($this->accessProperty($entity, $local) != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if container has array access
     *
     * @param $container
     *
     * @throws RelationException
     */
    protected function assertArrayAccess($container)
    {
        if (!$container instanceof \ArrayAccess && !is_array($container)) {
            throw new RelationException(sprintf('Relation container must be array or instance of ArrayAccess, got %s', $this->getType($container)));
        }
    }

    /**
     * Fetches collection of entities matching set conditions
     *
     * @param string $entity
     * @param array  $conditions
     * @param bool   $reset
     *
     * @return array
     */
    protected function fetch($entity, array $conditions, $reset = false)
    {
        $query = clone $this->query;

        if ($reset) {
            $query->reset();
        }

        $query->operation(QueryInterface::OPERATION_READ, $entity);

        foreach ($conditions as $field => $values) {
            $query->where($field, $values);
        }

        return $query->execute();
    }

    /**
     * Removes obsolete entities that match conditions but don't exist in collection
     *
     * @param string $entity
     * @param array  $collection
     * @param array  $conditions
     */
    protected function cleanup($entity, array $collection, array $conditions)
    {
        if (empty($collection) || empty($conditions)) {
            return;
        }

        $existing = $this->fetch($entity, $conditions);

        if (empty($existing)) {
            return;
        }

        $identifiers = array();
        foreach ($collection as $instance) {
            $identifiers[] = $this->identifyEntity($entity, $instance);
        }

        foreach ($existing as $instance) {
            if (in_array($this->identifyEntity($entity, $instance), $identifiers)) {
                continue;
            }

            $query = clone $this->query;
            $query->reset()
                ->operation(QueryInterface::OPERATION_DELETE, $entity, $instance)
                ->execute();
        }

        return;
    }

    /**
     * Builds local key from field property pairs
     *
     * @param mixed $entity
     * @param array $pairs
     *
     * @return string
     */
    protected function buildLocalKey($entity, array $pairs)
    {
        $key = '';
        foreach ($pairs as $local => $refer) {
            $key .= $local . ':' . $this->accessProperty($entity, $local);
        }

        return $key;
    }

    /**
     * Builds foreign key from field property pairs
     *
     * @param mixed $entity
     * @param array $pairs
     *
     * @return string
     */
    protected function buildForeignKey($entity, array $pairs)
    {
        $key = '';
        foreach ($pairs as $local => $refer) {
            $key .= $local . ':' . $this->accessProperty($entity, $refer);
        }

        return $key;
    }

    /**
     * Returns property value
     * If third parameter passed, value will be set to it
     *
     * @param array|object $entity
     * @param string       $field
     * @param null|mixed   $value
     *
     * @return mixed|null
     * @throws RelationException
     */
    protected function accessProperty(&$entity, $field, $value = null)
    {
        if (is_array($entity) || $entity instanceof \ArrayAccess) {
            if ($value !== null) {
                $entity[$field] = $value;
            }

            return isset($entity[$field]) ? $entity[$field] : null;
        }

        $ref = new \ReflectionObject($entity);

        if (!$ref->hasProperty($field)) {
            if ($value !== null) {
                $entity->$field = $value;
            }

            return isset($entity->$field) ? $entity->$field : null;
        }

        $prop = $ref->getProperty($field);
        $prop->setAccessible(true);

        if ($value !== null) {
            $prop->setValue($entity, $value);
        }

        return $prop->getValue($entity);
    }

    /**
     * Returns entity identifier
     * If more than one primary keys, entity will not be identified
     *
     * @param string $entity
     * @param object $instance
     *
     * @return mixed|null
     */
    protected function identifyEntity($entity, $instance)
    {
        $indexes = $this->models->get($entity)
            ->primaryFields();

        $id = array();
        foreach ($indexes as $field) {
            $id[] = $this->accessProperty($instance, $field->name());
        }

        return implode(':', $id);
    }
}
