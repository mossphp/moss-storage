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

use Moss\Storage\Query\QueryInterface;

/**
 * Relation interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Query
 */
interface RelationInterface
{
    // Relation types
    const RELATION_ONE = 'one';
    const RELATION_MANY = 'many';
    const RELATION_ONE_TROUGH = 'oneTrough';
    const RELATION_MANY_TROUGH = 'manyTrough';

    /**
     * Returns relation name
     *
     * @return string
     */
    public function name();

    /**
     * Returns relation query instance
     *
     * @return QueryInterface
     */
    public function query();

    /**
     * Adds sub relation
     *
     * @param string $relation
     *
     * @return $this
     */
    public function with($relation);

    /**
     * Returns sub relation instance
     *
     * @param string $relation
     *
     * @return QueryInterface
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

    /**
     * Executes clear relation
     */
    public function clear();
}
