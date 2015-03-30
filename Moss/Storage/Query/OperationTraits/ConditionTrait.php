<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\OperationTraits;


use Moss\Storage\Query\QueryException;

/**
 * Trait ConditionTrait
 * Adds method to add conditions to query
 *
 * @package Moss\Storage\Query\OperationTraits
 */
trait ConditionTrait
{
    use AwareTrait;

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
    public function where($field, $value, $comparison = '=', $logical = 'and')
    {
        $condition = $this->condition($field, $value, $comparison, $logical);

        if ($this->normalizeLogical($logical) === 'or') {
            $this->query()->orWhere($condition);

            return $this;
        }

        $this->query()->andWhere($condition);

        return $this;
    }

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
        $comparison = $this->normalizeComparison($comparison);
        $logical = $this->normalizeLogical($logical);

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
        $field = $this->model()->field($field);

        return $this->buildConditionString(
            $this->connection()->quoteIdentifier($field->mappedName()),
            $value === null ? null : $this->bindValues($field->mappedName(), $field->type(), $value),
            $comparison
        );
    }

    /**
     * Builds conditions for multiple fields
     *
     * @param array  $fields
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return array
     */
    protected function buildMultipleFieldsCondition($fields, $value, $comparison, $logical)
    {
        $conditions = [];
        foreach ((array) $fields as $field) {
            $field = $this->model()->field($field);

            $fieldName = $field->mappedName();
            $conditions[] = $this->buildConditionString(
                $this->connection()->quoteIdentifier($fieldName),
                $value === null ? null : $this->bindValues($fieldName, $field->type(), $value),
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

            $logical = $operator === '!=' ? ' and ' : ' or ';

            return '(' . implode($logical, $bind) . ')';
        }

        if ($bind === null) {
            return $field . ' ' . ($operator == '!=' ? 'IS NOT NULL' : 'IS NULL');
        }

        if ($operator === 'regexp') {
            return sprintf('%s regexp %s', $field, $bind);
        }

        return $field . ' ' . $operator . ' ' . $bind;
    }

    /**
     * Asserts correct comparison operator
     *
     * @param string $operator
     *
     * @return string
     * @throws QueryException
     */
    protected function normalizeComparison($operator)
    {
        switch (strtolower($operator)) {
            case '<':
            case 'lt':
                return '<';
            case '<=':
            case 'lte':
                return '<=';
            case '>':
            case 'gt':
                return '>';
            case '>=':
            case 'gte':
                return '>=';
            case '~':
            case '~=':
            case '=~':
            case 'regex':
            case 'regexp':
                return "regexp";
            // LIKE
            case 'like':
                return "like";
            case '||':
            case 'fulltext':
            case 'fulltext_boolean':
                return 'fulltext';
            case '<>':
            case '!=':
            case 'ne':
            case 'not':
                return '!=';
            case '=':
            case 'eq':
                return '=';
            default:
                throw new QueryException(sprintf('Query does not supports comparison operator "%s" in query "%s"', $operator, $this->model()->entity()));
        }
    }

    /**
     * Asserts correct logical operation
     *
     * @param string $operator
     *
     * @return string
     * @throws QueryException
     */
    protected function normalizeLogical($operator)
    {
        switch (strtolower($operator)) {
            case '&&':
            case 'and':
                return 'and';
            case '||':
            case 'or':
                return 'or';
            default:
                throw new QueryException(sprintf('Query does not supports logical operator "%s" in query "%s"', $operator, $this->model()->entity()));
        }
    }

    /**
     * Binds condition value to key
     *
     * @param $name
     * @param $type
     * @param $values
     *
     * @return string
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
}
