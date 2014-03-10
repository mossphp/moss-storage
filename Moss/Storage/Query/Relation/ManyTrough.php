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

use Moss\Storage\Query\QueryInterface;

/**
 * Many to many relation handler with mediator (pivot) table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Query
 */
class ManyTrough extends Relation
{
    /**
     * Executes read for one-to-many relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function read(&$result)
    {
        $relations = array();
        $conditions = array();

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

        $collection = $this->fetch($this->relation->mediator(), $conditions, true);

// --- MEDIATOR START

        $mediator = array();
        $conditions = array();
        foreach ($collection as $entity) {
            foreach ($this->relation->foreignKeys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $in = $this->buildForeignKey($entity, $this->relation->localKeys());
            $out = $this->buildLocalKey($entity, $this->relation->foreignKeys());
            $mediator[$out][] = $in;
        }

        $collection = $this->fetch($this->relation->entity(), $conditions);

// --- MEDIATOR END

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->relation->foreignKeys());

            if (!isset($mediator[$key])) {
                continue;
            }
            foreach ($mediator[$key] as $mkey) {
                foreach ($relations[$mkey] as &$entity) {
                    $value = $this->accessProperty($entity, $this->relation->container());
                    $value[] = $relEntity;
                    $this->accessProperty($entity, $this->relation->container(), $value);
                    unset($entity);
                }
            }
        }

        return $result;
    }

    /**
     * Executes write for one-to-many relation
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

        $container = & $result->{$this->relation->container()};

        foreach ($container as $entity) {
            $query = clone $this->query;
            $query->write($this->relation->entity(), $entity)
                ->execute();
        }

        $fields = array_merge(array_values($this->relation->localKeys()), array_keys($this->relation->foreignKeys()));
        $mediators = array();

        foreach ($container as $entity) {
            $mediator = array();

            foreach ($this->relation->localKeys() as $local => $foreign) {
                $mediator[$foreign] = $this->accessProperty($result, $local);
            }

            foreach ($this->relation->foreignKeys() as $foreign => $local) {
                $mediator[$foreign] = $this->accessProperty($entity, $local);
            }

            $query = clone $this->query;
            $query->reset()
                ->write($this->relation->mediator(), $mediator)
                ->fields($fields)
                ->execute();

            $mediators[] = $mediator;
        }

        $conditions = array();
        foreach ($this->relation->localKeys() as $foreign) {
            foreach($mediators as $mediator) {
                $conditions[$foreign][] = $this->accessProperty($mediator, $foreign);
            }
        }

        $this->cleanup($this->relation->mediator(), $mediators, $conditions);

        return $result;
    }

    /**
     * Executes delete for one-to-many relation
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

        $container = & $result->{$this->relation->container()};

        foreach ($container as $entity) {
            $mediator = array();

            foreach ($this->relation->localKeys() as $local => $foreign) {
                $mediator[$foreign] = $this->accessProperty($result, $local);
            }

            foreach ($this->relation->foreignKeys() as $foreign => $local) {
                $mediator[$foreign] = $this->accessProperty($entity, $local);
            }

            $query = clone $this->query;
            $query->reset()
                ->delete($this->relation->mediator(), $mediator)
                ->execute();

        }

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