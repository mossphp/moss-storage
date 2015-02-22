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
     * @return $this
     */
    public function relation(ModelInterface $model, $relation);

    /**
     * Adds where condition to relation
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = '=', $logical = 'and');

    /**
     * Adds sorting to relation
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = 'desc');

    /**
     * Sets limits to relation
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null);

    /**
     * Builds relation instance
     *
     * @return RelationInterface
     */
    public function build();

    /**
     * Resets builder
     *
     * @return $this
     */
    public function reset();

    /**
     * Splits relation names
     *
     * @param string $relationName
     *
     * @return array
     */
    public function splitRelationName($relationName);
}
