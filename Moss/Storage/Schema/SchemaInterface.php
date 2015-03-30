<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Schema;

use Doctrine\DBAL\Connection;

/**
 * Schema interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface SchemaInterface
{
    /**
     * Returns driver instance
     *
     * @return Connection
     */
    public function connection();

    /**
     * Sets create operation
     *
     * @param array $entityName
     *
     * @return $this
     */
    public function create(array $entityName = []);

    /**
     * Sets alter operation
     *
     * @param array $entityName
     *
     * @return $this
     */
    public function alter(array $entityName = []);

    /**
     * Sets drop operation
     *
     * @param array $entityName
     *
     * @return $this
     */
    public function drop(array $entityName = []);

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute();

    /**
     * Returns array of queries that will be executed
     *
     * @return array
     */
    public function queryString();

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset();
}
