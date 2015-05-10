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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Query interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface QueryInterface
{
    /**
     * Returns connection
     *
     * @return Connection
     */
    public function connection();

    /**
     * Returns query builder instance
     *
     * @return QueryBuilder
     */
    public function builder();

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute();

    /**
     * Adds relation to query
     *
     * @param string|array $relation
     *
     * @return $this
     * @throws QueryException
     */
    public function with($relation);

    /**
     * Returns relation instance
     *
     * @param string $relation
     *
     * @return QueryInterface
     */
    public function relation($relation);

    /**
     * Returns current query string
     *
     * @return string
     */
    public function getSQL();

    /**
     * Returns array with bound values and their placeholders as keys
     *
     * @return array
     */
    public function binds();

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset();
}
