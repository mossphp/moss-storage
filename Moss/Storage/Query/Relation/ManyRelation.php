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
 * One to many relation handler
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ManyRelation extends AbstractRelation implements RelationInterface
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

        foreach ($result as $i => $entity) {
            $container = $this->getPropertyValue($entity, $this->definition->container());
            if (empty($container)) {
                $this->setPropertyValue($entity, $this->definition->container(), []);
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
                $this->addPropertyValue($entity, $this->definition->container(), $relEntity);
                unset($entity);
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
        $container = $this->getPropertyValue($result, $this->definition->container());
        if (empty($container)) {
            $conditions = [];
            foreach ($this->definition->keys() as $local => $foreign) {
                $conditions[$foreign][] = $this->getPropertyValue($result, $local);
            }

            $this->cleanup($this->definition->entity(), [], $conditions);
            return $result;
        }

        $this->assertArrayAccess($container);

        foreach ($container as $relEntity) {
            foreach ($this->definition->keys() as $local => $foreign) {
                $this->setPropertyValue($relEntity, $foreign, $this->getPropertyValue($result, $local));
            }

            $this->query->write($this->definition->entity(), $relEntity)
                ->execute();
        }

        $this->setPropertyValue($result, $this->definition->container(), $container);

        $conditions = [];
        foreach ($this->definition->keys() as $local => $foreign) {
            $conditions[$foreign] = $this->getPropertyValue($result, $local);
        }

        $this->cleanup($this->definition->entity(), $container, $conditions);

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
        $container = $this->getPropertyValue($result, $this->definition->container());
        if (empty($container)) {
            return $result;
        }

        $this->assertArrayAccess($container);

        foreach ($container as $relEntity) {
            $this->query->delete($this->definition->entity(), $relEntity)
                ->execute();
        }

        $this->setPropertyValue($result, $this->definition->container(), $container);

        return $result;
    }
}
