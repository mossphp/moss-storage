<?php
namespace moss\storage\query;

use moss\storage\builder\QueryBuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelInterface;
use moss\storage\query\relation\RelationInterface;

/**
 * Query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface EntityQueryInterface extends QueryInterface
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
     * Sets fields read by query
     *
     * @param array $fields
     *
     * @return $this
     * @throws QueryException
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
     * @throws QueryException
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
     * @throws QueryException
     */
    public function condition($field, $value, $comparisonOperator = QueryBuilderInterface::COMPARISON_EQUAL, $logicalOperator = QueryBuilderInterface::LOGICAL_AND);

    /**
     * Adds order method to query
     *
     * @param string $field
     * @param string $order
     *
     * @return $this
     * @throws QueryException
     */
    public function order($field, $order = QueryBuilderInterface::ORDER_ASC);

    /**
     * Sets limits to results
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     * @throws QueryException
     */
    public function limit($limit, $offset = null);

    /**
     * Adds relation to query
     *
     * @param RelationInterface $relation
     *
     * @return $this
     */
    public function setRelation(RelationInterface $relation);

    /**
     * Returns relation in query
     *
     * @param string $relationName
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function getRelation($relationName);
}