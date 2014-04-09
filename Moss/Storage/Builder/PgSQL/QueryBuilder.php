<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder\PgSQL;

use Moss\Storage\Builder\BuilderException;
use Moss\Storage\Builder\QueryBuilderInterface;

/**
 * MySQL query builder - builds CRUD queries
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\MySQL
 */
class QueryBuilder implements QueryBuilderInterface
{
    const QUOTE = '"';

    protected $aggregateMethods = array(
        'distinct' => 'DISTINCT',
        'count' => 'COUNT',
        'average' => 'AVERAGE',
        'max' => 'MAX',
        'min' => 'MIN',
        'sum' => 'SUM'
    );

    protected $joinTypes = array(
        'inner' => 'INNER JOIN',
        'left' => 'LEFT OUTER JOIN',
        'right' => 'RIGHT OUTER JOIN',
    );

    protected $comparisonOperators = array(
        '=' => '=',
        '!=' => '!=',
        '<' => '<',
        '>' => '>',
        '<=' => '<=',
        '>=' => '>=',
        'like' => 'LIKE',
        'regex' => '~'
    );

    protected $logicalOperators = array(
        'and' => 'AND',
        'or' => 'OR',
    );

    protected $orderMethods = array(
        'asc' => 'ASC',
        'desc' => 'DESC',
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
    public function __construct($table = null, $alias = null, $operation = 'select')
    {
        if ($table !== null) {
            $this->table($table, $alias);
            $this->operation($operation);
        }
    }

    /**
     * @param string $string
     * @param null   $table
     *
     * @return string
     */
    protected function quote($string, $table = null)
    {
        $array = explode(self::SEPARATOR, $string, 2);

        if ($this->operation !== 'select') {
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

        return $array[0] . '.' . self::QUOTE . $array[1] . self::QUOTE;
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
        return $this->operation('select')
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
        return $this->operation('insert')
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
        return $this->operation('update')
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
        return $this->operation('delete')
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
        return $this->operation('clear')
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
            case 'select':
            case 'insert':
            case 'update':
            case 'delete':
            case 'clear':
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

        if ($this->operation !== 'select') {
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
                $result[] = $join[0] . ' = ' . $join[1];
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
        return $this->aggregate('distinct', $field, $alias);
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
        return $this->aggregate('count', $field, $alias);
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
        return $this->aggregate('average', $field, $alias);
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
        return $this->aggregate('max', $field, $alias);
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
        return $this->aggregate('min', $field, $alias);
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
        return $this->aggregate('sum', $field, $alias);
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

        $alias = $alias ? $alias : strtolower($method);

        $this->aggregates[$alias] = array(
            $method,
            $this->quote($field, $this->mapping()),
            $this->quote($alias),
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
     * @param QueryBuilderInterface $query
     * @param string                $alias
     *
     * @return $this
     */
    public function sub(QueryBuilderInterface $query, $alias)
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

        return sprintf(
            '(%s) %s (%s)',
            implode(', ', $fields),
            count($fields) > 1 ? 'VALUES' : 'VALUE',
            implode(', ', $values)
        );
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
     * @param string $alias
     *
     * @return $this
     */
    public function innerJoin($table, array $joins, $alias = null)
    {
        return $this->join('inner', $table, $joins, $alias);
    }

    /**
     * Adds left join with set table
     *
     * @param string $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     */
    public function leftJoin($table, array $joins, $alias = null)
    {
        return $this->join('left', $table, $joins, $alias);
    }

    /**
     * Adds right join with set table
     *
     * @param string $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     */
    public function rightJoin($table, array $joins, $alias = null)
    {
        return $this->join('right', $table, $joins, $alias);
    }

    /**
     * Adds join to query
     *
     * @param string $type
     * @param array  $table
     * @param array  $joins
     * @param string $alias
     *
     * @return $this
     * @throws BuilderException
     */
    public function join($type, $table, array $joins, $alias = null)
    {
        if (!isset($this->joinTypes[$type])) {
            throw new BuilderException(sprintf('Query builder does not supports join type "%s"', $type));
        }

        if (empty($joins)) {
            throw new BuilderException(sprintf('Empty join array for join type "%s"', $type));
        }

        $join = array(
            $type,
            array(
                $this->quote($table),
                $alias ? $this->quote($alias) : null
            ),
            array()
        );

        $mapping = isset($alias) ? $alias : $table;
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
    public function where($field, $value, $comparison = '=', $logical = 'and')
    {
        $this->assertComparisonOperator($comparison);
        $this->assertLogicalOperator($logical);

        if (is_array($field)) {
            array_walk_recursive($field, array($this, 'quoteWhereCallback'));
        } else {
            $this->quoteWhereCallback($field);
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
    public function having($field, $value, $comparison = '=', $logical = 'and')
    {
        $this->assertComparisonOperator($comparison);
        $this->assertLogicalOperator($logical);

        if (is_array($field)) {
            array_walk_recursive($field, array($this, 'quoteHavingCallback'));
        } else {
            $this->quoteHavingCallback($field);
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

    protected function quoteWhereCallback(&$field)
    {
        $field = $this->quote($field, $this->mapping());
    }

    protected function quoteHavingCallback(&$field)
    {
        $table = isset($this->aggregates[$field]) ? null : $this->mapping();
        $field = $this->quote($field, $table);
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

            $result[] = '(' . implode(' OR ', $condition) . ')';
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

            $operator = $operator === '!=' ? 'AND' : 'OR';

            return '(' . implode(sprintf(' %s ', $operator), $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        if ($operator === '~') {
            return sprintf('%s ~* %s', $field, $bind);
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
    public function order($field, $order = 'desc')
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
                $output[] = $this->buildConditionString($node[0], $v, '=') . ' DESC';
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
            case 'select':
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
            case 'insert':
                $stmt[] = 'INSERT INTO';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildInsertValues();
                break;
            case 'update':
                $stmt[] = 'UPDATE';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildUpdateValues();
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildLimit();
                break;
            case 'delete':
                $stmt[] = 'DELETE FROM';
                $stmt[] = $this->buildTable();
                $stmt[] = $this->buildWhere();
                $stmt[] = $this->buildLimit();
                break;
            case 'clear':
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