<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Builder;

/**
 * Abstract query builder
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    protected $aggregateMethods = array(
        'distinct' => null,
        'count' => null,
        'average' => null,
        'max' => null,
        'min' => null,
        'sum' => null
    );

    protected $joinTypes = array(
        'inner' => null,
        'left' => null,
        'right' => null
    );

    protected $comparisonOperators = array(
        '=' => null,
        '!=' => null,
        '<' => null,
        '>' => null,
        '<=' => null,
        '>=' => null,
        'like' => null,
        'regex' => null
    );

    protected $logicalOperators = array(
        'and' => null,
        'or' => null
    );

    protected $orderMethods = array(
        'asc' => null,
        'desc' => null
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
     * @param string      $string
     * @param null|string $table
     *
     * @return string
     */
    protected function quote($string, $table = null)
    {
        if ($this->operation !== 'select') {
            return $string;
        }

        $array = explode(self::SEPARATOR, $string, 2);

        if (count($array) === 1 && $table === null) {
            return $string;
        }

        if (!isset($array[1])) {
            array_unshift($array, $table);
        }

        return $array[0] . '.' . $array[1];
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

    /**
     * Returns table alias if exists or its name
     *
     * @return string
     */
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

    /**
     * Builds table with all required joins
     *
     * @return string
     */
    abstract protected function buildTable();

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

    /**
     * Builds GROUP statement
     *
     * @return string
     */
    abstract protected function buildGroup();

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

    /**
     * Builds field list with aggregation methods for SELECT statements
     *
     * @return string
     */
    abstract protected function buildFields();

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

    /**
     * Builds field-value definition for INSERT statements
     *
     * @return string
     */
    abstract protected function buildInsertValues();

    /**
     * Builds field-value definition for UPDATE statements
     *
     * @return string
     */
    abstract protected function buildUpdateValues();

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
        $this->where[] = $this->condition($field, $value, $comparison, $logical, array($this, 'quoteWhereCallback'));

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
        $this->having[] = $this->condition($field, $value, $comparison, $logical, array($this, 'quoteHavingCallback'));

        return $this;
    }

    /**
     * Builds generic condition array
     *
     * @param array|string  $field
     * @param mixed         $value
     * @param string        $comparison
     * @param string        $logical
     * @param null|callback $quotingCallback
     *
     * @return array
     */
    protected function condition($field, $value, $comparison = '=', $logical = 'and', $quotingCallback = null)
    {
        $this->assertComparisonOperator($comparison);
        $this->assertLogicalOperator($logical);

        if ($quotingCallback) {
            is_array($field) ? array_walk_recursive($field, $quotingCallback) : call_user_func_array($quotingCallback, array(&$field));
        }

        return array(
            $field,
            $value,
            $this->comparisonOperators[$comparison],
            $this->logicalOperators[$logical]
        );
    }

    /**
     * Asserts correct comparison operator
     *
     * @param string $operator
     *
     * @throws BuilderException
     */
    protected function assertComparisonOperator($operator)
    {
        if (!isset($this->comparisonOperators[$operator])) {
            throw new BuilderException(sprintf('Query builder does not supports comparison operator "%s"', $operator));
        }
    }

    /**
     * Asserts correct logical operator
     *
     * @param string $operator
     *
     * @throws BuilderException
     */
    protected function assertLogicalOperator($operator)
    {
        if (!isset($this->logicalOperators[$operator])) {
            throw new BuilderException(sprintf('Query builder does not supports logical operator "%s"', $operator));
        }
    }

    /**
     * Quotes field names for where conditions
     *
     * @param $field
     */
    protected function quoteWhereCallback(&$field)
    {
        $field = $this->quote($field, $this->mapping());
    }

    /**
     * Quotes field names for having conditions
     *
     * @param $field
     */
    protected function quoteHavingCallback(&$field)
    {
        $table = isset($this->aggregates[$field]) ? null : $this->mapping();
        $field = $this->quote($field, $table);
    }

    /**
     * Builds where query part
     *
     * @return string
     */
    protected function buildWhere()
    {
        $conditions = $this->buildConditions($this->where);
        if (empty($conditions)) {
            return null;
        }

        return 'WHERE ' . implode(' ', $conditions);
    }

    /**
     * Builds having query part
     *
     * @return string
     */
    protected function buildHaving()
    {
        $conditions = $this->buildConditions($this->having);
        if (empty($conditions)) {
            return null;
        }

        return 'HAVING ' . implode(' ', $conditions);
    }

    /**
     * Builds array of conditions
     *
     * @param array $conditions
     *
     * @return array
     */
    abstract protected function buildConditions(&$conditions);

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

    /**
     * Builds ORDER BY for query
     *
     * @return string
     */
    abstract protected function buildOrder();

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

    /**
     * Builds LIMIT part
     *
     * @return string
     */
    abstract protected function buildLimit();

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
        return $this->build();
    }
}
