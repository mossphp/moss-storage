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

/**
 * One to one relation handler
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class OneRelation extends AbstractRelation
{
    /**
     * Executes read for one-to-one relation
     *
     * @param array|\Traversable $result
     *
     * @return array|\Traversable
     */
    public function read(&$result)
    {
        $conditions = [];

        foreach ($this->relation->foreignValues() as $refer => $value) {
            $conditions[$refer][] = $value;
        }

        foreach ($result as $entity) {
            if (!$this->assertEntity($entity)) {
                continue;
            }

            if (!$this->accessProperty($entity, $this->relation->container())) {
                $this->accessProperty($entity, $this->relation->container(), null);
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }
        }

        $collection = $this->fetch($this->relation->entity(), $conditions);

// --- MEDIATOR START

        $relations = [];
        foreach ($result as $i => $entity) {
            $relations[$this->buildLocalKey($entity, $this->relation->keys())][] = & $result[$i];
        }

// --- MEDIATOR END

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->relation->keys());

            if (!isset($relations[$key])) {
                continue;
            }

            foreach ($relations[$key] as &$entity) {
                $this->accessProperty($entity, $this->relation->container(), $relEntity);
                unset($entity);
            }
        }

        return $result;
    }

    /**
     * Executes write fro one-to-one relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     * @throws RelationException
     */
    public function write(&$result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $entity = & $result->{$this->relation->container()};

        $this->assertInstance($entity);

        foreach ($this->relation->foreignValues() as $field => $value) {
            $this->accessProperty($entity, $field, $value);
        }

        foreach ($this->relation->keys() as $local => $foreign) {
            $this->accessProperty($entity, $foreign, $this->accessProperty($result, $local));
        }

        $query = clone $this->query;
        $query
            ->write($this->relation->entity(), $entity)
            ->execute();

        $conditions = [];
        foreach ($this->relation->foreignValues() as $field => $value) {
            $conditions[$field][] = $value;
        }

        foreach ($this->relation->keys() as $foreign) {
            $conditions[$foreign][] = $this->accessProperty($entity, $foreign);
        }

        $this->cleanup($this->relation->entity(), [$entity], $conditions);

        return $result;
    }

    /**
     * Executes delete for one-to-one relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     * @throws RelationException
     */
    public function delete(&$result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $entity = & $result->{$this->relation->container()};

        $this->assertInstance($entity);

        $query = clone $this->query;
        $query->delete($this->relation->entity(), $entity)
            ->execute();

        return $result;
    }

    /**
     * Executes clear for one-to-many relation
     */
    public function clear()
    {
        $query = clone $this->query;
        $query->clear($this->relation->entity())
            ->execute();
    }
}
