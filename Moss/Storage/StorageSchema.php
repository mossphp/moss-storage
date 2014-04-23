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

use Moss\Storage\Builder\SchemaBuilderInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Schema\SchemaInterface;
use Moss\Storage\Schema\Schema;

/**
 * Facade that eases calling queries
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class StorageSchema extends AbstractStorage
{
    /** @var SchemaInterface|Schema */
    protected $instance;

    /**
     * Constructor
     *
     * @param DriverInterface        $driver
     * @param SchemaBuilderInterface $builder
     */
    public function __construct(DriverInterface $driver, SchemaBuilderInterface $builder)
    {
        $this->driver = & $driver;
        $this->bag = new ModelBag();
        $this->instance = new Schema($this->driver, $builder, $this->bag);
    }

    /**
     * Returns true if entity table exists
     *
     * @param string|object $entity
     *
     * @return Schema
     */
    public function check($entity = null)
    {
        return $this->schema()
            ->check($entity);
    }

    /**
     * Returns query creating entity table
     *
     * @param string|object $entity
     *
     * @return Schema
     */
    public function create($entity = null)
    {
        return $this->schema()
            ->create($entity);
    }

    /**
     * Returns query altering entity table
     *
     * @param string|object $entity
     *
     * @return Schema
     */
    public function alter($entity = null)
    {
        return $this->schema()
            ->alter($entity);
    }

    /**
     * Returns query removing entity table
     *
     * @param string|object $entity
     *
     * @return Schema
     */
    public function drop($entity = null)
    {
        return $this->schema()
            ->drop($entity);
    }

    /**
     * @return Schema
     */
    private function schema()
    {
        return clone $this->instance;
    }
}
