<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Query;


use Moss\Storage\Query\Relation\RelationInterface;

/**
 * Query interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface RelationalQueryInterface
{
    /**
     * Adds relation to query
     *
     * @param string|array $relation
     *
     * @return $this
     * @throws QueryException
     */
    public function with($relation);

    /**
     * Returns relation instance
     *
     * @param string $relation
     *
     * @return QueryInterface
     */
    public function relation($relation);

    /**
     * Adds relation to query or if relation with same name exists - replaces it with new one
     *
     * @param RelationInterface $relation
     *
     * @return $this
     */
    public function setRelation(RelationInterface $relation);

    /**
     * Returns relation with set name
     *
     * @param string $name
     *
     * @return RelationInterface
     */
    public function getRelation($name);
}
