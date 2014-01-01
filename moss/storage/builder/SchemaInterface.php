<?php
namespace moss\storage\builder;

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
     * Sets container column
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
     * Sets key/index to container
     *
     * @param array $localFields
     *
     * @return $this
     */
    public function primary(array $localFields);

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $fields
     * @param string $container
     *
     * @return $this
     */
    public function foreign($name, array $fields, $container);

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $fields
     *
     * @return $this
     */
    public function unique($name, array $fields);

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     * @param null   $container
     *
     * @return $this
     */
    public function index($name, array $fields, $type = self::INDEX_INDEX, $container = null);

    /**
     * Parsers create table statement into array
     *
     * @param string $statement
     *
     * @return array
     */
    public function parse($statement);
} 