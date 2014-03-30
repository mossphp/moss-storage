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
 * MySQL query builder interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\MySQL
 */
interface QueryBuilderInterface extends BuilderInterface
{
    const SEPARATOR = '.';

    /**
     * Sets select operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function select($table, $alias = null);

    /**
     * Sets insert operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function insert($table, $alias = null);

    /**
     * Sets update operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function update($table, $alias = null);

    /**
     * Sets delete operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function delete($table, $alias = null);

    /**
     * Sets clear operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function clear($table, $alias = null);

    /**
     * Adds fields to query
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
     * Adds sub query

     *
*@param QueryBuilderInterface $query
     * @param string         $alias

     *
*@return $this
     */
    public function sub(QueryBuilderInterface $query, $alias);

    /**
     * Adds values to query
     *
     * @param array $values
     *
     * @return $this
     */
    public function values(array $values);

    /**
     * Adds value to query
     *
     * @param string $col
     * @param mixed  $value
     *
     * @return $this
     */
    public function value($col, $value);

    /**
     * Adds inner join with set table
     *
     * @param string $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     */
    public function innerJoin($table, array $joins, $alias = null);

    /**
     * Adds left join with set table
     *
     * @param string $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     */
    public function leftJoin($table, array $joins, $alias = null);

    /**
     * Adds right join with set table
     *
     * @param string $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     */
    public function rightJoin($table, array $joins, $alias = null);

    /**
     * Adds join to query
     *
     * @param string $type
     * @param string $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     */
    public function join($type, $table, array $joins, $alias = null);

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
}
