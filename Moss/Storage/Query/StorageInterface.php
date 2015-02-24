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

/**
 * Storage interface, used for creating queries for CRUD operations
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface StorageInterface
{
    /**
     * Returns connection
     *
     * @return Connection
     */
    public function connection();

    /**
     * Sets read operation
     *
     * @param string $entity
     *
     * @return ReadQueryInterface
     */
    public function read($entity);

    /**
     * Sets read one operation
     *
     * @param string $entity
     *
     * @return ReadQueryInterface
     */
    public function readOne($entity);

    /**
     * Sets write operation
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return WriteQueryInterface
     */
    public function write($entity, $instance = null);

    /**
     * Sets insert operation
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return InsertQueryInterface
     */
    public function insert($entity, $instance);

    /**
     * Sets update operation
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return UpdateQueryInterface
     */
    public function update($entity, $instance);

    /**
     * Sets delete operation
     *
     * @param string|object     $entity
     * @param null|array|object $instance
     *
     * @return DeleteQueryInterface
     */
    public function delete($entity, $instance);
}
