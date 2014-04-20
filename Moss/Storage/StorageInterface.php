<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage;

use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Driver\DriverInterface;

/**
 * Storage interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface StorageInterface
{
    /**
     * Returns adapter instance
     *
     * @return DriverInterface
     */
    public function getDriver();

    /**
     * Registers model into storage
     *
     * @param ModelInterface $model
     * @param string         $alias
     *
     * @return StorageInterface
     */
    public function register(ModelInterface $model, $alias = null);

    /**
     * Returns all registered models
     *
     * @return ModelBag
     */
    public function models();

    /**
     * Starts transaction
     *
     * @return $this
     */
    public function transactionStart();

    /**
     * Commits transaction
     *
     * @return $this
     */
    public function transactionCommit();

    /**
     * RollBacks transaction
     *
     * @return $this
     */
    public function transactionRollback();

    /**
     * Returns true if in transaction
     *
     * @return bool
     */
    public function transactionCheck();
}
