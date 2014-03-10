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
use Moss\Storage\Builder\QueryInterface;
use Moss\Storage\Builder\SchemaInterface;
use Moss\Storage\Driver\DriverInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Query;
use Moss\Storage\Query\Schema;

/**
 * Facade that eases calling queries
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Storage implements StorageInterface
{

    /** @var DriverInterface */
    protected $driver;

    /** @var BuilderInterface */
    protected $builders = array(
        'query' => null,
        'schema' => null
    );

    /** @var ModelBag */
    public $models = array();

    /**
     * Constructor
     *
     * @param DriverInterface    $driver
     * @param BuilderInterface[] $builders
     *
     * @throws StorageException
     */
    public function __construct(DriverInterface $driver, array $builders)
    {
        $this->driver = & $driver;
        $this->models = new ModelBag();

        foreach ($builders as $builder) {
            if (!$builder instanceof BuilderInterface) {
                throw new StorageException('Builder must be an instance of BuilderInterface');
            }

            if ($builder instanceof QueryInterface) {
                $this->builders['query'] = & $builder;
            }

            if ($builder instanceof SchemaInterface) {
                $this->builders['schema'] = & $builder;
            }

            unset($builder);
        }
    }

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
     * Registers model into storage
     *
     * @param string         $alias
     * @param ModelInterface $model
     *
     * @return Storage
     */
    public function register(ModelInterface $model, $alias = null)
    {
        $this->models->set($model, $alias);

        return $this;
    }

    /**
     * Returns all registered models
     *
     * @return ModelBag
     */
    public function models()
    {
        return $this->models;
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
    protected function schema()
    {
        $schema = new Schema($this->driver, $this->builders['schema'], $this->models);

        return $schema;
    }

    /**
     * Returns count query for passed entity class
     *
     * @param string $entity
     *
     * @return Query
     */
    public function count($entity)
    {
        return $this->query()
            ->number($entity);
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
    protected function query()
    {
        $query = new Query($this->driver, $this->builders['query'], $this->models);

        return $query;
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
