<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Join;

use Moss\Storage\Model\Definition\FieldInterface;

/**
 * Table join definition interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface JoinInterface
{
    /**
     * Returns join type
     *
     * @return string
     */
    public function type();

    /**
     * Returns join alias
     *
     * @return string
     */
    public function alias();

    /**
     * Returns true if joined entity is named
     *
     * @param string $name
     *
     * @return string
     */
    public function isNamed($name);

    /**
     * Returns entity name
     *
     * @return string
     */
    public function entity();

    /**
     * Returns field definitions from joined model
     *
     * @return FieldInterface[]
     */
    public function fields();

    /**
     * Returns field definition from joined model
     *
     * @param string $field
     *
     * @return FieldInterface
     */
    public function field($field);

    /**
     * Returns joins
     *
     * @return array
     */
    public function joints();

    /**
     * Returns join conditions
     *
     * @return array
     */
    public function conditions();
}
