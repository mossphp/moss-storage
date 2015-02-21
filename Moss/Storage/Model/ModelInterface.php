<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model;

use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\Definition\IndexInterface;
use Moss\Storage\Model\Definition\RelationInterface;

/**
 * Model interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface ModelInterface
{
    /**
     * Returns table
     *
     * @return string
     */
    public function table();

    /**
     * Returns entity class name
     *
     * @return string
     */
    public function entity();

    /**
     * Returns alias
     *
     * @param string $alias
     *
     * @return string
     */
    public function alias($alias = null);

    /**
     * Returns true if model has field
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasField($field);

    /**
     * Returns array containing field definition
     *
     * @return array|FieldInterface[]
     */
    public function fields();

    /**
     * Returns field definition
     *
     * @param string $field
     *
     * @return FieldInterface
     * @throws ModelException
     */
    public function field($field);

    /**
     * Returns true if field is primary index
     *
     * @param string $field
     *
     * @return bool
     * @throws ModelException
     */
    public function isPrimary($field);

    /**
     * Returns array containing names of primary indexes
     *
     * @return array|FieldInterface[]
     */
    public function primaryFields();

    /**
     * Returns true if field is index of any type
     *
     * @param string $field
     *
     * @return bool
     * @throws ModelException
     */
    public function isIndex($field);

    /**
     * Returns array containing all indexes in which field appears
     *
     * @param string $field
     *
     * @return array
     * @throws ModelException
     */
    public function inIndex($field);

    /**
     * Returns all relation where field is listed as local key
     *
     * @param string $field
     *
     * @return array|RelationInterface[]
     */
    public function referredIn($field);

    /**
     * Returns array containing names of indexes
     *
     * @return array|FieldInterface[]
     */
    public function indexFields();

    /**
     * Returns all index definitions
     *
     * @return IndexInterface[]
     */
    public function indexes();

    /**
     * Returns index definition
     *
     * @param string $index
     *
     * @return IndexInterface
     * @throws ModelException
     */
    public function index($index);

    /**
     * Returns true if at last one relation is defined
     *
     * @return bool
     */
    public function hasRelations();

    /**
     * Returns true if relation to passed entity class is defined
     *
     * @param string $relationName
     *
     * @return bool
     */
    public function hasRelation($relationName);

    /**
     * Returns all relation definition
     *
     * @return array|RelationInterface[]
     */
    public function relations();

    /**
     * Returns relation definition for passed entity class
     *
     * @param string $relationName
     *
     * @return RelationInterface
     * @throws ModelException
     */
    public function relation($relationName);
}
