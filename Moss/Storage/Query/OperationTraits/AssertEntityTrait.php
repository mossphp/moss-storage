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


use Moss\Storage\Query\QueryException;

/**
 * Trait AssertEntityTrait
 * Provides method for asserting if entity is of required type
 *
 * @package Moss\Storage\Query\OperationTraits
 */
trait AssertEntityTrait
{
    use AwareTrait;

    /**
     * Asserts entity instance
     *
     * @param array|object $entity
     *
     * @throws QueryException
     */
    protected function assertEntityInstance($entity)
    {
        $entityClass = $this->model()->entity();

        if ($entity === null) {
            throw new QueryException(sprintf('Missing required entity of class "%s"', $entityClass));
        }

        if (!is_array($entity) && !$entity instanceof $entityClass) {
            throw new QueryException(sprintf('Entity must be an instance of "%s" or array got "%s"', $entityClass, $this->getType($entity)));
        }
    }
}
