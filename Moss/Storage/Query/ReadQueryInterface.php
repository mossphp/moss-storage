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


/**
 * Query interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface ReadQueryInterface extends QueryInterface, RelationalQueryInterface
{
    /**
     * Sets field names which will be read
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields($fields = []);

    /**
     * Adds field to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function field($field);

    /**
     * Adds where condition to query
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
