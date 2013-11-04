<?php
namespace moss\storage\builder\mysql;

use moss\storage\builder\BuilderException;
use moss\storage\builder\QueryBuilderInterface;

class Query implements QueryBuilderInterface
{
    const QUOTE = '`';

    private $aggregateMethods = array(
        self::AGGREGATE_DISTINCT => 'DISTINCT',
        self::AGGREGATE_COUNT => 'COUNT',
        self::AGGREGATE_AVERAGE => 'AVERAGE',
        self::AGGREGATE_MAX => 'MAX',
        self::AGGREGATE_MIN => 'MIN',
        self::AGGREGATE_SUM => 'SUM'
    );

    private $comparisonOperators = array(
        self::COMPARISON_EQUAL => '=',
        self::COMPARISON_NOT_EQUAL => '!=',
        self::COMPARISON_LESS => '<',
        self::COMPARISON_GREATER => '>',
        self::COMPARISON_LESS_EQUAL => '<=',
        self::COMPARISON_GREATER_EQUAL => '>=',
        self::COMPARISON_LIKE => 'LIKE',
        self::COMPARISON_REGEX => 'REGEX'
    );

    private $logicalOperators = array(
        self::LOGICAL_AND => 'AND',
        self::LOGICAL_OR => 'OR',
    );

    private $orderMethods = array(
        self::ORDER_ASC => 'ASC',
        self::ORDER_DESC => 'DESC',
    );

    private $operation;

    private $container;

    private $values = array();

    private $fields = array();
    private $aggregates = array();
    private $group = array();
    private $sub = array();

    private $conditions = array();

    private $order = array();

