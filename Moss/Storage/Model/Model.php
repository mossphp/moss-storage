<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model;

use Moss\Storage\GetTypeTrait;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\Definition\IndexInterface;
use Moss\Storage\Model\Definition\RelationInterface;
use Moss\Storage\NormalizeNamespaceTrait;

/**
 * Model describing entity and its relationship to other entities
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Model implements ModelInterface
{
    use NormalizeNamespaceTrait;
    use GetTypeTrait;

    protected $table;
    protected $entity;
    protected $alias;

    /**
     * @var FieldInterface[]
     */
    protected $fields = [];

    /**
     * @var IndexInterface[]
     */
    protected $indexes = [];

    /**
     * @var RelationInterface[]
     */
    protected $relations = [];

    /**
     * Constructor
     *
     * @param string                    $entityClass
     * @param string                    $table
     * @param FieldInterface[]    $fields
     * @param IndexInterface[]    $indexes
     * @param RelationInterface[] $relations
     *
     * @throws ModelException
     */
    public function __construct($entityClass, $table, array $fields, array $indexes = [], array $relations = [])
    {
        $this->table = $table;
        $this->entity = $entityClass ? $this->normalizeNamespace($entityClass) : null;

        $this->assignFields($fields);
        $this->assignIndexes($indexes);
        $this->assignRelations($relations);
    }

    /**
     * Assigns fields to model
     *
     * @param array $fields
     *
     * @throws ModelException
     */
    protected function assignFields($fields)
    {
        foreach ($fields as $field) {
            if (!$field instanceof FieldInterface) {
                throw new ModelException(sprintf('Field must be an instance of FieldInterface, got "%s"', $this->getType($field)));
            }

            $field->table($this->table);
            $this->fields[$field->name()] = $field;
        }
    }

    /**
     * Assigns indexes to model
     *
     * @param array $indexes
     *
     * @throws ModelException
     */
    protected function assignIndexes($indexes)
    {
        foreach ($indexes as $index) {
            if (!$index instanceof IndexInterface) {
                throw new ModelException(sprintf('Index must be an instance of IndexInterface, got "%s"', $this->getType($index)));
            }

            foreach ($index->fields() as $key => $field) {
                $field = $index->type() == 'foreign' ? $key : $field;

                $this->assertField($field);
            }

            if ($index->type() !== 'foreign') {
                $index->table($this->table);
            }

            $this->indexes[$index->name()] = $index;
        }
    }

    /**
     * Assigns relations to model
     *
     * @param array $relations
     *
     * @throws ModelException
     */
    protected function assignRelations($relations)
    {
        foreach ($relations as $relation) {
            if (!$relation instanceof RelationInterface) {
                throw new ModelException(sprintf('Relation must be an instance of RelationInterface, got "%s"', $this->getType($relation)));
            }

            foreach (array_keys($relation->keys()) as $field) {
                $this->assertField($field);
            }

            $this->relations[$relation->name()] = $relation;
        }
    }

    /**
     * Returns table
     *
     * @return string
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * Returns entity class name
     *
     * @return string
     */
    public function entity()
    {
        return $this->entity;
    }

    /**
     * Returns alias
     *
     * @param string $alias
     *
     * @return string
     */
    public function alias($alias = null)
    {
        if ($alias !== null) {
            $this->alias = $alias;
        }

        return $this->alias;
    }

    /**
     * Returns true if model has field
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasField($field)
    {
        return isset($this->fields[$field]);
    }

    /**
     * Returns array containing field definition
     *
     * @return FieldInterface[]
     */
    public function fields()
    {
        return $this->fields;
    }

    /**
     * Asserts if model has field
     *
     * @param string $field
     *
     * @throws ModelException
     */
    protected function assertField($field)
    {
        if (!$this->hasField($field)) {
            throw new ModelException(sprintf('Unknown field, field "%s" not found in model "%s"', $field, $this->entity));
        }
    }

    /**
     * Returns field definition
     *
     * @param string $field
     *
     * @return FieldInterface
     * @throws ModelException
     */
    public function field($field)
    {
        $this->assertField($field);

        return $this->fields[$field];
    }

    /**
     * Returns array containing names of primary indexes
     *
     * @return FieldInterface[]
     */
    public function primaryFields()
    {
        $result = [];
        foreach ($this->indexes as $index) {
            if (!$index->isPrimary()) {
                continue;
            }

            foreach ($index->fields() as $field) {
                $result[] = $this->field($field);
            }
        }

        return $result;
    }

    /**
     * Returns array of fields from indexes
     *
     * @return FieldInterface[]
     */
    public function indexFields()
    {
        $fields = [];
        foreach ($this->indexes as $index) {
            $fields = array_merge($fields, $index->fields());
        }

        $result = [];
        foreach (array_unique($fields) as $field) {
            $result[] = $this->field($field);
        }

        return $result;
    }

    /**
     * Returns true if index with set name is defined
     *
     * @param string $index
     *
     * @return bool
     */
    public function hasIndex($index)
    {
        return isset($this->indexes[$index]);
    }

    /**
     * Returns all index definitions
     *
     * @return IndexInterface[]
     */
    public function indexes()
    {
        return $this->indexes;
    }


    /**
     * Returns index definition
     *
     * @param string $index
     *
     * @return IndexInterface[]
     * @throws ModelException
     */
    public function index($index)
    {
        if (empty($this->indexes[$index])) {
            throw new ModelException(sprintf('Unknown index, index "%s" not found in model "%s"', $index, $this->entity));
        }

        return $this->indexes[$index];
    }

    /**
     * Returns all relation where field is listed as local key
     *
     * @param string $field
     *
     * @return RelationInterface[]
     */
    public function referredIn($field)
    {
        $result = [];
        foreach ($this->relations as $relation) {
            if (false === $i = array_search($field, $relation->localKeys())) {
                continue;
            }

            $result[$relation->foreignKeys()[$i]] = $relation;
        }

        return $result;
    }

    /**
     * Returns true if at last one relation is defined
     *
     * @return bool
     */
    public function hasRelations()
    {
        return !empty($this->relations);
    }

    /**
     * Returns true if relation to passed entity class is defined
     *
     * @param string $relationName
     *
     * @return bool
     */
    public function hasRelation($relationName)
    {
        return $this->findRelationByName($relationName) !== false;
    }

    /**
     * Returns all relation definition
     *
     * @return RelationInterface[]
     */
    public function relations()
    {
        return $this->relations;
    }

    /**
     * Returns relation definition for passed entity class
     *
     * @param string $relationName
     *
     * @return RelationInterface
     * @throws ModelException
     */
    public function relation($relationName)
    {
        if (!$relation = $this->findRelationByName($relationName)) {
            throw new ModelException(sprintf('Unknown relation, relation "%s" not found in model "%s"', $relationName, $this->entity));
        }

        return $relation;
    }

    /**
     * Finds relation by its name
     *
     * @param string $relationName
     *
     * @return RelationInterface
     */
    protected function findRelationByName($relationName)
    {
        foreach ($this->relations as $relation) {
            if ($relation->name() == $relationName || $relation->entity() == $relationName) {
                return $relation;
            }
        }

        return null;
    }
}
