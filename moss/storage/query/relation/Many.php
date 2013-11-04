<?php
namespace moss\storage\query\relation;

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
        $references = array();
        $conditions = array();

        foreach ($result as $i => $entity) {
            if (!$this->assertEntity($entity)) {
                continue;
            }

            if (!$this->accessProperty($entity, $this->relation->container())) {
                $this->accessProperty($entity, $this->relation->container(), array());
            }

            foreach ($this->relation->referencedValues() as $refer => $value) {
                $conditions[$refer][] = $value;
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $references[$this->buildLocalKey($entity)][] = & $result[$i];
        }

        foreach ($conditions as $field => $values) {
            $this->query->condition($field, array_unique($values));
        }

        $collection = $this->query->execute();

        foreach ($collection as $relEntity) {
            $key = $this->buildReferencedKey($relEntity);

            if (!isset($references[$key])) {
                continue;
            }

            foreach ($references[$key] as &$entity) {
                $value = $this->accessProperty($entity, $this->relation->container());
                $value[] = $relEntity;
                $this->accessProperty($entity, $this->relation->container(), $value);
                unset($entity);
            }
        }

        if (!$this->transparent()) {
            return $result;
        }

        foreach ($result as &$entity) {
            $array = (array) $this->accessProperty($entity, $this->relation->container());

            $collection = array();

            foreach ($array as $rel) {
                if ($sub = $this->accessProperty($rel, $this->relation->container())) {
                    $collection = array_merge($collection, (array) $sub);
                    continue;
                }

                $collection[] = $rel;
            }

            $this->accessProperty($entity, $this->relation->container(), $collection);
            unset($entity);
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
    public function write($result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $container = & $result->{$this->relation->container()};

        if (!$container instanceof \ArrayAccess && !is_array($container)) {
            throw new RelationException(sprintf('Relation container must be array or instance of ArrayAccess, got %s', is_object($container) ? get_class($container) : gettype($container)));
        }

        if (empty($container)) {
            return $result;
        }

        foreach ($result->{$this->relation->container()} as $relEntity) {
            foreach ($this->relation->referencedValues() as $field => $value) {
                $this->accessProperty($relEntity, $field, $value);
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $this->accessProperty($relEntity, $refer, $this->accessProperty($result, $local));
            }

            $this->query
                ->operation('write', $relEntity)
                ->execute();
        }

        $relIdentifiers = array();
        foreach ($result->{$this->relation->container()} as $relEntity) {
            $relIdentifiers[] = $this->identifyEntity($relEntity);
        }

        $deleteQuery = $this->query->operation('read');

        foreach ($this->relation->referencedValues() as $field => $value) {
            $deleteQuery->condition($field, $value);
        }

        foreach ($this->relation->keys() as $local => $refer) {
            $deleteQuery->condition($refer, $this->accessProperty($result, $local));
        }

        $deleteCollection = $deleteQuery->execute();

        if (!empty($deleteCollection)) {
            foreach ($deleteCollection as $delEntity) {
                if (!in_array($this->identifyEntity($delEntity), $relIdentifiers)) {
                    $this->query
                        ->operation('delete', $delEntity)
                        ->execute();
                }
            }
        }

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
    public function delete($result)
    {
        if (!isset($result->{$this->relation->container()})) {
            return $result;
        }

        $container = & $result->{$this->relation->container()};

        if (!$container instanceof \ArrayAccess && !is_array($container)) {
            throw new RelationException(sprintf('Relation container must be array or instance of ArrayAccess, got %s', is_object($container) ? get_class($container) : gettype($container)));
        }

        foreach ($result->{$this->relation->container()} as $relEntity) {
            $this->query
                ->operation('delete', $relEntity)
                ->execute();
        }

        return $result;
    }

    /**
     * Executes clear for one-to-many relation
     */
    public function clear()
    {
        $this->query
            ->operation('clear')
            ->execute();
    }
}
