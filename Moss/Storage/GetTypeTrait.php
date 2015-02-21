<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage;

/**
 * Handy trait that returns variable type or its class when its an object
 *
 * @package Moss\Storage
 */
trait GetTypeTrait
{
    /**
     * Returns var type
     *
     * @param mixed $var
     *
     * @return string
     */
    protected function getType($var)
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }
}
