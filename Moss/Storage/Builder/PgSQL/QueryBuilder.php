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

use Moss\Storage\Builder\AbstractQueryBuilder;
use Moss\Storage\Builder\BuilderException;
use Moss\Storage\Builder\QueryBuilderInterface;

/**
 * MySQL query builder - builds CRUD queries
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class QueryBuilder extends AbstractQueryBuilder implements QueryBuilderInterface
{
    protected $aggregateMethods = array(
        'distinct' => 'DISTINCT %s',
        'count' => 'COUNT(%s)',
        'average' => 'AVG(%s)',
        'max' => 'MAX(%s)',
        'min' => 'MIN(%s)',
        'sum' => 'SUM(%s)'
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
        'regex' => '~*'
    );

    protected $logicalOperators = array(
        'and' => 'AND',
        'or' => 'OR',
    );

    protected $orderMethods = array(
        'asc' => 'ASC',
        'desc' => 'DESC',
    );

    /**
     * Builds table with all required joins
     *
     * @return string
     */
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
     * Builds GROUP statement
     *
     * @return string
     */
    protected function buildGroup()
    {
        if (empty($this->group)) {
            return null;
        }

        return 'GROUP BY ' . implode(', ', $this->group);
    }

    /**
     * Builds field list with aggregation methods for SELECT statements
     *
     * @return string
     */
    protected function buildFields()
    {
        if (empty($this->fields)) {
            return '*';
        }

        $result = array();

        foreach ($this->aggregates as $node) {
            $result[] = sprintF(
                $this->aggregateMethods[$node[0]] . ' AS %s',
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
     * Builds field-value definition for INSERT statements
     *
     * @return string
     * @throws BuilderException
     */
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

    /**
     * Builds field-value definition for UPDATE statements
     *
     * @return string
     * @throws BuilderException
     */
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
     * Builds array of conditions
     *
     * @param array $conditions
     *
     * @return array
     */
    protected function buildConditions(&$conditions)
    {
        if (empty($conditions)) {
            return array();
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

    private function buildConditionString($field, $bind, $operator)
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
     * Builds ORDER BY for query
     *
     * @return string
     */
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
     * Builds LIMIT part
     *
     * @return string
     */
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
}