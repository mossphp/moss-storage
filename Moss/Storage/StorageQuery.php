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

use Moss\Storage\Builder\QueryBuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Query\Query;

/**
 * Facade that eases calling queries
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class StorageQuery extends AbstractStorage
{
    /**
     * Constructor
     *
     * @param DriverInterface       $driver
     * @param QueryBuilderInterface $builder
     */
    public function __construct(DriverInterface $driver, QueryBuilderInterface $builder)
    {
        $this->driver = & $driver;
        $this->bag = new ModelBag();
        $this->instance = new Query($this->driver, $builder, $this->bag);
    }

    /**
     * Returns count query for passed entity class
     *
     * @param string $entity
     *
     * @return Query
     */
    public function num($entity)
    {
        return $this->query()
            ->num($entity);
    }

    /**
     * Returns read single entity for passed entity class
     *
     * @param string $entity
     *
     * @return Query
     */
    public function readOne($entity)
    {
        return $this->query()
            ->readOne($entity);
    }

    /**
     * Returns read query for passed entity class
     *
     * @param string $entity
     *
     * @return Query
     */
    public function read($entity)
    {
        return $this->query()
            ->read($entity);
    }

    /**
     * Returns write query for passed entity object or entity class
     *
     * @param string|object $instance
     *
     * @return Query
     */
    public function write($instance)
    {
        return $this->query()
            ->write(get_class($instance), $instance);
    }

    /**
     * Returns insert query for passed entity object or entity class
     *
     * @param string|object $instance
     *
     * @return Query
     */
    public function insert($instance)
    {
        return $this->query()
            ->insert(get_class($instance), $instance);
    }

    /**
     * Returns update query for passed entity object or entity class
     *
     * @param string|object $instance
     *
     * @return Query
     */
    public function update($instance)
    {
        return $this->query()
            ->update(get_class($instance), $instance);
    }

    /**
     * Returns delete query for passed entity object or entity class
     *
     * @param string|object $instance
     *
     * @return Query
     */
    public function delete($instance)
    {
        return $this->query()
            ->delete(get_class($instance), $instance);
    }

    /**
     * Returns clear query for passed entity class
     *
     * @param string $entity
     *
     * @return Query
     */
    public function clear($entity)
    {
        return $this->query()
            ->clear($entity);
    }

    /**
     * @return Query
     */
    private function query()
    {
        return clone $this->instance;
    }
}
