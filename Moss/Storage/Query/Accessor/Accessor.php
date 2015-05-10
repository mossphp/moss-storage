<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Accessor;

use Moss\Storage\Model\ModelInterface;

/**
 * Property accessor
 * Grants access to entity properties
 *
 * @package Moss\Storage
 */
final class Accessor implements AccessorInterface
{
    private $buffer = [];

    /**
     * Assigns passed identifier to primary key
     * Possible only when entity has one primary key
     *
     * @param ModelInterface $model
     * @param mixed          $entity
     * @param int|string     $identifier
     *
     * @return void
     */
    public function identifyEntity(ModelInterface $model, &$entity, $identifier)
    {
        $primaryKeys = $model->primaryFields();
        if (count($primaryKeys) !== 1) {
            return;
        }

        $field = reset($primaryKeys)->name();

        $this->setPropertyValue($entity, $field, $identifier);
    }

    /**
     * Returns property value
     *
     * @param array|object $entity
     * @param string       $field
     * @param mixed        $default
     *
     * @return mixed
     * @throws AccessorException
     */
    public function getPropertyValue($entity, $field, $default = null)
    {
        $this->assertEntity($entity);

        if ($this->hasArrayAccess($entity)) {
            return isset($entity[$field]) ? $entity[$field] : $default;
        }

        $ref = $this->getReflection($entity);
        if (!$ref->hasProperty($field)) {
            return $default;
        }

        return $this->getProperty($ref, $field)->getValue($entity);
    }

    /**
     * Sets property value
     *
     * @param array|object $entity
     * @param string       $field
     * @param mixed        $value
     *
     * @throws AccessorException
     */
    public function setPropertyValue(&$entity, $field, $value)
    {
        $this->assertEntity($entity);

        if ($this->hasArrayAccess($entity)) {
            $entity[$field] = $value;

            return;
        }

        $ref = $this->getReflection($entity);
        if (!$ref->hasProperty($field)) {
            $entity->{$field} = $value;

            return;
        }

        $this->getProperty($ref, $field)->setValue($entity, $value);
    }

    /**
     * Returns true if entity can be treated as array
     *
     * @param mixed $entity
     *
     * @return bool
     */
    private function hasArrayAccess($entity)
    {
        return is_array($entity) || $entity instanceof \ArrayAccess;
    }

    /**
     * Asserts if entity is array or object
     *
     * @param mixed $entity
     *
     * @throws AccessorException
     */
    private function assertEntity($entity)
    {
        if (!is_array($entity) && !is_object($entity)) {
            throw new AccessorException('Unable to access entity properties, missing instance');
        }
    }

    /**
     * Returns object reflection instance
     *
     * @param object $object
     *
     * @return \ReflectionObject
     */
    private function getReflection($object)
    {
        $hash = spl_object_hash($object);

        if (!array_key_exists($hash, $this->buffer)) {
            $this->buffer[$hash] = new \ReflectionObject($object);
        }

        return $this->buffer[$hash];
    }

    /**
     * Returns object property instance
     *
     * @param \ReflectionObject $ref
     * @param string            $property
     *
     * @return \ReflectionProperty
     */
    private function getProperty(\ReflectionObject $ref, $property)
    {
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);

        return $prop;
    }

    /**
     * Adds value to array property
     * If property is not an array - will be converted into one preserving existing value as first element
     *
     * @param mixed  $entity
     * @param string $field
     * @param mixed  $value
     *
     * @throws AccessorException
     */
    public function addPropertyValue(&$entity, $field, $value)
    {
        $container = $this->getPropertyValue($entity, $field, $entity);

        if (!is_array($container)) {
            $container = empty($container) ? [] : [$container];
        }
        $container[] = $value;

        $this->setPropertyValue($entity, $field, $container);
    }
}
