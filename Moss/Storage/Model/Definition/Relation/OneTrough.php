<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model\Definition\Relation;

use Moss\Storage\NormalizeNamespaceTrait;

/**
 * One to one trough mediator table (with pivot table)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class OneTrough extends TroughRelation
{
    use NormalizeNamespaceTrait;

    protected $type = 'oneTrough';
}
