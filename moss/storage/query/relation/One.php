<?php
namespace moss\storage\query\relation;

use moss\storage\query\QueryInterface;

/**
 * One to one relation representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class One extends Relation
{

    /**
     * Executes read for one-to-one relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function read(&$result)
    {
        $foreigns = array();

        $conditions = array();
        foreach ($this->relation->foreignValues() as $refer => $value) {
            $conditions[$refer][] = $value;
        }

        foreach ($result as $i => $entity) {
            if (!$this->assertEntity($entity)) {
                continue;
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $foreigns[$this->buildLocalKey($entity)][] = & $result[$i];
        }

        $this->query->reset()
                    ->operation(QueryInterface::OPERATION_READ, $this->relation->entity());

        foreach ($conditions as $field => $values) {
            $this->query->where($field, $values);
        }

        $collection = $this->query->execute();

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity);

            if (!isset($foreigns[$key])) {
                continue;
            }

            foreach ($foreigns[$key] as &$entity) {
                $this->accessProperty($entity, $this->relation->container(), $relEntity);
                unset($entity);
            }
        }

        if (!$this->transparent()) {
            return $result;
        }

        foreach ($result as &$entity) {
            if (!$rel = $this->accessProperty($entity, $this->relation->container())) {
                continue;
            }

            if ($sub = $this->accessProperty($rel, $this->relation->container())) {
                $this->accessProperty($entity, $this->relation->container(), $sub);
            }

            unset($entity);
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
    public function write($result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $entity = & $result->{$this->relation->container()};

        $this->assertInstance($entity);

        foreach ($this->relation->foreignValues() as $field => $value) {
            $this->accessProperty($entity, $field, $value);
        }

        foreach ($this->relation->keys() as $local => $refer) {
            $this->accessProperty($entity, $refer, $this->accessProperty($result, $local));
        }

        $this->query
            ->reset()
            ->operation(QueryInterface::OPERATION_WRITE, $entity)
            ->execute();


        // cleanup
        $this->query->reset()
                    ->operation(QueryInterface::OPERATION_READ, $this->relation->entity());

        foreach ($this->relation->foreignValues() as $field => $value) {
            $this->query->where($field, $value);
        }

        foreach ($this->relation->keys() as $local => $refer) {
            $this->query->where($refer, $this->accessProperty($entity, $local));
        }

        $existingEntities = $this->query->execute();

        if (empty($existingEntities)) {
            return $result;
        }

        $identifier = $this->identifyEntity($entity);
        foreach ($existingEntities as $existingEntity) {
            if ($identifier == $this->identifyEntity($existingEntity)) {
                continue;
            }

            $this->query
                ->reset()
                ->operation(QueryInterface::OPERATION_DELETE, $existingEntity)
                ->execute();
        }

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
    public function delete($result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $entity = & $result->{$this->relation->container()};

        $this->assertInstance($entity);

        $this->query
            ->reset()
            ->operation(QueryInterface::OPERATION_DELETE, $entity)
            ->execute();

        return $result;
    }

    /**
     * Executes clear for one-to-many relation
     */
    public function clear()
    {
        $this->query
            ->reset()
            ->operation(QueryInterface::OPERATION_CLEAR, $this->relation->entity())
            ->execute();
    }
}
