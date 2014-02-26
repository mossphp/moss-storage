<?php
namespace moss\storage\query\relation;

use moss\storage\query\QueryInterface;

/**
 * One to many relation representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class Many extends Relation
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

            if (!isset($entity->{$this->relation->container()})) {
                $entity->{$this->relation->container()} = array();
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $relations[$this->buildLocalKey($entity, $this->relation->keys())][] = & $result[$i];
        }

        $collection = $this->fetch($this->relation->entity(), $conditions);

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity, $this->relation->keys());

            if (!isset($relations[$key])) {
                continue;
            }

            foreach ($relations[$key] as &$entity) {
                $entity->{$this->relation->container()}[] = $relEntity;
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
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $container = & $result->{$this->relation->container()};
        $this->assertArrayAccess($container);

        foreach ($container as $relEntity) {
            foreach ($this->relation->foreignValues() as $field => $value) {
                $this->accessProperty($relEntity, $field, $value);
            }

            foreach ($this->relation->keys() as $local => $foreign) {
                $this->accessProperty($relEntity, $foreign, $this->accessProperty($result, $local));
            }

            $query = clone $this->query;
            $query->operation(QueryInterface::OPERATION_WRITE, $this->relation->entity(), $relEntity)
                ->execute();
        }

        // cleanup

        $conditions = array();
        foreach ($this->relation->foreignValues() as $field => $value) {
            $conditions[$field] = $value;
        }

        foreach ($this->relation->keys() as $local => $foreign) {
            $conditions[$foreign] = $this->accessProperty($result, $local);
        }

        $this->cleanup($this->relation->entity(), $container, $conditions);

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
        $this->assertArrayAccess($container);

        foreach ($result->{$this->relation->container()} as $relEntity) {
            $query = clone $this->query;
            $query->operation(QueryInterface::OPERATION_DELETE, $this->relation->entity(), $relEntity)
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
        $query->operation(QueryInterface::OPERATION_CLEAR, $this->relation->entity())
            ->execute();
    }
}