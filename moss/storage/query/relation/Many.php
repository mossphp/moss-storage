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
        $foreign = array();
        $conditions = array();

        foreach ($result as $i => $entity) {
            if (!$this->assertEntity($entity)) {
                continue;
            }

            if (!$this->accessProperty($entity, $this->relation->table())) {
                $this->accessProperty($entity, $this->relation->table(), array());
            }

            foreach ($this->relation->foreignValues() as $refer => $value) {
                $conditions[$refer][] = $value;
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $conditions[$refer][] = $this->accessProperty($entity, $local);
            }

            $foreign[$this->buildLocalKey($entity)][] = & $result[$i];
        }

        $this->query->reset()
                    ->operation(QueryInterface::OPERATION_READ, $this->relation->entity());

        foreach ($conditions as $field => $values) {
            $this->query->where($field, $values);
        }

        $collection = $this->query()
                           ->execute();

        foreach ($collection as $relEntity) {
            $key = $this->buildForeignKey($relEntity);

            if (!isset($foreign[$key])) {
                continue;
            }

            foreach ($foreign[$key] as &$entity) {
                $value = $this->accessProperty($entity, $this->relation->table());
                $value[] = $relEntity;
                $this->accessProperty($entity, $this->relation->table(), $value);
                unset($entity);
            }
        }

        if (!$this->transparent()) {
            return $result;
        }

        foreach ($result as &$entity) {
            $value = $this->accessProperty($entity, $this->relation->table());

            $collection = array();

            foreach ($value as $rel) {
                $sub = $this->accessProperty($rel, $this->relation->table());

                if (is_array($sub) || $sub instanceof \ArrayAccess) {
                    $collection = array_merge($collection, $sub);
                    continue;
                }

                $collection[] = $rel;
            }

            $this->accessProperty($entity, $this->relation->table(), $collection);
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
        if (!isset($result->{$this->relation->table()})) {
            return $result;
        }

        $table = & $result->{$this->relation->table()};

        if (!$table instanceof \ArrayAccess && !is_array($table)) {
            throw new RelationException(sprintf('Relation table must be array or instance of ArrayAccess, got %s', is_object($table) ? get_class($table) : gettype($table)));
        }

        foreach ($result->{$this->relation->table()} as $relEntity) {
            foreach ($this->relation->foreignValues() as $field => $value) {
                $this->accessProperty($relEntity, $field, $value);
            }

            foreach ($this->relation->keys() as $local => $refer) {
                $this->accessProperty($relEntity, $refer, $this->accessProperty($result, $local));
            }

            $this->query
                ->operation(QueryInterface::OPERATION_WRITE, $relEntity)
                ->execute();
        }

        $relIdentifiers = array();
        foreach ($result->{$this->relation->table()} as $relEntity) {
            $relIdentifiers[] = $this->identifyEntity($relEntity);
        }

        $deleteQuery = $this->query->reset()
                                   ->operation(QueryInterface::OPERATION_READ, $this->relation->entity());

        foreach ($this->relation->foreignValues() as $field => $value) {
            $deleteQuery->where($field, $value);
        }

        foreach ($this->relation->keys() as $local => $refer) {
            $deleteQuery->where($refer, $this->accessProperty($result, $local));
        }

        $deleteCollection = $deleteQuery->execute();

        if (empty($deleteCollection)) {
            return $result;
        }

        foreach ($deleteCollection as $delEntity) {
            if (in_array($this->identifyEntity($delEntity), $relIdentifiers)) {
                continue;
            }

            $this->query->reset()
                        ->operation(QueryInterface::OPERATION_DELETE, $delEntity)
                        ->execute();
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
        if (!isset($result->{$this->relation->table()})) {
            return $result;
        }

        $table = & $result->{$this->relation->table()};

        if (!$table instanceof \ArrayAccess && !is_array($table)) {
            throw new RelationException(sprintf('Relation table must be array or instance of ArrayAccess, got %s', is_object($table) ? get_class($table) : gettype($table)));
        }

        foreach ($result->{$this->relation->table()} as $relEntity) {
            $this->query
                ->reset()
                ->operation(QueryInterface::OPERATION_DELETE, $relEntity)
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
            ->reset()
            ->operation(QueryInterface::OPERATION_CLEAR, $this->relation->entity())
            ->execute();
    }
}
