<?php
namespace moss\storage\builder;

interface SchemaBuilderInterface extends BuilderInterface
{

    // Supported operations
    const OPERATION_CHECK = 'check';
    const OPERATION_INFO = 'info';
    const OPERATION_CREATE = 'create';
    const OPERATION_ALTER = 'alter';
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

    // Index types
    const INDEX_PRIMARY = 'primary';
    const INDEX_INDEX = 'index';
    const INDEX_UNIQUE = 'unique';

    // Info modes
    const INFO_COLUMNS = 'columns';
    const INFO_INDEXES = 'indexes';

    // Additional
    const ALTER_ADD_FIRST = 'first';


    /**
     * Sets container name
     *
     * @param string $container
     *
     * @return $this
     */
    public function container($container);

    /**
     * Changes info retrieval mode
     *
     * @param string $mode
     *
     * @return $this
     */
    public function mode($mode = SchemaBuilderInterface::INFO_COLUMNS);

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
    public function addColumn($name, $type = SchemaBuilderInterface::FIELD_STRING, $attributes = array(), $after = null);

    /**
     * Sets container column
     *
     * @param string      $name
     * @param string      $type
     * @param array       $attributes
     * @param null|string $previous
     *
     * @return $this
     */
    public function alterColumn($name, $type = SchemaBuilderInterface::FIELD_STRING, $attributes = array(), $previous = null);

    /**
     * Removes container index
     *
     * @param $name
     *
     * @return $this
     */
    public function dropColumn($name);

    /**
     * Parsers read columns into array for model
     *
     * @param array $collection
     *
     * @return array
     * @throws BuilderException
     */
    public function parseColumns($collection);

    /**
     * Sets container index
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     *
     * @return $this
     */
    public function addIndex($name, array $fields, $type = SchemaBuilderInterface::INDEX_INDEX);

    /**
     * Alters container index
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     *
     * @return $this
     */
    public function alterIndex($name, array $fields, $type = SchemaBuilderInterface::INDEX_INDEX);

    /**
     * Removes container index
     *
     * @param string $name
     * @param bool $primary
     *
     * @return $this
     */
    public function dropIndex($name, $primary = false);

    /**
     * Parsers read indexes into array for model
     *
     * @param array $collection
     *
     * @return array
     */
    public function parseIndexes($collection);
} 