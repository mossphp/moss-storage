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

use Moss\Storage\Builder\BuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\QueryInterface;
use Moss\Storage\Schema\SchemaInterface;

/**
 * Abstract class implementing basic storage functionality
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class AbstractStorage implements StorageInterface
{

    /** @var DriverInterface */
    protected $driver;

    /** @var ModelBag */
    protected $bag;

    /** @var QueryInterface|SchemaInterface  */
    protected $instance;

    /**
     * Returns adapter instance for specified entity
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns builder instance
     *
     * @return BuilderInterface
     */
    public function getBuilder()
    {
        return $this->instance->builder();
    }

    /**
     * Registers model into storage
     *
     * @param string         $alias
     * @param ModelInterface $model
     *
     * @return Storage
     */
    public function register(ModelInterface $model, $alias = null)
    {
        $this->bag->set($model, $alias);

        return $this;
    }

    /**
     * Returns all registered models
     *
     * @return ModelBag
     */
    public function getModels()
    {
        return $this->bag;
    }

    /**
     * Starts transaction
     *
     * @return $this
     */
    public function transactionStart()
    {
        $this->driver->transactionStart();

        return $this;
    }

    /**
     * Commits transaction
     *
     * @return $this
     */
    public function transactionCommit()
    {
        $this->driver->transactionCommit();

        return $this;
    }

    /**
     * RollBacks transaction
     *
     * @return $this
     */
    public function transactionRollback()
    {
        $this->driver->transactionRollback();

        return $this;
    }

    /**
     * Returns true if in transaction
     *
     * @return bool
     */
    public function transactionCheck()
    {
        return $this->driver->transactionCheck();
    }
}
