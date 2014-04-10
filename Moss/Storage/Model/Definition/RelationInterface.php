<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model\Definition;

/**
 * Relation interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
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
     * Returns relation mediating instance
     *
     * @return string
     */
    public function mediator();

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
     * Returns table name
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
     * Returns array containing local keys
     *
     * @return array
     */
    public function localKeys();

    /**
     * Returns array containing foreign keys
     *
     * @return array
     */
    public function foreignKeys();

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
