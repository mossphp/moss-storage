<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query;


/**
 * Abstract base class with condition building methods
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class AbstractConditionalQuery extends AbstractQuery
{
    const COMPARISON_EQUAL = '=';
    const COMPARISON_NOT_EQUAL = '!=';
    const COMPARISON_LESS = '<';
    const COMPARISON_LESS_OR_EQUAL = '<=';
    const COMPARISON_GREATER = '>';
    const COMPARISON_GREATER_OR_EQUAL = '>=';
    const COMPARISON_LIKE = 'like';
    const COMPARISON_REGEXP = 'regexp';

    const LOGICAL_AND = 'and';
    const LOGICAL_OR = 'or';

    /**
     * Adds where condition to query
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     * @throws QueryException
     */
    public function condition($field, $value, $comparison, $logical)
    {
        $comparison = strtolower($comparison);
        $logical = strtolower($logical);

        $this->assertComparison($comparison);
        $this->assertLogical($logical);

        if (!is_array($field)) {
            return $this->buildSingularFieldCondition($field, $value, $comparison);
        }

        return $this->buildMultipleFieldsCondition($field, $value, $comparison, $logical);
    }

    /**
     * Builds condition for singular field
     *
     * @param string $field
     * @param mixed  $value
     * @param string $comparison
     *
     * @return array
     */
    protected function buildSingularFieldCondition($field, $value, $comparison)
    {
        $f = $this->model->field($field);

        $fieldName = $f->mappedName();
        return $this->buildConditionString(
            $this->connection->quoteIdentifier($fieldName),
            $value === null ? null : $this->bindValues($fieldName, $f->type(), $value),
            $comparison
        );
    }

    /**
     * Builds conditions for multiple fields
     *
     * @param array  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return array
     */
    protected function buildMultipleFieldsCondition($field, $value, $comparison, $logical)
    {
        $conditions = [];
        foreach ((array) $field as $i => $f) {
            $f = $this->model->field($f);

            $fieldName = $f->mappedName();
            $conditions[] = $this->buildConditionString(
                $this->connection->quoteIdentifier($fieldName),
                $value === null ? null : $this->bindValues($fieldName, $f->type(), $value),
                $comparison
            );

            $conditions[] = $logical;
        }

        array_pop($conditions);

        return '(' . implode(' ', $conditions) . ')';
    }

    /**
     * Builds condition string
     *
     * @param string       $field
     * @param string|array $bind
     * @param string       $operator
     *
     * @return string
     */
    protected function buildConditionString($field, $bind, $operator)
    {
        if (is_array($bind)) {
            foreach ($bind as &$val) {
                $val = $this->buildConditionString($field, $val, $operator);
                unset($val);
            }

            $operator = $operator === self::COMPARISON_NOT_EQUAL ? 'and' : 'or';

            return '(' . implode(sprintf(' %s ', $operator), $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        if ($operator === self::COMPARISON_REGEXP) {
            return sprintf('%s regexp %s', $field, $bind);
        }

        return $field . ' ' . $operator . ' ' . $bind;
    }

    /**
     * Asserts correct comparison operator
     *
     * @param string $operator
     *
     * @throws QueryException
     */
    protected function assertComparison($operator)
    {
        $comparisonOperators = [
            self::COMPARISON_EQUAL,
            self::COMPARISON_NOT_EQUAL,
            self::COMPARISON_LESS,
            self::COMPARISON_LESS_OR_EQUAL,
            self::COMPARISON_GREATER,
            self::COMPARISON_GREATER_OR_EQUAL,
            self::COMPARISON_LIKE,
            self::COMPARISON_REGEXP
        ];

        if (!in_array($operator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $operator, $this->model->entity()));
        }
    }

    /**
     * Asserts correct logical operation
     *
     * @param string $operator
     *
     * @throws QueryException
     */
    protected function assertLogical($operator)
    {
        $comparisonOperators = [
            self::LOGICAL_AND,
            self::LOGICAL_OR
        ];

        if (!in_array($operator, $comparisonOperators)) {
            throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $operator, $this->model->entity()));
        }
    }

    /**
     * Binds condition value to key
     *
     * @param $name
     * @param $type
     * @param $values
     *
     * @return array|string
     */
    protected function bindValues($name, $type, $values)
    {
        if (!is_array($values)) {
            return $this->bind('condition', $name, $type, $values);
        }

        foreach ($values as $key => $value) {
            $values[$key] = $this->bindValues($name, $type, $value);
        }

        return $values;
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
        if ($offset !== null) {
            $this->query->setFirstResult((int) $offset);
        }

        $this->query->setMaxResults((int) $limit);

        return $this;
    }
}
