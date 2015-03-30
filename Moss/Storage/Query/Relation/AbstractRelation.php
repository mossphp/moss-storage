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

use Doctrine\DBAL\Query\QueryBuilder;
use Moss\Storage\GetTypeTrait;
use Moss\Storage\Model\Definition\RelationInterface as DefinitionInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Query\OperationTraits\PropertyAccessorTrait;
use Moss\Storage\Query\OperationTraits\RelationTrait;
use Moss\Storage\Query\StorageInterface;

/**
 * Abstract class for basic relation methods
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class AbstractRelation
{
    use RelationTrait;
    use PropertyAccessorTrait;
    use GetTypeTrait;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var ModelBag
     */
    protected $models;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var RelationInterface[]
     */
    protected $relations = [];
    protected $conditions = [];
    protected $orders = [];
    protected $limit;
    protected $offset;

    /**
     * Constructor
     *
     * @param StorageInterface         $storage
     * @param DefinitionInterface      $relation
     * @param ModelBag                 $models
     * @param RelationFactoryInterface $factory
     */
    public function __construct(StorageInterface $storage, DefinitionInterface $relation, ModelBag $models, RelationFactoryInterface $factory)
    {
        $this->storage = $storage;
        $this->definition = $relation;
        $this->models = $models;
        $this->factory = $factory;
    }

    /**
     * Returns relation name
     *
     * @return string
     */
    public function name()
    {
        return $this->definition->name();
    }

    /**
     * Returns relation query instance
     *
     * @return QueryBuilder
     */
    public function query()
    {
        return $this->storage;
    }

    /**
     * Adds where condition to relation
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = '==', $logical = 'and')
    {
        $this->conditions[] = [$field, $value, $comparison, $logical];

        return $this;
    }

    /**
     * Adds sorting to relation
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = 'desc')
    {
        $this->conditions[] = [$field, $order];

        return $this;
    }

    /**
     * Sets limits to relation
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
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
        $keys = array_keys($pairs);

        return $this->buildKey($entity, array_combine($keys, $keys));
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
        return $this->buildKey($entity, $pairs);
    }

    /**
     * Builds key from key-value pairs
     *
     * @param mixed $entity
     * @param array $pairs
     *
     * @return string
     */
    protected function buildKey($entity, array $pairs)
    {
        $key = [];
        foreach ($pairs as $local => $refer) {
            $key[] = $local . ':' . $this->getPropertyValue($entity, $refer);
        }

        return implode('-', $key);
    }

    /**
     * Fetches collection of entities matching set conditions
     * Optionally sorts it and limits it
     *
     * @param string $entity
     * @param array  $conditions
     * @param bool   $result
     *
     * @return array
     */
    protected function fetch($entity, array $conditions, $result = false)
    {
        $query = $this->storage->read($entity);

        foreach ($conditions as $field => $values) {
            $query->where($field, $values);
        }

        if (!$result) {
            return $query->execute();
        }

        foreach ($this->relations as $relation) {
            $query->setRelation($relation);
        }

        foreach ($this->conditions as $condition) {
            $query->where($condition[0], $condition[1], $condition[2], $condition[3]);
        }

        foreach ($this->orders as $order) {
            $query->order($order[0], $order[1]);
        }

        if ($this->limit !== null || $this->offset !== null) {
            $query->limit($this->limit, $this->offset);
        }

        return $query->execute();
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
        $entityClass = $this->definition->entity();
        if (!$entity instanceof $entityClass) {
            throw new RelationException(sprintf('Relation entity must be instance of %s, got %s', $entityClass, $this->getType($entity)));
        }

        return true;
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
        if (!$existing = $this->isCleanupNecessary($entity, $conditions)) {
            return;
        }

        $identifiers = [];
        foreach ($collection as $instance) {
            $identifiers[] = $this->identifyEntity($entity, $instance);
        }

        foreach ($existing as $instance) {
            if (in_array($this->identifyEntity($entity, $instance), $identifiers)) {
                continue;
            }

            $this->storage->delete($entity, $instance)
                ->execute();
        }

        return;
    }

    /**
     * Returns array with entities that should be deleted or false otherwise
     *
     * @param string $entity
     * @param array  $conditions
     *
     * @return array|bool
     */
    private function isCleanupNecessary($entity, $conditions)
    {
        if (empty($conditions)) {
            return false;
        }

        $existing = $this->fetch($entity, $conditions);

        if (empty($existing)) {
            return false;
        }

        return $existing;
    }

    /**
     * Returns entity identifier
     * If more than one primary keys, entity will not be identified
     *
     * @param string $entity
     * @param object $instance
     *
     * @return string
     */
    protected function identifyEntity($entity, $instance)
    {
        $fields = $this->models->get($entity)
            ->primaryFields();

        $id = [];
        foreach ($fields as $field) {
            $id[] = $this->getPropertyValue($instance, $field->name());
        }

        return implode(':', $id);
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
        if (!$container instanceof \Traversable && !is_array($container)) {
            throw new RelationException(sprintf('Relation container must be array or instance of ArrayAccess, got %s', $this->getType($container)));
        }
    }
}
