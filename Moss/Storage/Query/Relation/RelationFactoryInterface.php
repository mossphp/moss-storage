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
     * Sets model and its relation
     *
     * @param ModelInterface $model
     * @param string         $relation
     *
     * @return RelationInterface
     */
    public function build(ModelInterface $model, $relation);

    /**
     * Splits relation names
     *
     * @param string $relationName
     *
     * @return array
     */
    public function splitRelationName($relationName);
}
