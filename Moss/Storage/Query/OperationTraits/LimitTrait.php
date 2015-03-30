<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\OperationTraits;

/**
 * Trait LimitTrait
 * Limit query trait
 *
 * @package Moss\Storage\Query\OperationTraits
 */
trait LimitTrait
{
    use AwareTrait;

    /**
     * Sets limits to query
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        if ($offset !== null) {
            $this->query()->setFirstResult((int) $offset);
        }

        $this->query()->setMaxResults((int) $limit);

        return $this;
    }
}
