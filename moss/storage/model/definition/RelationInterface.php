<?php
namespace moss\storage\model\definition;

/**
 * Relation definition interface for entity model
 *
 * @package moss storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface RelationInterface
{

    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name();

    /**
     * Returns relation type
     *
     * @return string
     */
    public function type();

    /**
     * Returns relation entity class name
     *
     * @return string
     */
    public function entity();

    /**
     * Returns container name
     *
     * @return string
     */
    public function container();

    /**
     * Returns associative array containing local key - foreign key pairs
     *
     * @return array
     */
    public function keys();

    /**
     * Returns associative array containing local key - value pairs
     *
     * @param array $localValues ;
     *
     * @return array
     */
    public function localValues($localValues = array());

    /**
     * Returns associative array containing foreign key - value pairs
     *
     * @param array $foreignValues ;
     *
     * @return array
     */
    public function foreignValues($foreignValues = array());
}
