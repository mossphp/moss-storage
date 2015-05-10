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
 * Interface for property accessor
 * Grants access to entity properties
 *
 * @package Moss\Storage
 */
interface AccessorInterface
{
    /**
     * Assigns passed identifier to primary key
     * Possible only when entity has one primary key
     *
     * @param ModelInterface $model
     * @param mixed $entity
     * @param int|string   $identifier
     *
     * @return void
     */
    public function identifyEntity(ModelInterface $model, $entity, $identifier);

    /**
     * Returns property value
     *
     * @param null|array|object $entity
     * @param string            $field
     * @param mixed             $default
     *
     * @return mixed
     */
    public function getPropertyValue($entity, $field, $default = null);

    /**
     * Sets property value
     *
     * @param null|array|object $entity
     * @param string            $field
     * @param mixed             $value
     *
     * @throws AccessorException
     */
    public function setPropertyValue($entity, $field, $value);

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
    public function addPropertyValue($entity, $field, $value);
}
