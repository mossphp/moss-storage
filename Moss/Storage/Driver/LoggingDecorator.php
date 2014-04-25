<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Driver;

use Psr\Log\LoggerInterface;

/**
 * Logging decorator used to bind PSR logger interfaces to drivers
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class LoggingDecorator implements DriverInterface
{

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var LoggerInterface
     */
    private $logger;
    private $echo;

    /**
     * Constructor
     *
     * @param DriverInterface $driver
     * @param LoggerInterface $logger
     * @param bool            $echo
     */
    public function __construct(DriverInterface $driver, LoggerInterface $logger = null, $echo = false)
    {
        $this->driver = $driver;
        $this->logger = $logger;
        $this->echo = (bool) $echo;
    }

    /**
     * Returns current statements query string
     *
     * @return string
     */
    public function queryString()
    {
        return $this->driver->queryString();
    }

    /**
     * Prepares query
     *
     * @param string $queryString
     * @param string $comment
     *
     * @return $this
     */
    public function prepare($queryString, $comment = null)
    {
        $this->driver->prepare($queryString, $comment);

        return $this;
    }

    /**
     * Converts set type to storable value
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return null|string
     */
    public function store($value, $type)
    {
        return $this->driver->store($value, $type);
    }

    /**
     * Converts from storable to set type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function restore($value, $type)
    {
        return $this->driver->restore($value, $type);
    }

    /**
     * Executes prepared query with set parameters
     *
     * @param array $parameters
     *
     * @return $this
     * @throws DriverException
     */
    public function execute($parameters = array())
    {
        if ($this->logger) {
            $this->logger->debug($this->queryString(), $parameters);
        }

        if ($this->echo) {
            echo $this->queryString() . "\n";
        }

        $this->driver->execute($parameters);

        return $this;
    }

    /**
     * Returns number of affected rows
     *
     * @return int
     * @throws DriverException
     */
    public function affectedRows()
    {
        return $this->driver->affectedRows();
    }

    /**
     * Returns last inserted id
     *
     * @return int
     * @throws DriverException
     */
    public function lastInsertId()
    {
        return $this->driver->lastInsertId();
    }

    /**
     * Retches result element as object
     *
     * @param string $className
     * @param array  $restore
     *
     * @return bool|mixed
     * @throws DriverException
     */
    public function fetchObject($className, $restore = array())
    {
        return $this->driver->fetchObject($className, $restore);
    }

    /**
     * Fetches result element as associative array
     *
     * @param array $restore
     *
     * @return bool|mixed
     * @throws DriverException
     */
    public function fetchAssoc($restore = array())
    {
        return $this->driver->fetchAssoc($restore);
    }

    /**
     * Fetches field from result element
     *
     * @param int  $fieldNum
     * @param null $restore
     *
     * @return bool|mixed|string
     * @throws DriverException
     */
    public function fetchField($fieldNum = 0, $restore = null)
    {
        return $this->driver->fetchField($fieldNum, $restore);
    }

    /**
     * Fetches all results as objects or associative array
     *
     * @param string $className
     * @param array  $restore
     *
     * @return array
     * @throws DriverException
     */
    public function fetchAll($className = null, $restore = array())
    {
        return $this->driver->fetchAll($className, $restore);
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
     * @throws DriverException
     */
    public function transactionCommit()
    {
        $this->driver->transactionCommit();

        return $this;
    }

    /**
     * Rollbacks transaction
     *
     * @return $this
     * @throws DriverException
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

    /**
     * Resets driver,
     * If in transaction, rollbacks it
     *
     * @return $this
     */
    public function reset()
    {
        $this->driver->reset();

        return $this;
    }
}
