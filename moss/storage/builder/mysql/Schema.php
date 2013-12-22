<?php
namespace moss\storage\builder\mysql;


use moss\storage\builder\BuilderException;
use moss\storage\builder\SchemaInterface;

class Schema implements SchemaInterface
{
    const QUOTE = '`';

    private $fieldTypes = array(
        self::FIELD_BOOLEAN => array('tinyint:boolean'), // tinyint with "bool" in comment
        self::FIELD_INTEGER => array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'),
        self::FIELD_DECIMAL => array('decimal'),
        self::FIELD_STRING => array('char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext'),
        self::FIELD_DATETIME => array('time', 'date', 'datetime', 'timestamp', 'year'),
        self::FIELD_SERIAL => array('text:serial') // text with "serial" in comment
    );

    protected $operation;

    protected $container;
    protected $engine;
    protected $charset;

    protected $columns = array();
    protected $indexes = array();

    /**
     * Constructor
     *
     * @param string $container
     * @param string $operation
     * @param string $engine
     * @param string $charset
     */
    public function __construct($container = null, $operation = self::OPERATION_CREATE, $engine = 'InnoDB', $charset = 'utf8')
    {
        if ($container !== null) {
            $this->container($container);
            $this->operation($operation);
        }

        $this->engine = $engine; // todo assert engine
        $this->charset = $charset; // todo assert charset
    }

    protected function quote(&$string)
    {
        return $string = self::QUOTE . $string . self::QUOTE;
    }

    /**
     * Sets container name
     *
     * @param string $container
     *
     * @return $this
     * @throws BuilderException
     */
    public function container($container)
    {
        if (empty($container)) {
            throw new BuilderException('Missing container name');
        }

        $this->container = $container;

        return $this;
    }

    /**
     * Sets operation for builder
     *
     * @param string $operation
     *
     * @return $this
     * @throws BuilderException
     */
    public function operation($operation)
    {
        switch ($operation) {
            case self::OPERATION_CHECK:
            case self::OPERATION_INFO:
            case self::OPERATION_CREATE:
            case self::OPERATION_ADD:
            case self::OPERATION_CHANGE:
            case self::OPERATION_REMOVE:
            case self::OPERATION_DROP:
                break;
            default:
                throw new BuilderException(sprintf('Unknown operation "%s"', $operation));
        }

        $this->operation = $operation;

        return $this;
    }

    /**
     * Sets container column
     *
     * @param string      $name
     * @param string      $type
     * @param array       $attributes
     * @param null|string $after
     *
     * @return $this
     * @throws BuilderException
     */
    public function column($name, $type = self::FIELD_STRING, $attributes = array(), $after = null)
    {
        $this->assertColumnType($type);

        $this->columns[] = array(
            $name,
            $type,
            $this->prepareAttributes($attributes),
            $after ? $after : $name
        );

        return $this;
    }

    protected function assertColumnType($type)
    {
        if (!isset($this->fieldTypes[$type])) {
            throw new BuilderException(sprintf('Invalid column type "%s" in "%s"', $type, $this->container));
        }
    }

