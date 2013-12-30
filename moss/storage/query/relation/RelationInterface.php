<?php
namespace moss\storage\query\relation;

use moss\storage\query\QueryInterface;

/**
 * Relation interface
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface RelationInterface
{
    // Relation types
    const RELATION_ONE = 'one';
    const RELATION_MANY = 'many';

    /**
     * Returns relation name
     *
     * @return string
     */
    public function name();

    /**
     * Sets relation transparency
     *
     * @param bool $transparent
     *
     * @return bool
     */
    public function transparent($transparent = null);

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
    public function write($result);

    /**
     * Executes delete relation
     *
     * @param array|\ArrayAccess $result
     *
     * @return array|\ArrayAccess
     */
    public function delete($result);

    /**
     * Executes clear relation
     */
    public function clear();
}
