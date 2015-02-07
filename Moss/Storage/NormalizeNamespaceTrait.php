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


trait NormalizeNamespaceTrait
{
    /**
     * Returns fully qualified absolute class name
     *
     * @param string|object $class
     *
     * @return string
     */
    public function normalizeNamespace($class)
    {
        if ($class === null) {
            return null;
        }

        if (is_object($class)) {
            $class = get_class($class);
        }

        return ltrim($class, '\\');
    }
}
