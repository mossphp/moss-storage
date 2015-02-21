<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model\Definition\Field;

use Moss\Storage\Model\Definition\DefinitionException;
use Moss\Storage\Model\Definition\FieldInterface;

/**
 * String field
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class String implements FieldInterface
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
     * @param array       $attributes
     * @param null|string $mapping
     */
    public function __construct($field, $attributes = [], $mapping = null)
    {
        $this->initialize(
            'string',
            $field,
            array_merge(['length' => null], $attributes),
            empty($mapping) ? null : $mapping,
            ['length', 'notnull', 'default']
        );
    }

    /**
     * Initializes field
     *
     * @param string $type
     * @param string $field
     * @param array $attributes
     * @param null|string  $mapping
     * @param array $allowedAttr
     */
    protected function initialize($type, $field, array $attributes, $mapping = null, $allowedAttr = [])
    {
        $this->name = $field;
        $this->type = $type;
        $this->mapping = $mapping;

        $this->attributes = $this->prepareAttributes($attributes);
        $this->verifyAttribute($allowedAttr);
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

        if(isset($attributes['null'])) {
            unset($attributes['null']);
            $attributes['notnull'] = false;
        }

        return $attributes;
    }

    /**
     * Checks if attributes contain forbidden keys
     *
     * @param array $allowed
     *
     * @throws DefinitionException
     */
    protected function verifyAttribute($allowed = [])
    {
        foreach (array_keys($this->attributes) as $attr) {
            if (!in_array($attr, $allowed)) {
                throw new DefinitionException(sprintf('Forbidden attribute "%s" in field type "%s"', $attr, $this->type));
            }
        }
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
