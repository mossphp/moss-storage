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
 * Many to many relation handler with mediator (pivot) table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ManyTroughRelation extends AbstractRelation implements RelationInterface
{
    /**
     * Executes read for one-to-many relation
     *
     * @param array $result
     *
     * @return array
     */
    public function read(&$result)
    {
        $relations = [];
        $conditions = [];

        foreach ($this->definition->foreignValues() as $refer => $value) {
            $conditions[$refer][] = $value;
        }

        foreach ($result as $i => $entity) {
            if (!$this->assertEntity($entity)) {
                continue;
            }

            if (!$this->getPropertyValue($entity, $this->definition->container())) {
                $this->setPropertyValue($entity, $this->definition->container(), null);
            }

            foreach ($this->definition->localKeys() as $local => $refer) {
                $conditions[$refer][] = $this->getPropertyValue($entity, $local);
            }

            $relations[$this->buildLocalKey($entity, $this->definition->localKeys())][] = &$result[$i];
        }

        $collection = $this->fetch($this->definition->mediator(), $conditions, false);

// --- MEDIATOR START

        $mediator = [];
        $conditions = [];
        foreach ($collection as $entity) {
            foreach ($this->definition->foreignKeys() as $local => $refer) {
                $conditions[$refer][] = $this->getPropertyValue($entity, $local);
            }

            $in = $this->buildForeignKey($entity, $this->definition->localKeys());
            $out = $this->buildLocalKey($entity, $this->definition->foreignKeys());
            $mediator[$out][] = $in;
        }

        $collection = $this->fetch($this->definition->entity(), $conditions, true);

// --- MEDIATOR END

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->definition->foreignKeys());

            if (!isset($mediator[$key])) {
                continue;
            }
            foreach ($mediator[$key] as $mkey) {
                if (!isset($relations[$mkey])) {
                    continue;
                }

                foreach ($relations[$mkey] as &$entity) {
                    $value = $this->getPropertyValue($entity, $this->definition->container());
                    $value[] = $relEntity;
                    $this->setPropertyValue($entity, $this->definition->container(), $value);
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
        if (!isset($result->{$this->definition->container()})) {
            return $result;
        }

        $container = &$result->{$this->definition->container()};

        foreach ($container as $entity) {
            $this->query->write($this->definition->entity(), $entity)
                ->execute();
        }

        $mediators = [];

        foreach ($container as $entity) {
            $mediator = [];

            foreach ($this->definition->localKeys() as $local => $foreign) {
                $mediator[$foreign] = $this->getPropertyValue($result, $local);
            }

            foreach ($this->definition->foreignKeys() as $foreign => $local) {
                $mediator[$foreign] = $this->getPropertyValue($entity, $local);
            }

            $this->query->write($this->definition->mediator(), $mediator)
                ->execute();

            $mediators[] = $mediator;
        }

        $conditions = [];
        foreach ($this->definition->localKeys() as $foreign) {
            foreach ($mediators as $mediator) {
                $conditions[$foreign][] = $this->getPropertyValue($mediator, $foreign);
            }
        }

        $this->cleanup($this->definition->mediator(), $mediators, $conditions);

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
        if (!isset($result->{$this->definition->container()})) {
            return $result;
        }

        $container = &$result->{$this->definition->container()};

        foreach ($container as $entity) {
            $mediator = [];

            foreach ($this->definition->localKeys() as $local => $foreign) {
                $mediator[$foreign] = $this->getPropertyValue($result, $local);
            }

            foreach ($this->definition->foreignKeys() as $foreign => $local) {
                $mediator[$foreign] = $this->getPropertyValue($entity, $local);
            }

            $this->query
                ->delete($this->definition->mediator(), $mediator)
                ->execute();

        }

        return $result;
    }

    /**
     * Executes clear for one-to-many relation
     */
    public function clear()
    {
        $this->query->clear($this->definition->mediator())
            ->execute();
    }
}
