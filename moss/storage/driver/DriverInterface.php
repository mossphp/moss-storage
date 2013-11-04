<?php
namespace moss\storage\driver;

interface DriverInterface
{
    // Field types
    const FIELD_BOOLEAN = 'boolean';
    const FIELD_INTEGER = 'integer';
    const FIELD_DECIMAL = 'decimal';
    const FIELD_STRING = 'string';
    const FIELD_DATETIME = 'datetime';
    const FIELD_SERIAL = 'serial';

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
     * Binds value, casts to internal type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return null|string
     */
    public function cast($value, $type);

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
     * @return string
     * @throws DriverException
     */
    public function lastInsertId();

    /**
     * Unbinds value, casts from internal type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function reCast($value, $type);

    /**
     * Retches result element as object
     *
     * @param string $className
     * @param array  $reCast
     *
     * @return bool|mixed
     * @throws DriverException
     */
    public function fetchObject($className, $reCast = array());

    /**
     * Fetches result element as associative array
     *
     * @param array $reCast
     *
     * @return bool|mixed
     * @throws DriverException
     */
    public function fetchAssoc($reCast = array());

    /**
     * Fetches field from result element
     *
     * @param int  $fieldNum
     * @param null $reCast
     *
     * @return bool|mixed|string
     * @throws DriverException
     */
    public function fetchField($fieldNum = 0, $reCast = null);

    /**
     * Fetches all results as objects or associative array
     *
     * @param string $className
     * @param array $unbind
     *
     * @return array
     * @throws DriverException
     */
    public function fetchAll($className = null, $unbind = array());

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
