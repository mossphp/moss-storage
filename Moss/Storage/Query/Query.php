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
use Moss\Storage\Converter\ConverterInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;
use Moss\Storage\NormalizeClassNameTrait;

/**
 * Query used to create and execute CRUD operations on entities
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Query
{
    use NormalizeClassNameTrait;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ModelBag
     */
    protected $models;

    /**
     * @var ConverterInterface
     */
    protected $converter;

    /**
     * @var RelationFactoryInterface
     */
    protected $factory;

    /**
     * Constructor
     *
     * @param Connection               $connection
     * @param ModelBag                 $models
     * @param ConverterInterface       $converter
     * @param RelationFactoryInterface $factory
     */
    public function __construct(Connection $connection, ModelBag $models, ConverterInterface $converter, RelationFactoryInterface $factory)
    {
        $this->connection = $connection;
        $this->models = $models;
        $this->converter = $converter;
        $this->factory = $factory;
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
     * Sets counting operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function count($entity)
    {
        return new CountQuery(
            $this->connection,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets read operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function read($entity)
    {
        return new ReadQuery(
            $this->connection,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets read one operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function readOne($entity)
    {
        return new ReadOneQuery(
            $this->connection,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets write operation
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return $this
     */
    public function write($entity, $instance = null)
    {
        list($entity, $instance) = $this->reassignEntity($entity, $instance);

        return new WriteQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets insert operation
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return $this
     */
    public function insert($entity, $instance)
    {
        list($entity, $instance) = $this->reassignEntity($entity, $instance);

        return new InsertQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets update operation
     *
     * @param string|object       $entity
     * @param null|array|object $instance
     *
     * @return $this
     */
    public function update($entity, $instance)
    {
        list($entity, $instance) = $this->reassignEntity($entity, $instance);

        return new UpdateQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets delete operation
     *
     * @param string|object       $entity
     * @param null|array|object $instance
     *
     * @return $this
     */
    public function delete($entity, $instance)
    {
        list($entity, $instance) = $this->reassignEntity($entity, $instance);

        return new DeleteQuery(
            $this->connection,
            $instance,
            $this->models->get($entity),
            $this->converter,
            $this->factory
        );
    }

    /**
     * Sets clear operation
     *
     * @param string $entity
     *
     * @return $this
     */
    public function clear($entity)
    {
        return new ClearQuery(
            $this->connection,
            $this->models->get($entity),
            $this->factory
        );
    }

    /**
     * Reassigns entity/instance variables if entity is object
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return array
     */
    protected function reassignEntity($entity, $instance = null)
    {
        if (is_object($entity)) {
            return [$this->normalizeClassName($entity), $entity];
        }

        return [$entity, $instance];
    }
}
