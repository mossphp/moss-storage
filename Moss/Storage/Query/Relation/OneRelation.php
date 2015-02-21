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

        foreach ($result as $i => $entity) {
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
        $entity = $this->getPropertyValue($result, $this->definition->container());
        if (empty($entity)) {
            $conditions = [];
            foreach ($this->definition->keys() as $local => $foreign) {
                $conditions[$foreign][] = $this->getPropertyValue($result, $local);
            }

            $this->cleanup($this->definition->entity(), [], $conditions);
            return $result;
        }

        $this->assertInstance($entity);

        foreach ($this->definition->keys() as $local => $foreign) {
            $this->setPropertyValue($entity, $foreign, $this->getPropertyValue($result, $local));
        }

        $this->query->write($this->definition->entity(), $entity)->execute();
        $this->setPropertyValue($result, $this->definition->container(), $entity);

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
        $entity = $this->getPropertyValue($result, $this->definition->container());
        if (empty($entity)) {
            return $result;
        }

        $this->assertInstance($entity);

        $this->query->delete($this->definition->entity(), $entity)->execute();

        return $result;
    }
}

