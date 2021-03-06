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

use Moss\Storage\Model\Definition\FieldInterface;

/**
 * Abstract Entity Values Query
 * Provides functionality for changing entity values
 *
 * @package Moss\Storage\Query\OperationTraits
 */
abstract class AbstractEntityValueQuery extends AbstractEntityQuery
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

    /**
     * Gets value from first referenced entity that has it
     *
     * @param FieldInterface $field
     */
    protected function getValueFromReferencedEntity(FieldInterface $field)
    {
        $references = $this->model->referredIn($field->name());
        foreach ($references as $foreign => $reference) {
            $entity = $this->accessor->getPropertyValue($this->instance, $reference->container());

            if ($entity === null) {
                continue;
            }

            $value = $this->accessor->getPropertyValue($entity, $foreign);
            $this->accessor->setPropertyValue($this->instance, $field->name(), $value);
            break;
        }
    }
}
