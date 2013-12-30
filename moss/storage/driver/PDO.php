<?php
namespace moss\storage\driver;

class PDO implements DriverInterface
{

    private $prefix;

    /** @var \PDO */
    private $pdo;

    /** @var \PDOStatement */
    private $statement;

    /**
     * Constructor
     *
     * @param string $driver
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $hostname
     * @param int    $port
     * @param string $prefix
     *
     * @throws DriverException
     */
    public function __construct($driver, $database, $username, $password, $hostname = 'localhost', $port = 3306, $prefix = null)
    {
        $initCmd = array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => 1,
        );

        $dsn = sprintf('%s:dbname=%s;host=%s;port=%u', $driver, $database, $hostname, $port);
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
     */
    public function prepare($queryString, $comment = null)
    {
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
     * Binds value, casts to internal type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return null|string
     */
    public function cast($value, $type)
    {
        if (is_scalar($value) && $value !== false && !strlen($value)) {
            return null;
        }

        if ($type === self::FIELD_BOOLEAN || $type === self::FIELD_INTEGER) {
            return (int) $value;
        }

        if ($type === self::FIELD_DECIMAL) {
            $value = preg_replace('/[^0-9,.\-]+/i', null, $value);
            $value = str_replace(',', '.', $value);

            return (float) $value;
        }

        if ($type === self::FIELD_DATETIME && $value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($type === self::FIELD_SERIAL) {
            return base64_encode(serialize($value));
        }

        return $value;
    }

    /**
     * Unbinds value, casts from internal type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function reCast($value, $type)
    {
        if (is_scalar($value) && $value !== false && !strlen($value)) {
            return null;
        }

        if ($type === self::FIELD_BOOLEAN) {
            return (bool) $value;
        }

        if ($type === self::FIELD_INTEGER) {
            return (int) $value;
        }

        if ($type === self::FIELD_DECIMAL) {
            $value = preg_replace('/[^0-9,.\-]+/i', null, $value);
            $value = str_replace(',', '.', $value);
            $value = strpos($value, '.') === false ? (int) $value : (float) $value;

            return $value;
        }

        if ($type === self::FIELD_DATETIME) {
            return new \DateTime($value);
        }

        if ($type === self::FIELD_SERIAL) {
            return unserialize(base64_decode($value));
        }

        return $value;
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
            throw new DriverException(sprintf("Statement error!\n%s\n%s", implode(', ', $this->pdo->errorInfo()), $this->statement->queryString));
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
     * @return string
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
     * @param array  $reCast
     *
     * @return bool|mixed
     * @throws DriverException
     */
    public function fetchObject($className, $reCast = array())
    {
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if (!$row = $this->statement->fetchObject($className)) {
            return false;
        }

        if (empty($reCast)) {
            return $row;
        }

        $ref = new \ReflectionObject($row);
        foreach ($reCast as $field => $type) {
            $prop = $ref->getProperty($field);
            $prop->setAccessible(true);

            $value = $prop->getValue($row);
            $value = $this->reCast($value, $type);
            $prop->setValue($row, $value);
        }

        return $row;
    }

    /**
     * Fetches result element as associative array
     *
     * @param array $reCast
     *
     * @return bool|mixed
     * @throws DriverException
     */
    public function fetchAssoc($reCast = array())
    {
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if (!$row = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        if (empty($reCast)) {
            return $row;
        }

        foreach ($reCast as $field => $type) {
            $row[$field] = $this->reCast($row[$field], $type);
        }

        return $row;
    }

    /**
     * Fetches field from result element
     *
     * @param int  $fieldNum
     * @param null $reCast
     *
     * @return bool|mixed|string
     * @throws DriverException
     */
    public function fetchField($fieldNum = 0, $reCast = null)
    {
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        if (!$value = $this->statement->fetchColumn($fieldNum)) {
            return false;
        }

        if (empty($reCast)) {
            return $value;
        }

        if ($reCast) {
            $value = $this->reCast($value, $reCast);
        }

        return $value;
    }

    /**
     * Fetches all results as objects or associative array
     *
     * @param string $className
     * @param array  $unbind
     *
     * @return array
     * @throws DriverException
     */
    public function fetchAll($className = null, $unbind = array())
    {
        if (!$this->statement) {
            throw new DriverException('Result instance missing');
        }

        $result = array();
        if ($className === null) {
            while ($row = $this->fetchAssoc($unbind)) {
                $result[] = $row;
            }

            return $result;
        }

        if (empty($unbind)) {
            while ($row = $this->fetchObject($className)) {
                $result[] = $row;
            }

            return $result;
        }

        $ref = new \ReflectionClass($className);
        while ($row = $this->fetchObject($className)) {
            foreach ($unbind as $field => $type) {
                if (!$ref->hasProperty($field)) {
                    $row->$field = $this->reCast($row->$field, $type);
                    continue;
                }

                $prop = $ref->getProperty($field);
                $prop->setAccessible(true);

                $value = $prop->getValue($row);
                $value = $this->reCast($value, $type);
                $prop->setValue($row, $value);
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Starts transaction
     *
     * @return $this
     */
    public function transactionStart()
    {
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
            throw new DriverException(sprintf('No transactions to commit'));
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
            throw new DriverException(sprintf('No transactions to rollback'));
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
        return $this->pdo->inTransaction();
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
