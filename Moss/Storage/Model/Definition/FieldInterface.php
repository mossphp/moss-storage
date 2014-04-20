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
 * Field interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface FieldInterface
{

    /**
     * Returns table that field belongs to
     *
     * @return string
     */
    public function table();

    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name();

    /**
     * Returns relation type
     *
     * @return string
     */
    public function type();

    /**
     * Returns field table mapping or null when no mapping
     *
     * @return null|string
     */
    public function mapping();

    /**
     * Returns attribute value or null if not set
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function attribute($attribute);

    /**
     * Returns array containing field attributes
     *
     * @return array
     */
    public function attributes();
}
