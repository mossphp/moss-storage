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
 * @todo This should be renamed to QueryInterface when original one is no more needed
 */
interface QueryBaseInterface
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
     * Adds relation to query
     *
     * @param string|array $relation
     * @param array        $conditions
     * @param array        $order
     *
     * @return $this
     * @throws QueryException
     */
    public function with($relation, array $conditions = [], array $order = []);

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
