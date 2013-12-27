<?php
namespace moss\storage\builder\mysql;


use moss\storage\builder\BuilderException;
use moss\storage\builder\QueryInterface;

class Query implements QueryInterface
{
    const QUOTE = '`';

    protected $aggregateMethods = array(
        self::AGGREGATE_DISTINCT => 'DISTINCT',
        self::AGGREGATE_COUNT => 'COUNT',
        self::AGGREGATE_AVERAGE => 'AVERAGE',
        self::AGGREGATE_MAX => 'MAX',
        self::AGGREGATE_MIN => 'MIN',
        self::AGGREGATE_SUM => 'SUM'
    );

    protected $joinTypes = array(
        self::JOIN_INNER => 'INNER JOIN',
        self::JOIN_LEFT => 'LEFT OUTER JOIN',
        self::JOIN_RIGHT => 'RIGHT OUTER JOIN',
    );

    protected $comparisonOperators = array(
        self::COMPARISON_EQUAL => '=',
        self::COMPARISON_NOT_EQUAL => '!=',
        self::COMPARISON_LESS => '<',
        self::COMPARISON_GREATER => '>',
        self::COMPARISON_LESS_EQUAL => '<=',
        self::COMPARISON_GREATER_EQUAL => '>=',
        self::COMPARISON_LIKE => 'LIKE',
        self::COMPARISON_REGEX => 'REGEX'
    );

    protected $logicalOperators = array(
        self::LOGICAL_AND => 'AND',
        self::LOGICAL_OR => 'OR',
    );

    protected $orderMethods = array(
        self::ORDER_ASC => 'ASC',
        self::ORDER_DESC => 'DESC',
    );

    protected $operation;

    protected $container;
    protected $joins = array();

    protected $fields = array();
    protected $aggregates = array();
    protected $group = array();
    protected $subs = array();

    protected $values = array();

    protected $where = array();
    protected $having = array();

    protected $order = array();

    protected $limit = null;
    protected $offset = null;

    /**
     * Constructor
     *
     * @param string $container
     * @param string $alias
     * @param string $operation
     */
    public function __construct($container = null, $alias = null, $operation = self::OPERATION_SELECT)
    {
        if ($container !== null) {
            $this->container($container, $alias);
            $this->operation($operation);
        }
    }

    protected function quote($string, $container = null)
    {
        $array = explode(self::SEPARATOR, $string, 2);

        if ($this->operation !== self::OPERATION_SELECT) {
            return self::QUOTE . $string . self::QUOTE;
        }

        if (count($array) !== 2 && $container === null) {
            return self::QUOTE . $string . self::QUOTE;
        }

        if (!isset($array[1])) {
            array_unshift($array, $container);
        }

        if (strpos($array[0], self::QUOTE) !== 0) {
            $array[0] = self::QUOTE . $array[0] . self::QUOTE;
        }

        return $array[0] . self::SEPARATOR . self::QUOTE . $array[1] . self::QUOTE;
    }

    /**
     * Sets container name
     *
     * @param string $container
     * @param string $alias
     *
     * @return $this
     * @throws BuilderException
     */
    public function container($container, $alias = null)
    {
        if (empty($container)) {
            throw new BuilderException('Missing container name');
        }

        $this->container = array(
            $this->quote($container),
            $alias ? $this->quote($alias) : null
        );

        return $this;
    }

    protected function mapping()
    {
        return $this->container[1] ? $this->container[1] : $this->container[0];
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
                throw new BuilderException(sprintf('Unknown operation "%s"', $operation));
        }

        $this->operation = $operation;

