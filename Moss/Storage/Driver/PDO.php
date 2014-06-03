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
 * PDO implementing driver interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class PDO implements DriverInterface
{

    protected $prefix;

    /**
     * @var MutatorInterface
     */
    protected $mutator;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * Constructor
     *
     * @param string           $dsn
     * @param string           $username
     * @param string           $password
     * @param MutatorInterface $mutator
     * @param string           $prefix
     *
     * @throws DriverException
     */
    public function __construct($dsn, $username, $password, MutatorInterface $mutator = null, $prefix = null)
    {
        $initCmd = array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => 1,
        );

        if (!$this->pdo = new \PDO($dsn, $username, $password, $initCmd)) {
            throw new DriverException('Database adapter error!');
        }

        if (!empty($prefix)) {
            $this->prefix = $prefix . '_';
        }

        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') {
            $encoding = ($this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) >= 5.5) ? 'utf8mb4' : 'utf8';

            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES ' . $encoding); // re-connect
            $this->pdo->exec(' SET NAMES ' . $encoding); // current
        }

        $this->mutator = $mutator;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * Returns current statements query string
     *
     * @return string
     */
    public function queryString()
    {
        if (!$this->statement) {
            return null;
        }

        return $this->statement->queryString;
    }

    /**
     * Prepares query
     *
     * @param string $queryString
     * @param string $comment
     *
     * @return $this
     * @throws DriverException
     */
    public function prepare($queryString, $comment = null)
    {
        if(empty($queryString)) {
            throw new DriverException('Missing query string or query string is empty');
        }

        $queryString = str_replace('{prefix}', $this->prefix, $queryString);

        if ($comment !== null) {
            $queryString .= ' -- ' . $comment;
        }

        if (empty($this->statement) || $this->statement->queryString != $queryString) {
            $this->statement = $this->pdo->prepare($queryString);
        }

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
        if ($this->isNullValue($value)) {
            return null;
        }

        if (!$this->mutator) {
            return $value;
        }

        return $this->mutator->store($value, $type);
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
        if ($this->isNullValue($value)) {
            return null;
        }

        if (!$this->mutator) {
            return $value;
        }

        return $this->mutator->store($value, $type);
    }

    /**
     * Returns true if value is ALMOST null (empty string or null)
     *
     * @param $value
     *
     * @return bool
     */
    protected function isNullValue($value)
    {
        return is_scalar($value) && $value !== false && !strlen($value);
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
        if (!$this->statement) {
            throw new DriverException('No statement to execute');
        }

        if ($this->statement->execute($parameters) === false) {
            throw new DriverException(sprintf('Statement error!%1$s%2$s%1$%3$s', PHP_EOL, implode(', ', $this->pdo->errorInfo()), $this->statement->queryString));
        }

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
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        return $this->statement->rowCount();
    }

    /**
     * Returns last inserted id
     *
     * @return int
     * @throws DriverException
     */
    public function lastInsertId()
    {
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        return $this->pdo->lastInsertId();
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
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if (!$entity = $this->statement->fetchObject($className)) {
            return false;
        }

        if (empty($restore)) {
            return $entity;
        }

        $entity = $this->restoreObject($entity, $restore, new \ReflectionClass($className));

        return $entity;
    }

    protected function restoreObject($entity, array $restore, \ReflectionClass $ref)
    {
        foreach ($restore as $field => $type) {
            if (!$ref->hasProperty($field)) {
                $entity->$field = $this->restore($entity->$field, $type);
                continue;
            }

            $prop = $ref->getProperty($field);
            $prop->setAccessible(true);

            $value = $prop->getValue($entity);
            $value = $this->restore($value, $type);
            $prop->setValue($entity, $value);
        }

        return $entity;
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
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if (!$entity = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        if (empty($restore)) {
            return $entity;
        }

        $entity = $this->restoreArray($entity, $restore);

        return $entity;
    }

    protected function restoreArray($entity, array $restore)
    {
        foreach ($restore as $field => $type) {
            $entity[$field] = $this->restore($entity[$field], $type);
        }

        return $entity;
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
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if (!$value = $this->statement->fetchColumn($fieldNum)) {
            return false;
        }

        if (empty($restore)) {
            return $value;
        }

        if ($restore) {
            $value = $this->restore($value, $restore);
        }

        return $value;
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
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if ($className === null) {
            return $this->fetchAllAssoc($restore);
        }

        return $this->fetchAllObject($className, $restore);
    }

    /**
     * Fetches all result data as associative array
     *
     * @param array $restore
     *
     * @return array
     */
    protected function fetchAllAssoc($restore = array())
    {
        $result = $this->statement->fetchAll();
        foreach ($result as $entity) {
            $entity = $this->restoreArray($entity, $restore);
            unset($entity);
        }

        return $result;
    }

    /**
     * Fetches all result data as objects
     *
     * @param       $className
     * @param array $restore
     *
     * @return array
     */
    protected function fetchAllObject($className, $restore = array())
    {
        $result = $this->statement->fetchAll(\PDO::FETCH_CLASS, $className);
        $ref = new \ReflectionClass($className);

        foreach ($result as &$entity) {
            $entity = $this->restoreObject($entity, $restore, $ref);
        }

        return $result;
    }

    /**
     * Starts transaction
     *
     * @return $this
     * @throws DriverException
     */
    public function transactionStart()
    {
        if ($this->transactionCheck()) {
            throw new DriverException(sprintf('Unable to start transaction, already started'));
        }

        $this->pdo->beginTransaction();

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
        if (!$this->transactionCheck()) {
            throw new DriverException(sprintf('Unable to commit, no transactions started'));
        }

        $this->pdo->commit();

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
        if (!$this->transactionCheck()) {
            throw new DriverException(sprintf('Unable to rollback, no transactions started'));
        }

        $this->pdo->rollBack();

        return $this;
    }

    /**
     * Returns true if in transaction
     *
     * @return bool
     */
    public function transactionCheck()
    {
        return (bool) $this->pdo->inTransaction();
    }

    /**
     * Resets driver,
     * If in transaction, rollbacks it
     *
     * @return $this
     */
    public function reset()
    {
        if ($this->transactionCheck()) {
            $this->transactionRollback();
        }

        $this->statement = null;

        return $this;
    }
}