    protected function prepareAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                unset($attributes[$key]);
                $key = $value;
                $value = true;
            }

            if ($key === self::ATTRIBUTE_LENGTH || $key === self::ATTRIBUTE_PRECISION) {
                $value = (int) $value;
            }

            $attributes[$key] = $value;
        }

        return array_change_key_case($attributes, \CASE_LOWER);
    }

    protected function buildColumn($name, $type, array $attributes)
    {
        return $this->quote($name) . ' ' . $this->buildColumnType($name, $type, $attributes) . ' ' . $this->buildColumnAttributes($type, $attributes);
    }

    protected function buildColumnType($name, $type, array $attributes)
    {
        switch ($type) {
            case self::FIELD_BOOLEAN:
                return 'TINYINT(1) COMMENT \'boolean\'';
                break;
            case self::FIELD_INTEGER:
                $len = isset($attributes[self::ATTRIBUTE_LENGTH]) ? $attributes[self::ATTRIBUTE_LENGTH] : 10;

                return sprintf('INT(%u)', $len);
                break;
            case self::FIELD_DECIMAL:
                $len = isset($attributes[self::ATTRIBUTE_LENGTH]) ? $attributes[self::ATTRIBUTE_LENGTH] : 10;
                $prc = isset($attributes[self::ATTRIBUTE_PRECISION]) ? $attributes[self::ATTRIBUTE_PRECISION] : 0;

                return sprintf('DECIMAL(%u,%u)', $len, $prc);
                break;
            case self::FIELD_DATETIME:
                return 'DATETIME';
                break;
            case self::FIELD_SERIAL:
                return 'TEXT COMMENT \'serial\'';
                break;
            case self::FIELD_STRING:
                $len = isset($attributes[self::ATTRIBUTE_LENGTH]) ? $attributes[self::ATTRIBUTE_LENGTH] : null;
                if ($len == 0 || $len > 1023) {
                    return 'TEXT';
                } elseif ($len > 255) {
                    return sprintf('VARCHAR(%u)', $len);
                } else {
                    return sprintf('CHAR(%u)', $len);
                }
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for field "%s"', $type, $name));
                break;
        }
    }

    protected function buildColumnAttributes($type, array $attributes)
    {
        $node = array();

        if (isset($attributes[self::ATTRIBUTE_COMMENT]) && $type != self::FIELD_BOOLEAN && $type != self::FIELD_SERIAL) {
            $node[] = 'COMMENT \'' . $attributes[self::ATTRIBUTE_COMMENT] . '\'';
        }

        if (isset($attributes[self::ATTRIBUTE_UNSIGNED]) && in_array($type, array(self::FIELD_INTEGER, self::FIELD_DECIMAL))) {
            $node[] = 'UNSIGNED';
        }

        if (isset($attributes[self::ATTRIBUTE_DEFAULT])) {
            if (!in_array($type, array(self::FIELD_BOOLEAN, self::FIELD_INTEGER, self::FIELD_DECIMAL))) {
                $node[] = 'DEFAULT \'' . $attributes[self::ATTRIBUTE_DEFAULT] . '\'';
            } else {
                $node[] = 'DEFAULT ' . $attributes[self::ATTRIBUTE_DEFAULT];
            }
        } elseif (isset($attributes[self::ATTRIBUTE_NULL])) {
            $node[] = 'DEFAULT NULL';
        } else {
            $node[] = 'NOT NULL';
        }

        if ($type == self::FIELD_INTEGER && isset($attributes[self::ATTRIBUTE_AUTO])) {
            $node[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $node);
    }

    /**
     * Sets key/index to container
     *
     * @param array $localFields
     *
     * @return $this
     */
    public function primary(array $localFields)
    {
        $this->index('primary', $localFields, self::INDEX_PRIMARY);
    }

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $localFields
     * @param string $foreignContainer
     * @param array  $foreignFields
     *
     * @return $this
     */
    public function foreign($name, array $localFields, $foreignContainer, array $foreignFields)
    {
        $this->index($name, $localFields, self::INDEX_FOREIGN, $foreignContainer, $foreignFields);
    }

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $localFields
     *
     * @return $this
     */
    public function unique($name, array $localFields)
    {
        $this->index($name, $localFields, self::INDEX_UNIQUE);

        return $this;
    }

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $localFields
     * @param string $type
     * @param string $foreignContainer
     * @param array  $foreignFields
     *
     * @return $this
     * @throws BuilderException
     */
    public function index($name, array $localFields, $type = self::INDEX_INDEX, $foreignContainer = null, array $foreignFields = array())
    {
        $this->assertIndexFields($type);

        $this->indexes[] = array(
            $name,
            (array) $localFields,
            $type,
            $foreignContainer,
            (array) $foreignFields
        );

        return $this;
    }

    protected function assertIndexType($type)
    {
        if (!in_array($type, array(self::INDEX_PRIMARY, self::INDEX_FOREIGN, self::INDEX_UNIQUE, self::INDEX_INDEX))) {
            throw new BuilderException(sprintf('Invalid index type "%s" in "%s"', $type, $this->container));
        }
    }

    protected function assertIndexFields($fields)
    {
        if (empty($fields)) {
            throw new BuilderException(sprintf('Missing fields for index in "%s"', $this->container));
        }
    }

    protected function buildIndex($name, array $localFields, $type = self::INDEX_INDEX, $foreignContainer = null, array $foreignFields = array())
    {
        array_walk($localFields, array($this, 'quote'));
        $localFields = implode(', ', $localFields);

        array_walk($foreignFields, array($this, 'quote'));
        $foreignFields = implode(', ', $foreignFields);

        switch ($type) {
            case self::INDEX_PRIMARY:
                return 'PRIMARY KEY (' . $localFields . ')';
                break;
            case self::INDEX_FOREIGN:
                return 'CONSTRAINT ' . $this->quote($name) . ' FOREIGN KEY (' . $localFields . ') REFERENCES ' . $foreignContainer . '(' . $foreignFields . ') ON UPDATE CASCADE ON DELETE RESTRICT';
                break;
            case self::INDEX_UNIQUE:
                return 'UNIQUE KEY ' . $this->quote($name) . ' (' . $localFields . ')';
                break;
            case self::INDEX_INDEX:
                return 'KEY ' . $this->quote($name) . ' (' . $localFields . ')';
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for index "%s"', $type, $name));
                break;
        }
    }

    /**
     * Builds query string
     *
     * @return string
     * @throws BuilderException
     */
    public function build()
    {
        if(empty($this->container)) {
            throw new BuilderException('Missing container name');
        }

        $stmt = array();

        switch ($this->operation) {
            case self::OPERATION_CHECK:
                $stmt[] = 'SHOW TABLES LIKE';
                $stmt[] = '\'' . $this->container . '\'';
                break;
            case self::OPERATION_INFO:
                $stmt[] = 'SHOW CREATE TABLE';
                $stmt[] = $this->quote($this->container);
                break;
            case self::OPERATION_CREATE:
                $stmt[] = 'CREATE TABLE';
                $stmt[] = $this->quote($this->container);
                $stmt[] = '(';

                $nodes = array();
                foreach ($this->columns as $node) {
                    $nodes[] = $this->buildColumn($node[0], $node[1], $node[2]);
                }

                foreach ($this->indexes as $node) {
                    $nodes[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3], $node[4]);
                }

                $stmt[] = implode(', ', $nodes);

                $stmt[] = ')';
                $stmt[] = 'ENGINE=' . $this->engine;
                $stmt[] = sprintf('DEFAULT CHARSET=%1$s', $this->charset);
                break;
            case self::OPERATION_ADD:
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->quote($this->container);
                $nodes = array();
                foreach ($this->columns as $node) {
                    $str = 'ADD ' . $this->buildColumn($node[0], $node[1], $node[2]); // todo - after
                    $nodes[] = $str;
                }
                foreach ($this->indexes as $node) {
                    $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2], $node[3], $node[4]);
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case self::OPERATION_CHANGE:
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->quote($this->container);
                $nodes = array();
                foreach ($this->columns as $node) {
                    $str = 'CHANGE ' . $this->quote($node[3]) . ' ' . $this->buildColumn($node[0], $node[1], $node[2]); // todo - after
                    $nodes[] = $str;
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case self::OPERATION_REMOVE:
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->quote($this->container);
                $nodes = array();
                foreach ($this->columns as $node) {
                    $nodes[] = 'DROP ' . $this->quote($node[0]);
                }
                foreach ($this->indexes as $node) {
                    switch ($node[2]) {
                        case self::INDEX_PRIMARY:
                            $nodes[] = 'DROP PRIMARY KEY';
                            break;
                        case self::INDEX_FOREIGN:
                            $nodes[] = 'DROP FOREIGN KEY ' . $this->quote($node[0]);
                            break;
                        default:
                            $nodes[] = 'DROP KEY ' . $this->quote($node[0]);
                    }
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case self::OPERATION_DROP:
                $stmt[] = 'DROP TABLE IF EXISTS';
                $stmt[] = $this->quote($this->container);
                break;
        }

        $stmt = array_filter($stmt);

        return implode(' ', $stmt);
    }

    /**
     * Resets builder
     *
     * @return $this
     */
    public function reset()
    {
        // todo
    }

    /**
     * Casts query to string (builds it)
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->build();
    }
} 