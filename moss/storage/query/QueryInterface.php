<?php
namespace moss\storage\query;

use moss\storage\builder\QueryInterface as BuilderInterface;

/**
 * Query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface QueryInterface
{
    // Entity operation types
    const OPERATION_COUNT = 'count';
    const OPERATION_READ_ONE = 'readOne';
    const OPERATION_READ = 'read';
    const OPERATION_INSERT = 'insert';
    const OPERATION_WRITE = 'write';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_CLEAR = 'clear';


    /**
     * Sets query operation
     *
     * @param string $operation
     *
     * @return $this
     */
    public function operation($operation);

    /**
     * Sets fields read by query
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields($fields = array());

    /**
     * Adds aggregation method to read results
     *
     * @param string      $method
     * @param string      $field
     * @param null|string $group
     *
     * @return $this
     */
    public function aggregate($method, $field, $group = null);


    /**
     * Adds condition to query
     *
     * @param string|array $field
     * @param string|array $value
     * @param string       $comparisonOperator
     * @param string       $logicalOperator
     *
     * @return $this
     */
    public function condition($field, $value, $comparisonOperator = BuilderInterface::COMPARISON_EQUAL, $logicalOperator = BuilderInterface::LOGICAL_AND);

    /**
     * Adds order method to query
     *
     * @param string $field
     * @param string $order
     *
     * @return $this
     */
    public function order($field, $order = BuilderInterface::ORDER_ASC);

    /**
     * Sets limits to results
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null);

    /**
     * Adds relation to query
     *
     * @param string $relation
     * @param bool $transparent
     *
     * @return $this
     */
    public function relation($relation, $transparent = false);

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute();

    /**
     * Previews query
     * Write operation is treated as update
     *
     * @return string
     */
    public function preview();

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset();
}