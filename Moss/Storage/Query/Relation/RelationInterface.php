<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Query\Relation;

/**
 * Relation interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface RelationInterface
{
    /**
     * Returns relation name
     *
     * @return string
     */
    public function name();

    /**
     * Adds where condition to query
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = '==', $logical = 'and');

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
     * Adds sub relation
     *
     * @param string $relation
     *
     * @return $this
     */
    public function with($relation);

    /**
     * Returns query instance
     *
     * @param string $relation
     *
     * @return RelationInterface
     */
    public function relation($relation);

    /**
     * Executes read relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function read(&$result);

    /**
     * Executes write relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function write(&$result);

    /**
     * Executes delete relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function delete(&$result);
}
