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
 * Serial field
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Boolean extends Field
{
    public function __construct($field, $attributes = array(), $mapping = null)
    {
        $this->name = $field;
        $this->type = 'boolean';
        $this->mapping = $mapping;

        $this->attributes = $this->prepareAttributes($attributes);
        $this->verifyAttribute(array('null', 'default'));
    }
}
