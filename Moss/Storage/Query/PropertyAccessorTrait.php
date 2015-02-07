<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query;


trait PropertyAccessorTrait
{
    /**
     * Returns property value
     *
     * @param null|array|object $entity
     * @param string            $field
     * @param mixed             $default
     *
     * @return mixed
     * @throws QueryException
     */
    protected function getPropertyValue($entity, $field, $default = null)
    {
        if (!$entity) {
            throw new QueryException('Unable to access entity properties, missing instance');
        }

        if (is_array($entity) || $entity instanceof \ArrayAccess) {
            return isset($entity[$field]) ? $entity[$field] : $default;
        }

        $ref = new \ReflectionObject($entity);
        if (!$ref->hasProperty($field)) {
            return $default;
        }

        $prop = $ref->getProperty($field);
        $prop->setAccessible(true);

        return $prop->getValue($entity);
    }

    /**
     * Sets property value
     *
     * @param null|array|object $entity
     * @param string            $field
     * @param mixed             $value
     *
     * @return void
     * @throws QueryException
     */
    protected function setPropertyValue($entity, $field, $value)
    {
        if (!$entity) {
            throw new QueryException('Unable to access entity properties, missing instance');
        }

        if (is_array($entity) || $entity instanceof \ArrayAccess) {
            $entity[$field] = $value;

            return;
        }

        $ref = new \ReflectionObject($entity);
        if (!$ref->hasProperty($field)) {
            $entity->{$field} = $value;

            return;
        }

        $prop = $ref->getProperty($field);
        $prop->setAccessible(true);
        $prop->setValue($entity, $value);
    }
}
