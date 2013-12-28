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
        $this->assertIndexFields($localFields);

        $this->index('primary', $localFields, self::INDEX_PRIMARY);
    }

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $fields
     * @param string $container
     *
     * @return $this
     */
    public function foreign($name, array $fields, $container)
    {
        $this->assertIndexFields($fields);

        $this->indexes[] = array(
            $name,
            (array) $fields,
            self::INDEX_FOREIGN,
            $container,
        );

        return $this;
    }

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $fields
     *
     * @return $this
     */
    public function unique($name, array $fields)
    {
        $this->assertIndexFields($fields);

        $this->indexes[] = array(
            $name,
            (array) $fields,
            self::INDEX_UNIQUE,
            null
        );

        return $this;
    }

    /**
     * Sets key/index to container
     *
     * @param string $name
     * @param array  $fields
     * @param string $type
     * @param null   $container
     *
     * @return $this
     */
    public function index($name, array $fields, $type = self::INDEX_INDEX, $container = null)
    {
        $this->assertIndexType($type);
        $this->assertIndexFields($fields);

        $this->indexes[] = array(
            $name,
            (array) $fields,
            $type,
            $container,
        );

        return $this;
    }

    protected function assertIndexType($type)
    {
        if (!in_array($type, array(self::INDEX_PRIMARY, self::INDEX_INDEX, self::INDEX_UNIQUE, self::INDEX_FOREIGN))) {
            throw new BuilderException(sprintf('Invalid index type "%s" in "%s"', $type, $this->container));
        }
    }

    protected function assertIndexFields($fields)
    {
        if (empty($fields)) {
            throw new BuilderException(sprintf('Missing fields for index in "%s"', $this->container));
        }
    }

    protected function buildIndex($name, array $fields, $type = self::INDEX_INDEX, $container = null)
    {
        switch ($type) {
            case self::INDEX_PRIMARY:
                return 'PRIMARY KEY (' . $this->buildIndexFields($fields) . ')';
                break;
            case self::INDEX_FOREIGN:
                return 'CONSTRAINT ' . $this->quote($name) . ' FOREIGN KEY (' . $this->buildIndexFields(array_keys($fields)) . ') REFERENCES ' . $container . '(' . $this->buildIndexFields(array_values($fields)) . ') ON UPDATE CASCADE ON DELETE RESTRICT';
                break;
            case self::INDEX_UNIQUE:
                return 'UNIQUE KEY ' . $this->quote($name) . ' (' . $this->buildIndexFields($fields) . ')';
                break;
            case self::INDEX_INDEX:
                return 'KEY ' . $this->quote($name) . ' (' . $this->buildIndexFields($fields) . ')';
                break;
            default:
                throw new BuilderException(sprintf('Invalid type "%s" for index "%s"', $type, $name));
                break;
        }
    }

    protected function buildIndexFields(array $fields) {
        array_walk($fields, array($this, 'quote'));
        return implode(', ', $fields);
    }

    /**
     * Builds query string
     *
     * @return string
     * @throws BuilderException
     */
    public function build()
    {
        if (empty($this->container)) {
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
                    $nodes[] = $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
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
                    $nodes[] = 'ADD ' . $this->buildIndex($node[0], $node[1], $node[2], $node[3]);
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
     * Parsers create statement into array
     *
     * @param string $statement
     *
     * @return array
     * @throws BuilderException
     */
    public function parse($statement)
    {
        $statement = str_replace(array("\n", "\r", "\t", '  '), array(null, null, ' ', '  '), $statement);

        $result = array(
            'container' => preg_replace('/CREATE TABLE `([^`]+)`.*/i', '$1', $statement),
            'fields' => $this->parseColumns($statement),
            'indexes' => $this->parseIndexes($statement)
        );

        return $result;
    }

    protected function parseColumns($statement)
    {
        preg_match_all('/`(?P<name>[^`]+)` (?P<type>((tiny|small|medium|big)?int|integer|decimal|(var)?char|(tiny|medium|long)?text|(date)?time(stamp)?|year))(\((?P<length>[\d]+)(\,(?P<precision>[\d]+))?\))?(?P<attributes>[^,]+)?,?/i', $statement, $matches, \PREG_SET_ORDER);

        $columns = array();
        foreach ($matches as $match) {
            $node = array(
                'name' => $match['name'],
                'type' => $match['type'],
                'attributes' => array(
                    self::ATTRIBUTE_LENGTH => (int) $match['length'],
                    self::ATTRIBUTE_PRECISION => (int) $match['precision'],
                    self::ATTRIBUTE_NULL => stripos($match['attributes'], 'not null') === false,
                    self::ATTRIBUTE_UNSIGNED => stripos($match['attributes'], 'unsigned') !== false,
                    self::ATTRIBUTE_AUTO => stripos($match['attributes'], 'auto_increment') !== false,
                    self::ATTRIBUTE_DEFAULT => stripos($match['attributes'], 'default') !== false ? preg_replace('/.*DEFAULT \'([^\']+)\'.*/i', '$1', $match['attributes']) : null,
                    self::ATTRIBUTE_COMMENT => stripos($match['attributes'], 'comment') !== false ? preg_replace('/.*COMMENT \'([^\']+)\'.*/i', '$1', $match['attributes']) : null
                )
            );

            $type = strtolower($node['type']);
            $comm = strtolower($node['attributes'][self::ATTRIBUTE_COMMENT]);
            switch ($type) {
                case in_array($type . ':' . $comm, $this->fieldTypes[self::FIELD_BOOLEAN]):
                    $node['type'] = self::FIELD_BOOLEAN;
                    break;
                case in_array($type . ':' . $comm, $this->fieldTypes[self::FIELD_SERIAL]):
                    $node['type'] = self::FIELD_SERIAL;
                    break;
                case in_array($type, $this->fieldTypes[self::FIELD_INTEGER]):
                    $node['type'] = self::FIELD_INTEGER;
                    break;
                case in_array($type, $this->fieldTypes[self::FIELD_DECIMAL]):
                    $node['type'] = self::FIELD_DECIMAL;
                    break;
                case in_array($type, $this->fieldTypes[self::FIELD_STRING]):
                    $node['type'] = self::FIELD_STRING;
                    break;
                case in_array($type, $this->fieldTypes[self::FIELD_DATETIME]):
                    $node['type'] = self::FIELD_DATETIME;
                    break;
                default:
                    throw new BuilderException(sprintf('Invalid or unsupported field type "%s" in container "%s"', $type, $this->container));
            }

            foreach ($node['attributes'] as $i => $attr) {
                if (!$attr) {
                    unset($node['attributes'][$i]);
                }
            }

            $columns[] = $node;
        }

        return $columns;
    }

    protected function parseIndexes($statement)
    {
        preg_match_all('/(?P<fname>`[^`]+`)? ?(?P<type>PRIMARY KEY|FOREIGN KEY|UNIQUE KEY|KEY) (?P<name>`[^`]+`)? ?\((?P<fields>[^)]+)\)( REFERENCES `(?P<container>[^`]+)` ?\((?P<foreign>[^)]+)\))?/i', $statement, $matches, \PREG_SET_ORDER);

        $indexes = array();
        foreach ($matches as $match) {
            $node = array(
                'name' => trim($match['fname'] ? $match['fname'] : $match['name'], '`'),
                'type' => trim($match['type']),
                'fields' => explode(',', str_replace(array('`', ' '), null, $match['fields'])),
                'container' => isset($match['container']) ? trim($match['container'], '`') : null,
                'foreign' => isset($match['foreign']) ? explode(',', str_replace(array('`', ' '), null, $match['foreign'])) : array()
            );

            switch ($node['type']) {
                case 'PRIMARY KEY':
                    $node['name'] = self::INDEX_PRIMARY;
                    $node['type'] = self::INDEX_PRIMARY;
                    break;
                case 'FOREIGN KEY':
                    $node['type'] = self::INDEX_FOREIGN;
                    break;
                case 'UNIQUE KEY':
                    $node['type'] = self::INDEX_UNIQUE;
                    break;
                default:
                    $node['type'] = self::INDEX_INDEX;
            }

            if ($node['type'] != self::INDEX_FOREIGN) {
                unset($node['container'], $node['foreign']);
            }

            $indexes[] = $node;
        }

        return $indexes;
    }

    /**
     * Resets builder
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;
        $this->container = null;

        $this->columns = array();
        $this->indexes = array();

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
            return (string) $this->build();
        } catch(\Exception $e) {
            return get_class($e) . ' - ' . $e->getMessage();
        }
    }
} 