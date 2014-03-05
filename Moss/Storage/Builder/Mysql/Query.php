<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder\MySQL;

use Moss\Storage\Builder\BuilderException;
use Moss\Storage\Builder\QueryInterface;

/**
 * MySQL query builder - builds CRUD queries
 *
 * @author Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\MySQL
 */
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

    protected $table;
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
     * @param string $table
     * @param string $alias
     * @param string $operation
     */
    public function __construct($table = null, $alias = null, $operation = self::OPERATION_SELECT)
    {
        if ($table !== null) {
            $this->table($table, $alias);
            $this->operation($operation);
        }
    }

    /**
     * @param string $string
     * @param null $table
     *
     * @return string
     */
    protected function quote($string, $table = null)
    {
        $array = explode(self::SEPARATOR, $string, 2);

        if ($this->operation !== self::OPERATION_SELECT) {
            return self::QUOTE . $string . self::QUOTE;
        }

        if (count($array) !== 2 && $table === null) {
            return self::QUOTE . $string . self::QUOTE;
        }

        if (!isset($array[1])) {
            array_unshift($array, $table);
        }

        if (strpos($array[0], self::QUOTE) !== 0) {
            $array[0] = self::QUOTE . $array[0] . self::QUOTE;
        }

        return $array[0] . self::SEPARATOR . self::QUOTE . $array[1] . self::QUOTE;
    }

    /**
     * Sets select operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function select($table, $alias = null)
    {
        return $this->operation(self::OPERATION_SELECT)
            ->table($table, $alias);
    }

    /**
     * Sets insert operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function insert($table, $alias = null)
    {
        return $this->operation(self::OPERATION_INSERT)
            ->table($table, $alias);
    }

    /**
     * Sets update operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function update($table, $alias = null)
    {
        return $this->operation(self::OPERATION_UPDATE)
            ->table($table, $alias);
    }

    /**
     * Sets delete operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function delete($table, $alias = null)
    {
        return $this->operation(self::OPERATION_DELETE)
            ->table($table, $alias);
    }

    /**
     * Sets clear operation on table
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     */
    public function clear($table, $alias = null)
    {
        return $this->operation(self::OPERATION_CLEAR)
            ->table($table, $alias);
    }

    /**
     * Sets table name
     *
     * @param string $table
     * @param string $alias
     *
     * @return $this
     * @throws BuilderException
     */
    public function table($table, $alias = null)
    {
        if (empty($table)) {
            throw new BuilderException('Missing table name');
        }

        $this->table = array(
            $this->quote($table),
            $alias ? $this->quote($alias) : null
        );

        return $this;
    }

    protected function mapping()
    {
        return $this->table[1] ? $this->table[1] : $this->table[0];
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

    protected function buildTable()
    {
        $result = array();
        $result[] = $this->table[0];

        if ($this->operation !== self::OPERATION_SELECT) {
            return implode(' ', $result);
        }

        if ($this->table[1]) {
            $result[] = 'AS';
            $result[] = $this->table[1];
        }

        foreach ($this->joins as $node) {
            list($type, $table, $joins) = $node;

            $result[] = $this->joinTypes[$type];
            $result[] = $table[0];
            if ($table[1]) {
                $result[] = 'AS';
                $result[] = $table[1];
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
     * Adds inner join with set table
     *
     * @param string $table
     * @param array  $joins
     *
     * @return $this
     */
    public function innerJoin($table, array $joins)
    {
        return $this->join(self::JOIN_INNER, $table, $joins);
    }

    /**
     * Adds left join with set table
     *
     * @param string $table
     * @param array  $joins
     *
     * @return $this
     */
    public function leftJoin($table, array $joins)
    {
        return $this->join(self::JOIN_LEFT, $table, $joins);
    }

    /**
     * Adds right join with set table
     *
     * @param string $table
     * @param array  $joins
     *
     * @return $this
     */
    public function rightJoin($table, array $joins)
    {
        return $this->join(self::JOIN_RIGHT, $table, $joins);
    }

    /**
     * Adds join to query
     *
     * @param string $type
     * @param array  $table
     * @param array  $joins
     *
     * @return $this
     * @throws BuilderException
     */
    public function join($type, $table, array $joins)
    {
        if (!isset($this->joinTypes[$type])) {
            throw new BuilderException(sprintf('Query builder does not supports join type "%s"', $type));
        }

        if (empty($joins)) {
            throw new BuilderException(sprintf('Empty join array for join type "%s"', $type));
        }

        if (is_array($table) && !is_numeric(key($table))) {
            $table = array(key($table), reset($table));
        } elseif (is_array($table) && count($table) == 2) {
            $table = array(reset($table), end($table));
        } else {
            $table = array($table, null);
        }

        $table = array(
            $this->quote($table[0]),
            $table[1] ? $this->quote($table[1]) : null
        );

        $join = array(
            $type,
            $table,
            array()
        );

        $mapping = isset($table[1]) ? $table[1] : $table[0];
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
        if (empty($this->table)) {
            throw new BuilderException('Missing table name');
        }

        $stmt = array();

        switch ($this->operation) {
            case self::OPERATION_SELECT:
                $stmt[] = 'SELECT';
                $stmt[] = $this->buildFields();
                $stmt[] = 'FROM';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildGroup();
                $stmt[] = $this->buildHaving();
                $stmt[] = $this->buildOrder();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_INSERT:
                $stmt[] = 'INSERT INTO';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildInsertValues();
                break;
            case self::OPERATION_UPDATE:
                $stmt[] = 'UPDATE';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildUpdateValues();
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_DELETE:
                $stmt[] = 'DELETE FROM';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildLimit();
                break;
            case self::OPERATION_CLEAR:
                $stmt[] = 'TRUNCATE TABLE';
                $stmt[] = $this->buildTable();
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

        $this->table = null;
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
        } catch (BuilderException $e) {
            return get_class($e) . ' - ' . $e->getMessage();
        }
    }
}