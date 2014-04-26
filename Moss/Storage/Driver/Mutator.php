<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Driver;


class Mutator implements MutatorInterface
{

    /**
     * Converts set type to storable value
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return null|string
     */
    public function store($value, $type)
    {
        if ($this->isNullValue($value)) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return (int) (bool) $value;
            case 'integer':
                return (int) $value;
            case 'decimal':
                $value = preg_replace('/[^0-9,.\-]+/i', null, $value);
                $value = str_replace(',', '.', $value);

                return (float) $value;
            case 'datetime' && $value instanceof \DateTime:
                return $value->format('Y-m-d H:i:s');
            case 'serial':
                return base64_encode(serialize($value));
            default:
                return $value;
        }
    }

    /**
     * Converts from storable to set type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function restore($value, $type)
    {
        if ($this->isNullValue($value)) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'decimal':
                $value = preg_replace('/[^0-9,.\-]+/i', null, $value);
                $value = str_replace(',', '.', $value);
                $value = strpos($value, '.') === false ? (int) $value : (float) $value;

                return $value;
            case 'datetime':
                return new \DateTime((is_numeric($value) ? '@' : '') . $value);
            case 'serial':
                return unserialize(base64_decode($value));
            default:
                return $value;
        }
    }

    /**
     * Returns true if value is ALMOST null (empty string or null)
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isNullValue($value)
    {
        return is_scalar($value) && $value !== false && !strlen($value);
    }
} 