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
*@param string $entityName
     *
*@return ReadQueryInterface
     */
    public function read($entityName);

    /**
     * Sets read one operation

     *
*@param string $entityName
     *
*@return ReadQueryInterface
     */
    public function readOne($entityName);

    /**
     * Sets write operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return WriteQueryInterface
     */
    public function write($instance, $entity = null);

    /**
     * Sets insert operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return InsertQueryInterface
     */
    public function insert($instance, $entity = null);

    /**
     * Sets update operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return UpdateQueryInterface
     */
    public function update($instance, $entity = null);

    /**
     * Sets delete operation
     *
     * @param array|object       $instance
     * @param null|string|object $entity
     *
     * @return DeleteQueryInterface
     */
    public function delete($instance, $entity = null);
}
