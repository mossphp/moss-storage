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
 * Decimal field
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Decimal extends String
{
    /**
     * Constructor
     *
     * @param string $field
     * @param array  $attributes
     * @param null|string $mapping
     */
    public function __construct($field, $attributes = [], $mapping = null)
    {
        $this->initialize(
            'decimal',
            $field,
            array_merge(['length' => 11, 'precision' => 4], $attributes),
            $mapping,
            ['length', 'precision', 'null', 'default']
        );
    }
}
