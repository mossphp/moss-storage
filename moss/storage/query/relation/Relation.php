<?php
namespace moss\storage\query\relation;

use moss\storage\query\EntityQueryInterface;
use moss\storage\model\definition\RelationInterface as RelationDefinitionInterface;

/**
 * Abstract class for basic relation methods
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
abstract class Relation implements RelationInterface
{

    /** @var EntityQueryInterface */
    protected $query;

    /** @var RelationDefinitionInterface */
    protected $relation;

    protected $transparent;

    /**
     * Constructor
     *
     * @param EntityQueryInterface              $query
     * @param RelationDefinitionInterface $relation
     */
    public function __construct(EntityQueryInterface $query, RelationDefinitionInterface $relation)
    {
        $this->query = & $query;
        $this->relation = & $relation;
    }

    /**
     * Returns relation name
     *
     * @return string
     */
    public function name()
    {
        return $this->relation->name();
    }

    /**
     * Sets relation transparency
     *
     * @param bool $transparent
     *
     * @return bool
     */
    public function transparent($transparent = null)
    {
        if ($transparent !== null) {
            $this->transparent = (bool) $transparent;
        }

        return $this->transparent;
    }


    /**
     * Returns relation query instance
     *
     * @return EntityQueryInterface
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Returns relation definition
     *
     * @return \moss\storage\model\definition\RelationInterface
     */
    public function definition()
    {
        return $this->relation;
    }

    /**
     * Throws exception when entity is not required instance
     *
     * @param mixed $entity
     *
     * @return bool
     * @throws RelationException
     */
    protected function assertInstance($entity)
    {
        $entityClass = $this->query
            ->getModel()
            ->entity();

        if (!$entity instanceof $entityClass) {
            throw new RelationException(sprintf('Relation container must be instance of %s, got %s', $entityClass, is_object($entity) ? get_class($entity) : gettype($entity)));
        }

        return true;
    }

    /**
     * Checks if entity fits to relation requirements
     *
     * @param mixed $entity
     *
     * @return bool
     */
    protected function assertEntity($entity)
    {
        foreach ($this->relation->localValues() as $local => $value) {
            if ($this->accessProperty($entity, $local) != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Builds local key from entity relation property values
     *
     * @param mixed $entity
     *
     * @return string
     */
    protected function buildLocalKey($entity)
    {
        $key = '';
        foreach ($this->relation->keys() as $local => $refer) {
            $key .= $local . ':' . $this->accessProperty($entity, $local);
        }

        return $key;
    }

    /**
     * Builds referenced key from entity relation property values
     *
     * @param mixed $entity
     *
     * @return string
     */
    protected function buildReferencedKey($entity)
    {
        $key = '';
        foreach ($this->relation->keys() as $local => $refer) {
            $key .= $local . ':' . $this->accessProperty($entity, $refer);
        }

        return $key;
    }

    /**
     * Returns property value
     * If third parameter passed, value will be set to it
     *
     * @param object     $entity
     * @param string     $field
     * @param null|mixed $value
     *
     * @return mixed|null
     * @todo buffer Reflection instances
     */
    protected function accessProperty(&$entity, $field, $value = null)
    {
        if (is_array($entity)) {
            if ($value !== null) {
                $entity[$field] = $value;
            }

            return array_key_exists($field, $entity) ? $entity[$field] : null;
        }

        $ref = new \ReflectionObject($entity);
        if (!$ref->hasProperty($field)) {
            if ($value !== null) {
                $entity->$field = $value;

                return $entity->$field;
            }

            return null;
        }

        $prop = $ref->getProperty($field);
        $prop->setAccessible(true);

        if ($value !== null) {
            $prop->setValue($entity, $value);
        }

        return $prop->getValue($entity);
    }

    /**
     * Returns entity identifier
     * If more than one primary keys, entity will not be identified
     *
     * @param object $entity
     *
     * @return mixed|null
     * @todo buffer Reflection instances
     */
    protected function identifyEntity($entity)
    {
        $indexes = $this->query
            ->getModel($entity)
            ->primaryFields();

        if (count($indexes) == 1) {
            return $this->accessProperty($entity, reset($indexes));
        }

        $id = array();
        foreach ($indexes as $field) {
            $id[] = $this->accessProperty($entity, $field);
        }

        return implode(':', $id);
    }
}
