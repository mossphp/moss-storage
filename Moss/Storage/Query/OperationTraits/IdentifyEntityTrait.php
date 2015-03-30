<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\OperationTraits;

/**
 * Trait IdentifyEntityTrait
 * Allows to identify entities
 *
 * @package Moss\Storage\Query
 */
trait IdentifyEntityTrait
{
    use AwareTrait;

    /**
     * Assigns passed identifier to primary key
     * Possible only when entity has one primary key
     *
     * @param object $entity
     * @param int|string   $identifier
     *
     * @return void
     */
    protected function identifyEntity($entity, $identifier)
    {
        $primaryKeys = $this->model()->primaryFields();
        if (count($primaryKeys) !== 1) {
            return;
        }

        $field = reset($primaryKeys)->name();

        $this->setPropertyValue($entity, $field, $identifier);
    }

    /**
     * Sets property value
     *
     * @param null|array|object $entity
     * @param string            $field
     * @param mixed             $value
     *
     * @return void
     */
    abstract protected function setPropertyValue($entity, $field, $value);
}
