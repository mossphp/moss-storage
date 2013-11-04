<?php
namespace moss\storage\builder;

interface QueryBuilderInterface extends BuilderInterface
{
    // Supported operation
    const OPERATION_SELECT = 'select';
    const OPERATION_INSERT = 'insert';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_CLEAR = 'clear';

    // Aggregate methods
    const AGGREGATE_DISTINCT = 'distinct';
    const AGGREGATE_COUNT = 'count';
    const AGGREGATE_AVERAGE = 'average';
    const AGGREGATE_MAX = 'max';
    const AGGREGATE_MIN = 'min';
    const AGGREGATE_SUM = 'sum';

    // Comparison operator types
    const COMPARISON_EQUAL = '=';
    const COMPARISON_NOT_EQUAL = '!=';
    const COMPARISON_LESS = '<';
    const COMPARISON_GREATER = '>';
    const COMPARISON_LESS_EQUAL = '<=';
    const COMPARISON_GREATER_EQUAL = '>=';
    const COMPARISON_LIKE = 'like';
    const COMPARISON_REGEX = 'regex';

    // Logical operator types
    const LOGICAL_AND = 'and';
    const LOGICAL_OR = 'or';

    // Sorting methods
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    /**
     * Sets container name
     *
     * @param string $container
     *
     * @return $this
     */
    public function container($container);

    /**
     * Sets fields to query
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields);

    /**
     * Adds field to query
     *
     * @param string      $field
     * @param null|string $alias
     *
     * @return $this
     */
    public function field($field, $alias = null);

    /**
     * Adds aggregate method to query
     *
     * @param string $method
     * @param string $field
     *
     * @return $this
     */
    public function aggregate($method, $field);

    /**
     * Adds sub query
     *
     * @param QueryBuilderInterface $subSelect
     * @param string          $alias
     *
     * @return $this
     */
    public function sub(QueryBuilderInterface $subSelect, $alias);

    /**
     * Adds grouping to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function group($field);

    /**
     * Adds value to query
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return $this
     */
    public function value($field, $value);

    /**
     * Adds condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comp
     * @param string $log
     *
     * @return $this
     */
    public function condition($field, $value, $comp = self::COMPARISON_EQUAL, $log = self::LOGICAL_OR);

    /**
     * Adds sorting to query
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = SelectInterface::ORDER_DESC);

    /**
     * Sets limits to query
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null);
}