    private $limit = null;
    private $offset = null;

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
            case self::OPERATION_SELECT:
            case self::OPERATION_INSERT:
            case self::OPERATION_UPDATE:
            case self::OPERATION_DELETE:
            case self::OPERATION_CLEAR:
                break;
            default:
                throw new BuilderException(sprintf('Unknown operation %s', $operation));
        }

        $this->operation = $operation;

        return $this;
    }

    protected function quote($string)
    {
        return self::QUOTE . $string . self::QUOTE;
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
     * Adds fields to query
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = array();
        foreach ($fields as $key => $val) {
            $this->fields[] = is_numeric($key) ? array($val, null) : array($key, $val);
        }

        return $this;
    }

    /**
     * Adds field to query
     *
     * @param string      $field
     * @param null|string $alias
     *
     * @return $this
     */
    public function field($field, $alias = null)
    {
        $this->fields[] = array($field, $alias);

        return $this;
    }

    /**
     * Adds aggregate method to query
     *
     * @param string $method
     * @param string $field
     *
     * @return $this
     * @throws BuilderException
     */
    public function aggregate($method, $field)
    {
        if (!isset($this->aggregateMethods[$method])) {
            throw new BuilderException(sprintf('Query builder does not supports aggregation method %s', $method));
        }

        $this->aggregates[] = array($method, $field);

        return $this;
    }

    /**
     * Adds sub query
     *
     * @param QueryBuilderInterface $subSelect
     * @param string                $alias
     *
     * @return $this
     */
    public function sub(QueryBuilderInterface $subSelect, $alias)
    {
        $this->sub[] = array($subSelect, $alias);

        return $this;
    }

    protected function buildFields()
    {
        if (empty($this->fields)) {
            throw new BuilderException('No fields selected for reading in query');
        }

        $result = array();

        foreach ($this->aggregates as $node) {
            $result[] = $this->aggregateMethods[$node[0]] . '(' . $this->quote($node[1]) . ') AS ' . strtolower($this->quote($node[0]));
        }

        foreach ($this->fields as $node) {
            if ($node[1] === null) {
                $result[] = $this->quote($node[0]);
                continue;
            }

            $result[] = $this->quote($node[0]) . ' AS ' . $this->quote($node[1], false);
        }

        foreach ($this->sub as $node) {
            $result[] = '( ' . $node[0] . ' ) AS ' . $this->quote($node[1]);
        }

        return implode(', ', $result);
    }

    /**
     * Adds grouping to query
     *
     * @param string $field
     *
     * @return $this
     */
    public function group($field)
    {
        $this->group[] = $field;

        return $this;
    }

    protected function buildGroup()
    {
        if (empty($this->group)) {
            return null;
        }

        $group = array_map(array($this, 'quote'), $this->group);

        return 'GROUP BY ' . implode(', ', $group);
    }

    /**
     * Adds value to query
     *
     * @param string $col
     * @param mixed  $value
     *
     * @return $this
     */
    public function value($col, $value)
    {
        $this->values[] = array($col, $value);

        return $this;
    }

    protected function buildInsertValues()
    {
        if (empty($this->values)) {
            throw new BuilderException('No values to insert');
        }

        $fields = array();
        $values = array();

        foreach ($this->values as $node) {
            $fields[] = $this->quote($node[0]);
            $values[] = ($node[1] === null ? 'NULL' : $node[1]);
        }

        return '(' . implode(', ', $fields) . ') ' . (count($fields) > 1 ? 'VALUES' : 'VALUE') . ' (' . implode(', ', $values) . ')';
    }

    protected function buildUpdateValues()
    {
        if (empty($this->values)) {
            throw new BuilderException('No values to update');
        }

        $result = array();

        foreach ($this->values as $node) {
            $result[] = $this->quote($node[0]) . ' = ' . ($node[1] === null ? 'NULL' : $node[1]);
        }

        return 'SET ' . implode(', ', $result);
    }

    /**
     * Adds condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comp
     * @param string $log
     *
     * @return $this
     * @throws BuilderException
     */
    public function condition($field, $value, $comp = self::COMPARISON_EQUAL, $log = self::LOGICAL_OR)
    {
        if (!isset($this->comparisonOperators[$comp])) {
            throw new BuilderException(sprintf('Query builder does not supports comparison operator %s', $comp));
        }

        if (!isset($this->logicalOperators[$log])) {
            throw new BuilderException(sprintf('Query builder does not supports logical operator %s', $log));
        }

        $this->conditions[] = array(
            is_array($field) ? array_map(array($this, 'quote'), $field) : $this->quote($field),
            $value,
            $this->comparisonOperators[$comp],
            $this->logicalOperators[$log]
        );

        return $this;
    }

    protected function buildConditions()
    {
        if (empty($this->conditions)) {
            return null;
        }

        $result = array();
        foreach ($this->conditions as $node) {
            if (!is_array($node[0])) {
                $result[] = $this->buildConditionString($node[0], $node[1], $node[2]);
                $result[] = $node[3];
                continue;
            }

            $condition = array();
            foreach ($node[0] as $key => $field) {
                $condition[] = $this->buildConditionString($node[0][$key], is_array($node[1]) ? $node[1][$key] : $node[1], $node[2]);
            }

            $result[] = '(' . implode(' ' . $this->logicalOperators[self::LOGICAL_OR] . ' ', $condition) . ')';
            $result[] = $node[3];
        }

        array_pop($result);

        return 'WHERE ' . implode(' ', $result);
    }

    protected function buildConditionString($field, $bind, $operator)
    {
        if (is_array($bind)) {
            foreach ($bind as &$val) {
                $val = $this->buildConditionString($field, $val, $operator);
                unset($val);
            }

            $operator = $operator == '!=' ? self::LOGICAL_AND : self::LOGICAL_OR;

            return '(' . implode(sprintf(' %s ', $this->logicalOperators[$operator]), $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        return $field . ' ' . $operator . ' ' . $bind;
    }

    /**
     * Adds sorting to query
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     * @throws BuilderException
     */
    public function order($field, $order = self::ORDER_DESC)
    {
        if (!is_array($order) && !isset($this->orderMethods[$order])) {
            throw new BuilderException(sprintf('Query builder does not supports order method %s', $order));
        }

        if (is_array($order)) {
            $this->order[] = array(
                $this->quote($field),
                $order
            );

            return $this;
        }

        $this->order[] = array(
            $field,
            $this->orderMethods[(string) $order]
        );

        return $this;
    }

    protected function buildOrder()
    {
        if (empty($this->order)) {
            return null;
        }

        $output = array();
        foreach ($this->order as $node) {
            if (!is_array($node[1])) {
                $output[] = $this->quote($node[0]) . ' ' . $node[1];
                continue;
            }

            foreach ($node[1] as $v) {
                $output[] = $this->buildConditionString($node[0], $v, $this->comparisonOperators[self::COMPARISON_EQUAL]) . ' ' . $this->orderMethods[self::ORDER_DESC];
            }
        }

        return 'ORDER BY ' . implode(', ', (array) $output);
    }

    /**
     * Sets limits to query
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit > 0 ? (int) $limit : null;
        $this->offset = $offset > 0 ? (int) $offset : null;

        return $this;
    }

    protected function buildLimit()
    {
        if ($this->limit <= 0) {
            return null;
        }

        if (!$this->offset) {
            return 'LIMIT ' . (int) $this->limit;
        }

        return 'LIMIT ' . ($this->offset ? $this->offset . ',' : null) . ' ' . (int) $this->limit;
    }

    /**
     * Builds query string
     *
     * @return string
     */
    public function build()
    {
        $stmt = array();

        switch ($this->operation) {
            case self::OPERATION_SELECT:
                $stmt[] = 'SELECT';
                $stmt[] = $this->buildFields();
                $stmt[] = 'FROM';
                $stmt[] = $this->buildContainer();
                $stmt[] = $this->buildConditions();
                $stmt[] = $this->buildGroup();
                $stmt[] = $this->buildOrder();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_INSERT:
                $stmt[] = 'INSERT INTO';
                $stmt[] = $this->buildContainer();
                $stmt[] = $this->buildInsertValues();
                break;
            case self::OPERATION_UPDATE:
                $stmt[] = 'UPDATE';
                $stmt[] = $this->buildContainer();
                $stmt[] = $this->buildUpdateValues();
                $stmt[] = $this->buildConditions();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_DELETE:
                $stmt[] = 'DELETE FROM';
                $stmt[] = $this->buildContainer();
                $stmt[] = $this->buildConditions();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_CLEAR:
                $stmt[] = 'TRUNCATE TABLE';
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

        $this->values = array();

        $this->fields = array();
        $this->aggregates = array();
        $this->group = array();
        $this->sub = array();

        $this->conditions = array();

        $this->order = array();

        $this->limit = null;
        $this->offset = null;

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