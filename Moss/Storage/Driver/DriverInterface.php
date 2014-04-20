<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Driver;

/**
 * Driver interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface DriverInterface
{
    /**
     * Returns current statements query string
     *
     * @return string
     */
    public function queryString();

    /**
     * Prepares query
     *
     * @param string $queryString
     * @param string $comment
     *
     * @return $this
     */
    public function prepare($queryString, $comment = null);

    /**
     * Converts set type to storable value
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return null|string
     */
    public function store($value, $type);

    /**
     * Converts from storable to set type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function restore($value, $type);

    /**
     * Executes prepared query with set parameters
     *
     * @param array $parameters
     *
     * @return $this
     * @throws DriverException
     */
    public function execute($parameters = array());

    /**
     * Returns number of affected rows
     *
     * @return int
     * @throws DriverException
     */
    public function affectedRows();

    /**
     * Returns last inserted id
     *
     * @return int
     * @throws DriverException
     */
    public function lastInsertId();

    /**
     * Retches result element as object

     *
*@param string $className
     * @param array $restore
     *
*@return bool|mixed
     * @throws DriverException
     */
    public function fetchObject($className, $restore = array());

    /**
     * Fetches result element as associative array

     *
*@param array $restore
     *
*@return bool|mixed
     * @throws DriverException
     */
    public function fetchAssoc($restore = array());

    /**
     * Fetches field from result element

     *
*@param int  $fieldNum
     * @param null $restore
     *
*@return bool|mixed|string
     * @throws DriverException
     */
    public function fetchField($fieldNum = 0, $restore = null);

    /**
     * Fetches all results as objects or associative array

     *
*@param string $className
     * @param array $restore
     *
*@return array
     * @throws DriverException
     */
    public function fetchAll($className = null, $restore = array());

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
     * @throws DriverException
     */
    public function transactionCommit();

    /**
     * Rollbacks transaction
     *
     * @return $this
     * @throws DriverException
     */
    public function transactionRollback();

    /**
     * Returns true if in transaction
     *
     * @return bool
     */
    public function transactionCheck();

    /**
     * Resets driver,
     * If in transaction, rollbacks it
     *
     * @return $this
     */
    public function reset();
}
