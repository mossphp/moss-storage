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
 * DateTime field
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class DateTime extends Field
{
    /**
     * Constructor
     *
     * @param string $field
     * @param array  $attributes
     * @param null|string $mapping
     */
    public function __construct($field, $attributes = array(), $mapping = null)
    {
        $this->name = $field;
        $this->type = 'datetime';
        $this->mapping = $mapping;

        $this->attributes = $this->prepareAttributes($attributes);
        $this->verifyAttribute(array('null', 'default'));
    }
}
