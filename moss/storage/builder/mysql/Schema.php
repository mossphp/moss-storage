<?php
namespace moss\storage\builder\mysql;


use moss\storage\builder\BuilderException;
use moss\storage\builder\SchemaBuilderInterface;

class Schema implements SchemaBuilderInterface
{

    const QUOTE = '`';
    const SEPARATOR = '.';

    protected $operation;

    protected $container;

    protected $mode;

    protected $columnsToAdd = array();
    protected $columnsToAlter = array();
    protected $columnsToDrop = array();

    protected $indexesToAdd = array();
    protected $indexesToAlter = array();
    protected $indexesToDrop = array();

    private $fieldTypes = array(
        self::FIELD_BOOLEAN => array('tinyint:boolean'), // tinyint with "bool" in comment
        self::FIELD_INTEGER => array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'),
        self::FIELD_DECIMAL => array('float', 'double', 'decimal', 'numeric'),
        self::FIELD_STRING => array('char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext'),
        self::FIELD_DATETIME => array('time', 'date', 'datetime', 'timestamp', 'year'),
        self::FIELD_SERIAL => array('text:serial') // text with "serial" in comment
    );

    /**
     * Constructor
     *
     * @param string $operation
     */
    public function __construct($operation = null)
    {
        if ($operation !== null) {
            $this->operation($operation);
        }
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
            case self::OPERATION_INFO:
            case self::OPERATION_CHECK:
            case self::OPERATION_CREATE:
            case self::OPERATION_ALTER:
            case self::OPERATION_DROP:
                break;
            default:
                throw new BuilderException(sprintf('Unknown operation %s', $operation));
        }

        $this->operation = $operation;

