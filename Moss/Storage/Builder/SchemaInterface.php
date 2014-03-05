<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder;

/**
 * MySQL schema builder interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\MySQL
 */
interface SchemaInterface extends BuilderInterface
{
    // Schema operations
    const OPERATION_CHECK = 'check';
    const OPERATION_INFO = 'info';
    const OPERATION_CREATE = 'create';
    const OPERATION_ADD = 'add';
    const OPERATION_CHANGE = 'change';
    const OPERATION_REMOVE = 'remove';
    const OPERATION_DROP = 'drop';

    // Field types
    const FIELD_BOOLEAN = 'boolean';
    const FIELD_INTEGER = 'integer';
    const FIELD_DECIMAL = 'decimal';
    const FIELD_STRING = 'string';
    const FIELD_DATETIME = 'datetime';
    const FIELD_SERIAL = 'serial';

    // Attributes
    const ATTRIBUTE_UNSIGNED = 'unsigned';
    const ATTRIBUTE_DEFAULT = 'default';
    const ATTRIBUTE_AUTO = 'auto_increment';
    const ATTRIBUTE_NULL = 'null';
    const ATTRIBUTE_LENGTH = 'length';
    const ATTRIBUTE_PRECISION = 'precision';
    const ATTRIBUTE_COMMENT = 'comment';

    // Index types
    const INDEX_PRIMARY = 'primary';
    const INDEX_FOREIGN = 'foreign';
    const INDEX_INDEX = 'index';
    const INDEX_UNIQUE = 'unique';

    /**
     * Sets check operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function check($table);

    /**
     * Sets check operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function info($table);

    /**
     * Sets check operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function create($table);

    /**
     * Sets check operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function add($table);

    /**
     * Sets change operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function change($table);

    /**
     * Sets remove operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function remove($table);

    /**
     * Sets drop operation on table
     *
     * @param string $table
     *
     * @return $this
     */
    public function drop($table);

    /**
     * Sets table column
     *
     * @param string      $name
     * @param string      $type
     * @param array       $attributes
     * @param null|string $after
     *
     * @return $this
     */
    public function column($name, $type = self::FIELD_STRING, $attributes = array(), $after = null);

    /**
     * Sets key/index to table
     *
     * @param array $localFields
     *
     * @return $this
     */
    public function primary(array $localFields);

    /**
     * Sets key/index to table
     *
     * @param string $name
     * @param array  $fields
     * @param string $table
     *
     * @return $this
     */
    public function foreign($name, array $fields, $table);

    /**
     * Sets key/index to table
     *
     * @param string $name
     * @param array  $fields
     *
     * @return $this
     */
    public function unique($name, array $fields);

    /**
     * Sets key/index to table
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     * @param null   $table
     *
     * @return $this
     */
    public function index($name, array $fields, $type = self::INDEX_INDEX, $table = null);

    /**
     * Parsers create table statement into array
     *
     * @param string $statement
     *
     * @return array
     */
    public function parse($statement);
}
