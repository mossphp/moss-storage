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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelInterface;


/**
 * Abstract Query
 * Implements basic query methods
 *
 * @package Moss\Storage\Query\OperationTraits
 */
abstract class AbstractEntityQuery extends AbstractQuery
{
    /**
     * Sets field names which values will be written
     *
     * @param array $fields
     *
     * @return $this
     */
    public function values($fields = [])
    {
        $parts = $this->builder()->getQueryParts();
        foreach (['set', 'value'] as $part) {
            if (isset($parts[$part])) {
                $this->builder()->resetQueryPart($part);
            }
        }

        $this->resetBinds('value');

        if (empty($fields)) {
            foreach ($this->model()->fields() as $field) {
                $this->assignValue($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->assignValue($this->model()->field($field));
        }

        return $this;
    }

    /**
     * Adds field which value will be written
     *
     * @param string $field
     *
     * @return $this
     */
    public function value($field)
    {
        $this->assignValue($this->model()->field($field));

        return $this;
    }

    /**
     * Assigns value to query
     *
     * @param FieldInterface $field
     */
    abstract protected function assignValue(FieldInterface $field);
}
