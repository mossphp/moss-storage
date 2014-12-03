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

use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Builder\QueryBuilderInterface as BuilderInterface;

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
     * Sets check operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function check($entity = []);

    /**
     * Sets create operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function create($entity = []);

    /**
     * Sets alter operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function alter($entity = []);

    /**
     * Sets drop operation
     *
     * @param array $entity
     *
     * @return $this
     */
    public function drop($entity = []);

    /**
     * Sets query operation
     *
     * @param string $operation
     * @param array  $entity
     *
     * @return $this
     */
    public function operation($operation, $entity = []);

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
