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
 * One to one relation handler with mediator (pivot) table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class OneTroughRelation extends Relation
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
        $relations = [];
        $conditions = [];

        foreach ($this->relation->foreignValues() as $refer => $value) {
            $conditions[$refer][] = $value;
        }

        foreach ($result as $i => $entity) {
            if (!$this->assertEntity($entity)) {
                continue;
            }

            if (!$this->accessProperty($entity, $this->relation->container())) {
                $this->accessProperty($entity, $this->relation->container(), null);
            }

            foreach ($this->relation->localKeys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $relations[$this->buildLocalKey($entity, $this->relation->localKeys())][] = & $result[$i];
        }

        $collection = $this->fetch($this->relation->mediator(), $conditions);

// --- MEDIATOR START

        $mediator = [];
        $conditions = [];
        foreach ($collection as $entity) {
            foreach ($this->relation->foreignKeys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $in = $this->buildForeignKey($entity, $this->relation->localKeys());
            $out = $this->buildLocalKey($entity, $this->relation->foreignKeys());
            $mediator[$out] = $in;
        }

        $collection = $this->fetch($this->relation->entity(), $conditions);

// --- MEDIATOR END

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->relation->foreignKeys());

            if (!isset($mediator[$key]) || !isset($relations[$mediator[$key]])) {
                continue;
            }

            foreach ($relations[$mediator[$key]] as &$entity) {
                $entity->{$this->relation->container()} = $relEntity;
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
     */
    public function write(&$result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $entity = & $result->{$this->relation->container()};

        $query = clone $this->query;
        $query->write($this->relation->entity(), $entity)
            ->execute();

        $fields = array_merge(array_values($this->relation->localKeys()), array_keys($this->relation->foreignKeys()));
        $mediator = [];

        foreach ($this->relation->localKeys() as $local => $foreign) {
            $mediator[$foreign] = $this->accessProperty($result, $local);
        }

        foreach ($this->relation->foreignKeys() as $foreign => $local) {
            $mediator[$foreign] = $this->accessProperty($entity, $local);
        }

        $query = clone $this->query;
        $query->write($this->relation->mediator(), $mediator)
            ->fields($fields)
            ->execute();

        $conditions = [];
        foreach ($this->relation->localKeys() as $foreign) {
            $conditions[$foreign][] = $this->accessProperty($mediator, $foreign);
        }

        $this->cleanup($this->relation->mediator(), [$mediator], $conditions);

        return $result;
    }

    /**
     * Executes delete for one-to-one relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function delete(&$result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $mediator = [];

        foreach ($this->relation->localKeys() as $entityField => $mediatorField) {
            $mediator[$mediatorField] = $this->accessProperty($result, $entityField);
        }

        $entity = $result->{$this->relation->container()};
        foreach ($this->relation->foreignKeys() as $mediatorField => $entityField) {
            $mediator[$mediatorField] = $this->accessProperty($entity, $entityField);
        }

        $query = clone $this->query;
        $query->delete($this->relation->mediator(), $mediator)
            ->execute();

        return $result;
    }

    /**
     * Executes clear for one-to-many relation
     */
    public function clear()
    {
        $query = clone $this->query;
        $query->clear($this->relation->mediator())
            ->execute();
    }
}
