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

        foreach ($result as $i => $entity) {
            foreach ($this->definition->localKeys() as $local => $refer) {
                $conditions[$refer][] = $this->getPropertyValue($entity, $local);
            }

            $relations[$this->buildLocalKey($entity, $this->definition->localKeys())][] = & $result[$i];
        }

        $collection = $this->fetch($this->definition->mediator(), $conditions, false);

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

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->definition->foreignKeys());

            if (!isset($mediator[$key]) || !isset($relations[$mediator[$key]])) {
                continue;
            }

            foreach ($relations[$mediator[$key]] as &$entity) {
                $this->setPropertyValue($entity, $this->definition->container(), $relEntity);
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
        $entity = $this->getPropertyValue($result, $this->definition->container());
        if (empty($entity)) {
            $conditions = [];
            foreach ($this->definition->localKeys() as $local => $foreign) {
                $conditions[$foreign][] = $this->getPropertyValue($result, $local);
            }

            $this->cleanup($this->definition->mediator(), [], $conditions);
            return $result;
        }

        $this->storage->write($entity, $this->definition->entity())->execute();

        $mediator = [];

        foreach ($this->definition->localKeys() as $local => $foreign) {
            $mediator[$foreign] = $this->getPropertyValue($result, $local);
        }

        foreach ($this->definition->foreignKeys() as $foreign => $local) {
            $mediator[$foreign] = $this->getPropertyValue($entity, $local);
        }

        $this->storage->write($mediator, $this->definition->mediator())->execute();
        $this->setPropertyValue($result, $this->definition->container(), $entity);

        $conditions = [];
        foreach ($this->definition->localKeys() as $foreign) {
            $conditions[$foreign][] = $this->getPropertyValue($mediator, $foreign);
        }

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
        $entity = $this->getPropertyValue($result, $this->definition->container());
        if (empty($entity)) {
            return $result;
        }

        $mediator = [];

        foreach ($this->definition->localKeys() as $entityField => $mediatorField) {
            $mediator[$mediatorField] = $this->getPropertyValue($result, $entityField);
        }

        foreach ($this->definition->foreignKeys() as $mediatorField => $entityField) {
            $mediator[$mediatorField] = $this->getPropertyValue($entity, $entityField);
        }

        $this->storage->delete($mediator, $this->definition->mediator())->execute();
        $this->setPropertyValue($result, $this->definition->container(), $entity);

        return $result;
    }
}
