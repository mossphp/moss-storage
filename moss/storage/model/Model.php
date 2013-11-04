<?php
namespace moss\storage\model;

use moss\storage\model\definition\FieldInterface;
use moss\storage\model\definition\IndexInterface;
use moss\storage\model\definition\RelationInterface;

/**
 * Entity model representation for storage
 *
 * @package moss storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class Model implements ModelInterface
{

    protected $container;
    protected $entity;

    /** @var array|FieldInterface[] */
    protected $fields = array();

    /** @var array|IndexInterface[] */
    protected $primary = array();

    /** @var array|IndexInterface[] */
    protected $indexes = array();

    /** @var array|RelationInterface[] */
    protected $relations = array();

    /**
     * Constructor
     *
     * @param string                    $entityClass
     * @param string                    $container
     * @param array|FieldInterface[]    $fields
     * @param array|IndexInterface[]    $indexes
     * @param array|RelationInterface[] $relations
     *
     * @throws ModelException
     */
    public function __construct($entityClass, $container, $fields, $indexes = array(), $relations = array())
    {
        $this->container = $container;
        $this->entity = ltrim($entityClass, '\\');

        foreach ($fields as $field) {
            if (!$field instanceof FieldInterface) {
                throw new ModelException(sprintf('Field must be an instance of FieldInterface, got "%s"', gettype($field)));
            }

            $this->setField($field);
        }

        foreach ($indexes as $index) {
            if (!$index instanceof IndexInterface) {
                throw new ModelException(sprintf('Index must be an instance of IndexInterface, got "%s"', gettype($index)));
            }

            $this->setIndex($index);
        }

        foreach ($relations as $relation) {
            if (!$relation instanceof RelationInterface) {
                throw new ModelException(sprintf('Relation must be an instance of RelationInterface, got "%s"', gettype($relation)));
            }

            $this->setRelation($relation);
        }
    }

    /**
     * Returns container
     *
     * @return string
     */
    public function container()
    {
        return $this->container;
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
     * Sets field in model
     *
     * @param FieldInterface $field
     *
     * @return $this
     */
    public function setField(FieldInterface $field)
    {
        $this->fields[$field->name()] = $field;

        return $this;
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
        if (!$this->hasField($field)) {
            throw new ModelException(sprintf('Field "%s" not found in model "%s"', $field, $this->entity));
        }

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
        if (!$this->hasField($field)) {
            throw new ModelException(sprintf('Unknown field "%s" in model "%s"', $field, $this->entity));
        }

        foreach ($this->indexes as $index) {
            if ($index->isPrimary() && $index->hasField($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns array containing names of primary indexes
     *
     * @return array
     */
    public function primaryFields()
    {
        $result = array();
        foreach ($this->indexes as $index) {
            if (!$index->isPrimary()) {
                continue;
            }

            $result = array_merge($result, $index->fields());
        }

        return $result;
    }

    /**
     * Sets index in model
     *
     * @param IndexInterface $index
     *
     * @return $this
     * @throws ModelException
     */
    public function setIndex(IndexInterface $index)
    {
        foreach ($index->fields() as $field) {
            if (!$this->hasField($field)) {
                throw new ModelException(sprintf('Index field "%s" does not exist in entity model "%s"', $field, $this->entity));
            }
        }

        $this->indexes[$index->name()] = $index;

        return $this;
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
        if (!$this->hasField($field)) {
            throw new ModelException(sprintf('Unknown field "%s" in model "%s"', $field, $this->entity));
        }

        foreach ($this->indexes as $index) {
            if ($index->hasField($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns array containing names of indexes
     *
     * @return array|IndexInterface[]
     */
    public function indexFields()
    {
        $result = array();
        foreach ($this->indexes as $index) {
            $result = array_merge($result, $index->fields());
        }

        return $result;
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
            throw new ModelException(sprintf('Unknown index "%s" in model "%s"', $index, $this->entity));
        }

        return $this->indexes[$index];
    }

    /**
     * Sets relation in model
     *
     * @param RelationInterface $relation
     *
     * @return $this
     * @throws ModelException
     */
    public function setRelation(RelationInterface $relation)
    {
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
            throw new ModelException(sprintf('Relation "%s" not found in model "%s"', $relationName, $this->entity));
        }

        return $this->relations[$relationName];
    }
}