        return $this;
    }

    protected function quote($string)
    {
        return static::QUOTE . $string . static::QUOTE;
    }

    /**
     * Sets container name
     *
     * @param string $container
     *
     * @return $this
     */
    public function container($container)
    {
        $this->container = $container;

        return $this;
    }

    protected function buildContainer()
    {
        if (empty($this->container)) {
            throw new BuilderException('Missing container name');
        }

        return $this->quote($this->container);
    }

    /**
     * Changes info retrieval mode
     *
     * @param string $mode
     *
     * @return $this
     */
    public function mode($mode = self::INFO_COLUMNS)
    {
        $this->mode = $mode == self::INFO_INDEXES ? self::INFO_INDEXES : self::INFO_COLUMNS;

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
     */
    public function addColumn($name, $type = self::FIELD_STRING, $attributes = array(), $after = null)
    {
        $this->assertColumnType($type);

        $this->columnsToAdd[] = array(
            $name,
            $type,
            $this->prepareAttributes($attributes),
            $after
        );

        return $this;
    }

    /**
     * Sets container column
     *
     * @param string      $name
     * @param string      $type
     * @param array       $attributes
     * @param null|string $previous
     *
     * @return $this
     */
    public function alterColumn($name, $type = self::FIELD_STRING, $attributes = array(), $previous = null)
    {
        $this->assertColumnType($type);

        $this->columnsToAlter[] = array(
            $name,
            $type,
            $this->prepareAttributes($attributes),
            $previous ? $previous : $name
        );

        return $this;
    }

    /**
     * Removes container index
     *
     * @param $name
     *
     * @return $this
     */
    public function dropColumn($name)
    {
        $this->columnsToDrop[] = array($name);

        return $this;
    }

    /**
     * Parsers read columns into array for model
     *
     * @param array $collection
     *
     * @return array
     * @throws BuilderException
     * @todo - default value
     */
    public function parseColumns($collection)
    {
        $output = array();
        foreach ($collection as $row) {
            $node = array(
                'name' => $row['Field'],
                'type' => null,
                'attributes' => array()
            );

            $type = preg_replace('/^([^\(]+).*$/', '$1', $row['Type']);
            $type .= (!empty($row['Comment']) ? ':' . $row['Comment'] : null);

            if (in_array($type, $this->fieldTypes[self::FIELD_BOOLEAN]) && $row['Comment'] == 'boolean') {
                $node['type'] = self::FIELD_BOOLEAN;
            } elseif (in_array($type, $this->fieldTypes[self::FIELD_INTEGER])) {
                $node['type'] = self::FIELD_INTEGER;
            } elseif (in_array($type, $this->fieldTypes[self::FIELD_DECIMAL])) {
                $node['type'] = self::FIELD_DECIMAL;
            } elseif (in_array($type, $this->fieldTypes[self::FIELD_STRING])) {
                $node['type'] = self::FIELD_STRING;
            } elseif (in_array($type, $this->fieldTypes[self::FIELD_DATETIME])) {
                $node['type'] = self::FIELD_DATETIME;
            } elseif (in_array($type, $this->fieldTypes[self::FIELD_SERIAL]) && $row['Comment'] == 'serial') {
                $node['type'] = self::FIELD_SERIAL;
            } else {
                throw new BuilderException(sprintf('Invalid or unsupported field type "%s" in container "%s"', $type, $this->container));
            }

            if (stripos($row['Type'], '(') !== false) {
                $len = preg_replace('/^.+\(([\d]+)(,([\d]+))?\).*$/', '$1,$3', $row['Type']);
                list($node['attributes'][self::ATTRIBUTE_LENGTH], $node['attributes'][self::ATTRIBUTE_PRECISION]) = explode(',', $len);
            }

            if (stripos($row['Type'], 'unsigned') !== false) {
                $node['attributes'][self::ATTRIBUTE_UNSIGNED] = true;
            }

            if (stripos($row['Null'], 'YES') !== false) {
                $node['attributes'][self::ATTRIBUTE_NULL] = true;
            }

            if (stripos($row['Extra'], 'auto_increment') !== false) {
                $node['attributes'][self::ATTRIBUTE_AUTO] = true;
            }

            $output[$node['name']] = $node;
        }

        return $output;
    }

    /**
     * Sets container index
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     *
     * @return $this
     */
    public function addIndex($name, array $fields, $type = self::INDEX_INDEX)
    {
        $this->assertIndexType($type);
        $this->assertIndexFields($fields);

        $this->indexesToAdd[] = array(
            $name,
            $type,
            $fields,
        );

        return $this;
    }

    /**
     * Alters container index
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     *
     * @return $this
     */
    public function alterIndex($name, array $fields, $type = self::INDEX_INDEX)
    {
        $this->assertIndexType($type);
        $this->assertIndexFields($fields);

        $this->indexesToAlter[] = array(
            $name,
            $type,
            $fields,
        );

        return $this;
    }

    /**
     * Removes container index
     *
     * @param string $name
     * @param bool   $primary
     *
     * @return $this
     */
    public function dropIndex($name, $primary = false)
    {
        $this->indexesToDrop[] = array(
            $name,
            $primary ? self::INDEX_PRIMARY : self::INDEX_INDEX
        );
    }

    /**
     * Parsers read indexes into array for model
     *
     * @param array $collection
     *
     * @return array
     */
    public function parseIndexes($collection)
    {
        $output = array();
        foreach ($collection as $row) {
            $node = array(
                'name' => $row['Key_name'],
                'type' => self::INDEX_INDEX,
                'fields' => array()
            );

            if (stripos($row['Key_name'], 'PRIMARY') !== false) {
                $node['name'] = self::INDEX_PRIMARY;
                $node['type'] = self::INDEX_PRIMARY;
            } elseif (!$row['Non_unique']) {
                $node['type'] = self::INDEX_UNIQUE;
            }

            if (!isset($output[$node['name']])) {
                $output[$node['name']] = $node;
            }

            $output[$node['name']]['fields'][] = $row['Column_name'];
            ksort($output[$node['name']]['fields']);
        }

        return $output;
    }

    /**
     * Asserts if correct field type
     *
     * @param string $type
     *
     * @throws BuilderException
     */
    protected function assertColumnType($type)
    {
        if (!isset($this->fieldTypes[$type])) {
            throw new BuilderException(sprintf('Invalid column type "%s" in "%s"', $type, $this->container));
        }
    }

    /**
     * Prepares passed attribute array into key value pairs
     *
     * @param array $attributes
     *
     * @return array
     */
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

        return $attributes;
    }

    /**
     * Builds column string
     *
     * @param string $name
     * @param string $type
     * @param array  $attributes
     *
     * @return string
     * @throws BuilderException
     */
    protected function buildColumn($name, $type, array $attributes)
    {
        switch ($type) {
            case self::FIELD_BOOLEAN:
                $node[] = 'TINYINT(1) COMMENT \'boolean\'';
                break;
            case self::FIELD_INTEGER:
                $l = isset($attributes[self::ATTRIBUTE_LENGTH]) ? $attributes[self::ATTRIBUTE_LENGTH] : 10;
                $node[] = sprintf('INT(%u)', $l);
                break;
            case self::FIELD_DECIMAL:
                $l = isset($attributes[self::ATTRIBUTE_LENGTH]) ? $attributes[self::ATTRIBUTE_LENGTH] : 10;
                $p = isset($attributes[self::ATTRIBUTE_PRECISION]) ? $attributes[self::ATTRIBUTE_PRECISION] : 0;
                $node[] = sprintf('DECIMAL(%u,%u)', $l, $p);
                break;
            case self::FIELD_DATETIME:
                $node[] = 'DATETIME';
                break;
            case self::FIELD_SERIAL:
                $node[] = 'TEXT COMMENT \'serial\'';
                break;
            case self::FIELD_STRING:
                $l = isset($attributes[self::ATTRIBUTE_LENGTH]) ? $attributes[self::ATTRIBUTE_LENGTH] : null;
                if ($l === null || $l > 1023) {
                    $node[] = 'TEXT';
                } elseif ($l > 255) {
                    $node[] = sprintf('VARCHAR(%u)', $l);
                } else {
                    $node[] = sprintf('CHAR(%u)', $l);
                }
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for field "%s"', $type, $name));
                break;
        }

        if (($type == self::FIELD_INTEGER || $type == self::FIELD_DECIMAL) && isset($attributes[self::ATTRIBUTE_UNSIGNED])) {
            $node[] = 'UNSIGNED';
        }

        if (isset($attributes[self::ATTRIBUTE_DEFAULT])) {
            $default = $attributes[self::ATTRIBUTE_DEFAULT];
            if (!in_array($type, array(self::FIELD_BOOLEAN, self::FIELD_INTEGER, self::FIELD_DECIMAL))) {
                $default = '\'' . $default . '\'';
            }
            $node[] = 'DEFAULT ' . $default;
        } elseif (isset($attributes[self::ATTRIBUTE_NULL])) {
            $node[] = 'DEFAULT NULL';
        } else {
            $node[] = 'NOT NULL';
        }

        if ($type == self::FIELD_INTEGER && isset($attributes[self::ATTRIBUTE_AUTO])) {
            $node[] = 'AUTO_INCREMENT';
        }

        return sprintf('`%s` %s', $name, implode(' ', $node));
    }

    /**
     * Asserts if correct index type
     *
     * @param string $type
     *
     * @throws BuilderException
     */
    protected function assertIndexType($type)
    {
        if (!in_array($type, array(self::INDEX_PRIMARY, self::INDEX_UNIQUE, self::INDEX_INDEX))) {
            throw new BuilderException(sprintf('Invalid index type "%s" in "%s"', $type, $this->container));
        }
    }

    /**
     * Asserts if fields list has at least one field
     *
     * @param array $fields
     *
     * @throws BuilderException
     */
    protected function assertIndexFields($fields)
    {
        if (empty($fields)) {
            throw new BuilderException(sprintf('Missing fields for index in "%s"', $this->container));
        }
    }

    /**
     * Builds index string
     *
     * @param string $name
     * @param string $type
     * @param array  $fields
     *
     * @return string
     * @throws \moss\storage\builder\BuilderException
     */
    protected function buildIndex($name, $type, array $fields)
    {
        foreach ($fields as &$field) {
            $field = '`' . $field . '`';
            unset($field);
        }
        $fields = implode(', ', $fields);

        switch ($type) {
            case self::INDEX_PRIMARY:
                return 'PRIMARY KEY (' . $fields . ')';
                break;
            case self::INDEX_UNIQUE:
                return 'UNIQUE KEY ' . $this->quote($name) . ' (' . $fields . ')';
                break;
            case self::INDEX_INDEX:
                return 'KEY ' . $this->quote($name) . ' (' . $fields . ')';
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
        $stmt = array();

        switch ($this->operation) {
            case self::OPERATION_CHECK:
                $stmt[] = 'SHOW TABLES LIKE';
                $stmt[] = '\'' . $this->container . '\'';
                break;
            case self::OPERATION_INFO:
                switch ($this->mode) {
                    case self::INFO_COLUMNS:
                        $stmt[] = 'SHOW FULL COLUMNS FROM';
                        $stmt[] = $this->buildContainer();
                        break;
                    case self::INFO_INDEXES:
                        $stmt[] = 'SHOW INDEXES FROM';
                        $stmt[] = $this->buildContainer();
                        break;
                    default:
                        throw new BuilderException('Info mode not set');
                }
                break;
            case self::OPERATION_CREATE:
                $stmt[] = 'CREATE TABLE';
                $stmt[] = $this->buildContainer();
                $stmt[] = '(';

                if (empty($this->columnsToAdd)) {
                    throw new BuilderException('No columns defined for container. Container must have at least one column');
                }

                $nodes = array();
                foreach ($this->columnsToAdd as $node) {
                    $nodes[] = $this->buildColumn($node[0], $node[1], $node[2]);
                }
                $stmt[] = implode(', ', $nodes);

                $nodes = array();
                foreach ($this->indexesToAdd as $node) {
                    $nodes[] = $this->buildIndex($node[0], $node[1], $node[2]);
                }
                $stmt[] = empty($nodes) ? null : ', ' . implode(', ', $nodes);

                $stmt[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
                break;
            case self::OPERATION_ALTER:
                $stmt[] = 'ALTER TABLE';
                $stmt[] = $this->buildContainer();

                $nodes = array();
                foreach ($this->columnsToAdd as $node) {
                    $str = 'ADD ' . $this->buildColumn($node[0], $node[1], $node[2]);
                    if ($node[3] === self::ALTER_ADD_FIRST) {
                        $str .= ' FIRST';
                    } elseif ($node[3] !== null) {
                        $str .= ' AFTER ' . $this->quote($node[3]);
                    }

                    $nodes[] = $str;
                }

                foreach ($this->columnsToAlter as $node) {
                    $nodes[] = 'CHANGE ' . $this->quote($node[3]) . ' ' . $this->buildColumn($node[0], $node[1], $node[2]);
                }

                foreach ($this->columnsToDrop as $node) {
                    $nodes[] = 'DROP ' . $this->quote($node[0]);
                }

                foreach (array_merge($this->indexesToDrop, $this->indexesToAlter) as $node) {
                    $nodes[] = 'DROP ' . ($node[1] == self::INDEX_PRIMARY ? 'PRIMARY KEY' : 'INDEX ' . $this->quote($node[0]));
                }

                foreach (array_merge($this->indexesToAdd, $this->indexesToAlter) as $node) {
                    $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2]);
                }
                $stmt[] = implode(', ', $nodes);
                break;
            case self::OPERATION_DROP:
                $stmt[] = 'DROP TABLE IF EXISTS';
                $stmt[] = $this->buildContainer();
                break;
        }

        $stmt = array_filter($stmt);

        return implode(' ', $stmt);
    }

    /**
     * Resets query builder
     */
    public function reset()
    {
        $this->operation = null;

        $this->container = null;

        $this->mode = null;

        $this->columnsToAdd = array();
        $this->columnsToAlter = array();
        $this->columnsToDrop = array();

        $this->indexesToAdd = array();
        $this->indexesToAlter = array();
        $this->indexesToDrop = array();

        return $this;
    }

    /**
     * Casts query to string (builds it)
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->build();
        } catch(BuilderException $e) {
            return $e->getMessage();
        }
    }
} 