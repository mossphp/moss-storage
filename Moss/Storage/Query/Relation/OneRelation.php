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
class OneRelation extends AbstractRelation implements RelationInterface
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

            foreach ($this->definition->keys() as $local => $refer) {
                $conditions[$refer][] = $this->getPropertyValue($entity, $local);
            }

            $relations[$this->buildLocalKey($entity, $this->definition->keys())][] = &$result[$i];
        }

        $collection = $this->fetch($this->definition->entity(), $conditions, true);

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->definition->keys());

            if (!isset($relations[$key])) {
                continue;
            }

            foreach ($relations[$key] as &$entity) {
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
     * @throws RelationException
     */
    public function write(&$result)
    {
        if (!isset($result->{$this->definition->container()})) {
            return $result;
        }

        $entity = &$result->{$this->definition->container()};

        $this->assertInstance($entity);

        foreach ($this->definition->foreignValues() as $field => $value) {
            $this->setPropertyValue($entity, $field, $value);
        }

        foreach ($this->definition->keys() as $local => $foreign) {
            $this->setPropertyValue($entity, $foreign, $this->getPropertyValue($result, $local));
        }

        $this->query->write($this->definition->entity(), $entity)
            ->execute();

        $conditions = [];
        foreach ($this->definition->foreignValues() as $field => $value) {
            $conditions[$field][] = $value;
        }

        foreach ($this->definition->keys() as $foreign) {
            $conditions[$foreign][] = $this->getPropertyValue($entity, $foreign);
        }

        $this->cleanup($this->definition->entity(), [$entity], $conditions);

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
        if (!isset($result->{$this->definition->container()})) {
            return $result;
        }

        $entity = &$result->{$this->definition->container()};

        $this->assertInstance($entity);

        $this->query->delete($this->definition->entity(), $entity)
            ->execute();

        return $result;
    }

    /**
     * Executes clear for one-to-many relation
     */
    public function clear()
    {
        $this->query->clear($this->definition->entity())
            ->execute();
    }
}

