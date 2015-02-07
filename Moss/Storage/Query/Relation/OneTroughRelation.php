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
class OneTroughRelation extends AbstractRelation implements RelationInterface
{
    /**
     * Executes read for one-to-one relation
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

            $relations[$this->buildLocalKey($entity, $this->definition->localKeys())][] = & $result[$i];
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
            $mediator[$out] = $in;
        }

        $collection = $this->fetch($this->definition->entity(), $conditions, true);

// --- MEDIATOR END

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->definition->foreignKeys());

            if (!isset($mediator[$key]) || !isset($relations[$mediator[$key]])) {
                continue;
            }

            foreach ($relations[$mediator[$key]] as &$entity) {
                $entity->{$this->definition->container()} = $relEntity;
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
        if (!isset($result->{$this->definition->container()})) {
            return $result;
        }

        $entity = & $result->{$this->definition->container()};

        $this->query->write($this->definition->entity(), $entity)
            ->execute();

        $mediator = [];

        foreach ($this->definition->localKeys() as $local => $foreign) {
            $mediator[$foreign] = $this->getPropertyValue($result, $local);
        }

        foreach ($this->definition->foreignKeys() as $foreign => $local) {
            $mediator[$foreign] = $this->getPropertyValue($entity, $local);
        }

        $this->query->write($this->definition->mediator(), $mediator)
            ->execute();

        $conditions = [];
        foreach ($this->definition->localKeys() as $foreign) {
            $conditions[$foreign][] = $this->getPropertyValue($mediator, $foreign);
        }

        $this->cleanup($this->definition->mediator(), [$mediator], $conditions);

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
        if (!isset($result->{$this->definition->container()})) {
            return $result;
        }

        $mediator = [];

        foreach ($this->definition->localKeys() as $entityField => $mediatorField) {
            $mediator[$mediatorField] = $this->getPropertyValue($result, $entityField);
        }

        $entity = $result->{$this->definition->container()};
        foreach ($this->definition->foreignKeys() as $mediatorField => $entityField) {
            $mediator[$mediatorField] = $this->getPropertyValue($entity, $entityField);
        }

        $this->query->delete($this->definition->mediator(), $mediator)
            ->execute();

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
