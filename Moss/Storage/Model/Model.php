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

use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\Definition\IndexInterface;
use Moss\Storage\Model\Definition\RelationInterface;

/**
 * Model describing entity and its relationship to other entities
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Model implements ModelInterface
{

    protected $table;
    protected $entity;

    /** @var array|FieldInterface[] */
    protected $fields = array();

    /** @var array|IndexInterface[] */
    protected $indexes = array();

    /** @var array|RelationInterface[] */
    protected $relations = array();

    /**
     * Constructor
     *
     * @param string                    $entityClass
     * @param string                    $table
     * @param array|FieldInterface[]    $fields
     * @param array|IndexInterface[]    $indexes
     * @param array|RelationInterface[] $relations
     *
     * @throws ModelException
     */
    public function __construct($entityClass, $table, array $fields, array $indexes = array(), array $relations = array())
    {
        $this->table = $table;
        $this->entity = $entityClass ? ltrim($entityClass, '\\') : null;

        $this->assignFields($fields);
        $this->assignIndexes($indexes);
        $this->assignRelations($relations);
    }

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

    protected function assignIndexes($indexes)
    {
        foreach ($indexes as $index) {
            if (!$index instanceof IndexInterface) {
                throw new ModelException(sprintf('Index must be an instance of IndexInterface, got "%s"', $this->getType($index)));
            }

            foreach ($index->fields() as $key => $field) {
                $field = $index->type() == 'foreign' ? $key : $field;

                if (!$this->hasField($field)) {
                    throw new ModelException(sprintf('Index field "%s" does not exist in entity model "%s"', $field, $this->entity));
                }
            }

            if ($index->type() !== 'foreign') {
                $index->table($this->table);
            }

            $this->indexes[$index->name()] = $index;
        }
    }

    protected function assignRelations($relations)
    {
        foreach ($relations as $relation) {
            if (!$relation instanceof RelationInterface) {
                throw new ModelException(sprintf('Relation must be an instance of RelationInterface, got "%s"', $this->getType($relation)));
            }

            foreach ($relation->keys() as $field => $trash) {
                if (!$this->hasField($field)) {
                    throw new ModelException(sprintf('Relation field "%s" does not exist in entity model "%s"', $field, $this->entity));
                }
            }

            foreach ($relation->localValues() as $field => $trash) {
                if (!$this->hasField($field)) {
                    throw new ModelException(sprintf('Relation field "%s" does not exist in entity model "%s"', $field, $this->entity));
                }
            }

            $this->relations[$relation->name()] = $relation;
        }
    }

    private function getType($var)
    {
        if (is_object($var)) {
            return get_class($var);
        }

        return gettype($var);
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
     * @return array|FieldInterface[]
     */
    public function fields()
    {
        return $this->fields;
    }

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
     * Returns true if field is primary index
     *
     * @param string $field
     *
     * @return bool
     * @throws ModelException
     */
    public function isPrimary($field)
    {
        $this->assertField($field);

        return in_array($this->field($field), $this->primaryFields(), true);
    }

    /**
     * Returns array containing names of primary indexes
     *
     * @return array|FieldInterface[]
     */
    public function primaryFields()
    {
        $result = array();
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
     * Returns true if field is index of any type
     *
     * @param string $field
     *
     * @return bool
     * @throws ModelException
     */
    public function isIndex($field)
    {
        $this->assertField($field);

        return in_array($this->field($field), $this->indexFields(), true);
    }

    /**
     * Returns array containing all indexes in which field appears
     *
     * @param string $field
     *
     * @return bool
     * @throws ModelException
     */
    public function inIndex($field)
    {
        $this->assertField($field);

        $result = array();
        foreach ($this->indexes as $index) {
            if ($index->hasField($field)) {
                $result[] = $index;
            }
        }

        return $result;
    }

    /**
     * Returns array containing names of indexes
     *
     * @return array|FieldInterface[]
     */
    public function indexFields()
    {
        $fields = array();
        foreach ($this->indexes as $index) {
            foreach ($index->fields() as $field) {
                if (isset($fields[$field])) {
                    continue;
                }

                $fields[$field] = $this->field($field);
            }
        }

        return array_values($fields);
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
        return isset($this->relations[$relationName]);
    }

    /**
     * Returns all relation definition
     *
     * @return array|RelationInterface[]
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
        if (!$this->hasRelation($relationName)) {
            throw new ModelException(sprintf('Unknown relation, relation "%s" not found in model "%s"', $relationName, $this->entity));
        }

        return $this->relations[$relationName];
    }
}
