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
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Query\Relation\RelationFactory;
use Moss\Storage\Query\Relation\RelationFactoryInterface;
use Moss\Storage\NormalizeNamespaceTrait;
use Moss\Storage\StorageException;

/**
 * Storage - query source, used to create and execute CRUD operations on entities
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Storage implements StorageInterface
{
    use NormalizeNamespaceTrait;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ModelBag
     */
    protected $models;

    /**
     * @var RelationFactoryInterface
     */
    protected $factory;

    /**
     * Constructor
     *
     * @param Connection $connection
     * @param ModelBag   $models
     */
    public function __construct(Connection $connection, ModelBag $models)
    {
        $this->connection = $connection;
        $this->models = $models;
        $this->factory = new RelationFactory($this, $models);
    }

    /**
     * Returns connection
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * Sets read operation
     *
     * @param string $entity
     *
     * @return ReadQueryInterface
     */
    public function read($entity)
    {
        return new ReadQuery(
            $this->connection,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Sets read one operation
     *
     * @param string $entity
     *
     * @return ReadQueryInterface
     */
    public function readOne($entity)
    {
        return new ReadOneQuery(
            $this->connection,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Sets write operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return WriteQueryInterface
     */
    public function write($instance, $entity = null)
    {
        list($instance, $entity) = $this->reassignEntity($instance, $entity);

        return new WriteQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Sets update operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return UpdateQueryInterface
     */
    public function update($instance, $entity = null)
    {
        list($instance, $entity) = $this->reassignEntity($instance, $entity);

        return new UpdateQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Sets insert operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return InsertQueryInterface
     */
    public function insert($instance, $entity = null)
    {
        list($instance, $entity) = $this->reassignEntity($instance, $entity);

        return new InsertQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Sets delete operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return DeleteQueryInterface
     */
    public function delete($instance, $entity = null)
    {
        list($instance, $entity) = $this->reassignEntity($instance, $entity);

        return new DeleteQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Reassigns entity/instance variables if entity is object
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return array
     * @throws StorageException
     */
    protected function reassignEntity($instance, $entity = null)
    {
        if ($entity === null && !is_object($instance)) {
            throw new StorageException('When entity class is omitted, instance must be an object');
        }

        if (is_object($instance)) {
            return [$instance, $this->normalizeNamespace($instance)];
        }

        return [$instance, $entity];
    }
}
