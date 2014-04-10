<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Query;

use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Builder\QueryBuilderInterface as BuilderInterface;

/**
 * Query interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface QueryInterface
{
    /**
     * Returns driver instance
     *
     * @return DriverInterface
     */
    public function driver();

    /**
     * Returns builder instance
     *
     * @return BuilderInterface
     */
    public function builder();

    /**
     * Sets counting operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function num($entity);

    /**
     * Sets read operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function read($entity);

    /**
     * Sets read one operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function readOne($entity);

    /**
     * Sets write operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function write($entity, $instance);

    /**
     * Sets insert operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function insert($entity, $instance);

    /**
     * Sets update operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function update($entity, $instance);

    /**
     * Sets delete operation
     *
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function delete($entity, $instance);

    /**
     * Sets clear operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function clear($entity);

    /**
     * Sets query operation
     *
     * @param string $operation
     * @param string $entity
     * @param object $instance
     *
     * @return $this
     */
    public function operation($operation, $entity, $instance = null);

    /**
     * Sets field names which will be read
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields($fields = array());

    /**
     * Adds field to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function field($field);

    /**
     * Adds distinct method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function distinct($field, $alias = null);

    /**
     * Adds count method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function count($field, $alias = null);

    /**
     * Adds average method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function average($field, $alias = null);

    /**
     * Adds max method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function max($field, $alias = null);

    /**
     * Adds min method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function min($field, $alias = null);

    /**
     * Adds sum method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function sum($field, $alias = null);

    /**
     * Adds aggregate method to query
     *
     * @param string $method
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function aggregate($method, $field, $alias = null);

    /**
     * Adds grouping to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function group($field);

    /**
     * Sets field names which values will be written
     *
     * @param array $fields
     *
     * @return $this
     */
    public function values($fields = array());

    /**
     * Adds field which value will be written
     *
     * @param string $field
     *
     * @return $this
     */
    public function value($field);

    /**
     * Adds inner join with set table
     *
     * @param string $entity
     *
     * @return $this
     */
    public function innerJoin($entity);

    /**
     * Adds left join with set table
     *
     * @param string $entity
     *
     * @return $this
     */
    public function leftJoin($entity);

    /**
     * Adds right join with set table
     *
     * @param string $entity
     *
     * @return $this
     */
    public function rightJoin($entity);

    /**
     * Adds join to query
     *
     * @param string $type
     * @param string $entity
     *
     * @return $this
     */
    public function join($type, $entity);

    /**
     * Adds where condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = '==', $logical = 'and');

    /**
     * Adds having condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function having($field, $value, $comparison = '==', $logical = 'and');

    /**
     * Adds sorting to query
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = 'desc');

    /**
     * Sets limits to query
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
     * @param string|array $relation
     * @param array        $conditions
     * @param array        $order
     *
     * @return $this
     * @throws QueryException
     */
    public function with($relation, array $conditions = array(), array $order = array());

    /**
     * Returns query instance from requested relation
     *
     * @param string $relation
     *
     * @return QueryInterface
     */
    public function relation($relation);

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute();

    /**
     * Returns current query string
     *
     * @return string
     */
    public function queryString();

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset();
}