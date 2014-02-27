<?php
namespace Moss\Storage\Query\Relation;

use Moss\Storage\Query\QueryInterface;

/**
 * Relation interface
 *
 * @package Moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
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
