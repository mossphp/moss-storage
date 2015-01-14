<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Relation;

use Moss\Storage\Model\ModelInterface;

/**
 * Relationship factory interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface RelationFactoryInterface
{
    /**
     * Adds relation to query with optional conditions and sorting (as key value pairs)
     *
     * @param ModelInterface $model
     * @param string|array   $relation
     * @param array          $conditions
     * @param array          $order
     *
     * @return RelationInterface[]
     */
    public function create(ModelInterface $model, $relation, array $conditions = [], array $order = []);

    /**
     * Splits relation name
     *
     * @param string $relationName
     *
     * @return array
     */
    public function splitRelationName($relationName);
}
