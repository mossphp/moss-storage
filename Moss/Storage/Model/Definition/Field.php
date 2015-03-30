<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model\Definition;

/**
 * String field
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Field implements FieldInterface
{
    protected $table;
    protected $name;
    protected $type;
    protected $mapping;
    protected $attributes;

    /**
     * Constructor
     *
     * @param string      $field
     * @param string      $type
     * @param array       $attributes
     * @param null|string $mapping
     */
    public function __construct($field, $type = 'string', $attributes = [], $mapping = null)
    {
        $this->name = $field;
        $this->type = $type;
        $this->mapping = $mapping;
        $this->attributes = $this->prepareAttributes($attributes);
    }

    /**
     * Prepares attributes, changes them into key value pairs
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function prepareAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                unset($attributes[$key]);
                $attributes[$value] = true;
                continue;
            }
        }

        if (isset($attributes['null'])) {
            unset($attributes['null']);
            $attributes['notnull'] = false;
        }

        return $attributes;
    }

    /**
     * Returns table owning the field
     *
     * @param null|string $table
     *
     * @return string
     */
    public function table($table = null)
    {
        if ($table !== null) {
            $this->table = $table;
        }

        return $this->table;
    }

    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns relation type
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns mapped table cell or null when no mapping
     *
     * @return null|string
     */
    public function mapping()
    {
        return $this->mapping;
    }

    /**
     * Returns fields mapped name
     *
     * @return string
     */
    public function mappedName()
    {
        return $this->mapping ?: $this->name;
    }

    /**
     * Returns attribute value or null if not set
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function attribute($attribute)
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            return null;
        }

        return $this->attributes[$attribute];
    }

    /**
     * Returns array containing field attributes
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }
}
