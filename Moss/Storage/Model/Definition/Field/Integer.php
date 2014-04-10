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

/**
 * Integer field
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Integer extends Field
{
    public function __construct($field, $attributes = array(), $mapping = null)
    {
        $this->name = $field;
        $this->type = 'integer';
        $this->mapping = $mapping;

        $default = array('length' => 11);
        $this->attributes = $this->prepareAttributes(array_merge($default, $attributes));
        $this->verifyAttribute(array('length', 'null', 'unsigned', 'auto_increment', 'default', 'comment'));
    }
}
