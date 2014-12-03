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
class Integer extends String
{
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
            'integer',
            $field,
            array_merge(['length' => 11], $attributes),
            $mapping,
            ['length', 'null', 'auto_increment', 'default']
        );
    }
}