        return $this;
    }

    protected function buildContainer()
    {
        $result = array();
        $result[] = $this->container[0];

        if ($this->operation !== self::OPERATION_SELECT) {
            return implode(' ', $result);
        }

        if ($this->container[1]) {
            $result[] = 'AS';
            $result[] = $this->container[1];
        }

        foreach ($this->joins as $node) {
            list($type, $container, $joins) = $node;

            $result[] = $this->joinTypes[$type];
            $result[] = $container[0];
            if ($container[1]) {
                $result[] = 'AS';
                $result[] = $container[1];
            }
            $result[] = 'ON';

            foreach ($joins as $join) {
                $result[] = $join[0] . ' ' . self::COMPARISON_EQUAL . ' ' . $join[1];
            }
        }

        return implode(' ', $result);
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
            is_numeric($key) ? $this->field($val) : $this->field($key, $val);
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
        $this->fields[] = array(
            $this->quote($field, $this->mapping()),
            $alias ? $this->quote($alias) : null
        );

        return $this;
    }

    /**
     * Adds distinct method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function distinct($field, $alias = null)
    {
        return $this->aggregate(self::AGGREGATE_DISTINCT, $field, $alias);
    }

    /**
     * Adds count method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function count($field, $alias = null)
    {
        return $this->aggregate(self::AGGREGATE_COUNT, $field, $alias);
    }

    /**
     * Adds average method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function average($field, $alias = null)
    {
        return $this->aggregate(self::AGGREGATE_AVERAGE, $field, $alias);
    }

    /**
     * Adds max method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function max($field, $alias = null)
    {
        return $this->aggregate(self::AGGREGATE_MAX, $field, $alias);
    }

    /**
     * Adds min method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function min($field, $alias = null)
    {
        return $this->aggregate(self::AGGREGATE_MIN, $field, $alias);
    }

    /**
     * Adds sum method to query
     *
     * @param string $field
     * @param string $alias
     *
     * @return $this
     */
    public function sum($field, $alias = null)
    {
        return $this->aggregate(self::AGGREGATE_SUM, $field, $alias);
    }

    /**
     * Adds aggregate method to query
     *
     * @param string $method
     * @param string $field
     * @param string $alias
     *
     * @return $this
     * @throws BuilderException
     */
    public function aggregate($method, $field, $alias = null)
    {
        if (!isset($this->aggregateMethods[$method])) {
            throw new BuilderException(sprintf('Query builder does not supports aggregation method "%s"', $method));
        }

        $this->aggregates[] = array(
            $method,
            $this->quote($field, $this->mapping()),
            $this->quote($alias ? $alias : strtolower($method)),
        );

        return $this;
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
        $this->group[] = $this->quote($field, $this->mapping());

        return $this;
    }

    protected function buildGroup()
    {
        if (empty($this->group)) {
            return null;
        }

        return 'GROUP BY ' . implode(', ', $this->group);
    }

    /**
     * Adds sub query
     *
     * @param QueryInterface $query
     * @param string         $alias
     *
     * @return $this
     */
    public function sub(QueryInterface $query, $alias)
    {
        $this->subs[] = array($query, $this->quote($alias));

        return $this;
    }

    protected function buildFields()
    {
        if (empty($this->fields)) {
            return '*';
        }

        $result = array();

        foreach ($this->aggregates as $node) {
            $result[] = sprintF(
                '%s(%s) AS %s',
                $this->aggregateMethods[$node[0]],
                $node[1],
                $node[2]
            );
        }

        foreach ($this->fields as $node) {
            if ($node[1] === null) {
                $result[] = $node[0];
                continue;
            }

            $result[] = $node[0] . ' AS ' . $node[1];
        }

        foreach ($this->subs as $node) {
            $result[] = '( ' . $node[0] . ' ) AS ' . $node[1];
        }

        return implode(', ', $result);
    }

    /**
     * Adds values to query
     *
     * @param array $values
     *
     * @return $this
     */
    public function values(array $values)
    {
        $this->values = array();

        foreach ($values as $col => $value) {
            $this->value($col, $value);
        }

        return $this;
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
        $this->values[] = array(
            $this->quote($col, $this->mapping()),
            $value
        );

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
            $fields[] = $node[0];
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
            $result[] = $node[0] . ' = ' . ($node[1] === null ? 'NULL' : $node[1]);
        }

        return 'SET ' . implode(', ', $result);
    }

    /**
     * Adds inner join with set container
     *
     * @param string $container
     * @param array  $joins
     *
     * @return $this
     */
    public function innerJoin($container, array $joins)
    {
        return $this->join(self::JOIN_INNER, $container, $joins);
    }

    /**
     * Adds left join with set container
     *
     * @param string $container
     * @param array  $joins
     *
     * @return $this
     */
    public function leftJoin($container, array $joins)
    {
        return $this->join(self::JOIN_LEFT, $container, $joins);
    }

    /**
     * Adds right join with set container
     *
     * @param string $container
     * @param array  $joins
     *
     * @return $this
     */
    public function rightJoin($container, array $joins)
    {
        return $this->join(self::JOIN_RIGHT, $container, $joins);
    }

    /**
     * Adds join to query
     *
     * @param string $type
     * @param array  $container
     * @param array  $joins
     *
     * @return $this
     * @throws BuilderException
     */
    public function join($type, $container, array $joins)
    {
        if (!isset($this->joinTypes[$type])) {
            throw new BuilderException(sprintf('Query builder does not supports join type "%s"', $type));
        }

        if (empty($joins)) {
            throw new BuilderException(sprintf('Empty join array for join type "%s"', $type));
        }

        if (is_array($container) && !is_numeric(key($container))) {
            $container = array(key($container), reset($container));
        } elseif (is_array($container) && count($container) == 2) {
            $container = array(reset($container), end($container));
        } else {
            $container = array($container, null);
        }

        $container = array(
            $this->quote($container[0]),
            $container[1] ? $this->quote($container[1]) : null
        );

        $join = array(
            $type,
            $container,
            array()
        );

        $mapping = isset($container[1]) ? $container[1] : $container[0];
        foreach ($joins as $local => $foreign) {
            $join[2][] = array(
                $this->quote($local, $this->mapping()),
                $this->quote($foreign, $mapping)
            );
        }

        $this->joins[] = $join;

        return $this;
    }

    /**
     * Adds where condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     * @throws BuilderException
     */
    public function where($field, $value, $comparison = self::COMPARISON_EQUAL, $logical = self::LOGICAL_AND)
    {
        $this->assertComparisonOperator($comparison);
        $this->assertLogicalOperator($logical);

        if (is_array($field)) {
            array_walk_recursive($field, array($this, 'quoteCallback'));
        } else {
            $field = $this->quote($field, $this->mapping());
        }

        $this->where[] = array(
            $field,
            $value,
            $this->comparisonOperators[$comparison],
            $this->logicalOperators[$logical]
        );

        return $this;
    }

    /**
     * Adds having condition to builder
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     * @throws BuilderException
     */
    public function having($field, $value, $comparison = self::COMPARISON_EQUAL, $logical = self::LOGICAL_AND)
    {
        $this->assertComparisonOperator($comparison);
        $this->assertLogicalOperator($logical);

        if (is_array($field)) {
            array_walk_recursive($field, array($this, 'quoteCallback'));
        } else {
            $field = $this->quote($field, $this->mapping());
        }

        $this->having[] = array(
            $field,
            $value,
            $this->comparisonOperators[$comparison],
            $this->logicalOperators[$logical]
        );

        return $this;
    }

    protected function assertComparisonOperator($operator)
    {
        if (!isset($this->comparisonOperators[$operator])) {
            throw new BuilderException(sprintf('Query builder does not supports comparison operator "%s"', $operator));
        }
    }

    protected function assertLogicalOperator($operator)
    {
        if (!isset($this->logicalOperators[$operator])) {
            throw new BuilderException(sprintf('Query builder does not supports logical operator "%s"', $operator));
        }
    }

    protected function quoteCallback(&$field)
    {
        $field = $this->quote($field, $this->mapping());
    }

    protected function buildWhere()
    {
        if (empty($this->where)) {
            return null;
        }

        $result = $this->buildConditions($this->where);

        return 'WHERE ' . implode(' ', $result);
    }

    protected function buildHaving()
    {
        if (empty($this->having)) {
            return null;
        }

        $result = $this->buildConditions($this->having);

        return 'HAVING ' . implode(' ', $result);
    }

    protected function buildConditions(&$conditions)
    {
        if (empty($conditions)) {
            return null;
        }

        $result = array();

        foreach ($conditions as $node) {
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

        return $result;
    }

    protected function buildConditionString($field, $bind, $operator)
    {
        if (is_array($bind)) {
            foreach ($bind as &$val) {
                $val = $this->buildConditionString($field, $val, $operator);
                unset($val);
            }

            $operator = $operator == self::COMPARISON_NOT_EQUAL ? self::LOGICAL_AND : self::LOGICAL_OR;

            return '(' . implode(sprintf(' %s ', $this->logicalOperators[$operator]), $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        if ($operator == self::COMPARISON_REGEX) {
            return sprintf('LOWER(%s) %s LOWER(%s)', $field, $operator, $bind);
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
            throw new BuilderException(sprintf('Query builder does not supports order method "%s"', $order));
        }

        $field = $this->quote($field, $this->mapping());

        if (is_array($order)) {
            $this->order[] = array(
                $field,
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
                $output[] = $node[0] . ' ' . $node[1];
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
     * @throws BuilderException
     */
    public function build()
    {
        if (empty($this->container)) {
            throw new BuilderException('Missing container name');
        }

        $stmt = array();

        switch ($this->operation) {
            case self::OPERATION_SELECT:
                $stmt[] = 'SELECT';
                $stmt[] = $this->buildFields();
                $stmt[] = 'FROM';
                $stmt[] = $this->buildContainer();
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildGroup();
                $stmt[] = $this->buildHaving();
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
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_DELETE:
                $stmt[] = 'DELETE FROM';
                $stmt[] = $this->buildContainer();
                $stmt[] = $this->buildWhere();
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
     * Resets builder
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;

        $this->container = null;
        $this->joins = array();

        $this->fields = array();
        $this->aggregates = array();
        $this->group = array();
        $this->subs = array();

        $this->values = array();

        $this->where = array();
        $this->having = array();

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
            return get_class($e) . ' - ' . $e->getMessage();
        }
    }
}