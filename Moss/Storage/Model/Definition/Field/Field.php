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
 * Field describes how entities property is mapped to field in database
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class Field implements FieldInterface
{
    protected $table;
    protected $name;
    protected $type;
    protected $mapping;
    protected $attributes;

    protected function prepareAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                unset($attributes[$key]);
                $attributes[$value] = true;
                continue;
            }

            if($key == 'default') {
                $attributes['null'] = true;
            }
        }

        return $attributes;
    }

    protected function verifyAttribute($allowed = array())
    {
        foreach (array_keys($this->attributes) as $attr) {
            if (!in_array($attr, $allowed)) {
                throw new DefinitionException(sprintf('Forbidden attribute "%s" in field type "%s"', $attr, $this->type));
            }
        }
    }

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
     * Returns field table mapping or null when no mapping
     *
     * @return null|string
     */
    public function mapping()
    {
        return $this->mapping ? $this->mapping : $this->name;
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
