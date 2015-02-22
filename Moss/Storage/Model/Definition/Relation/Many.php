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

use Moss\Storage\Model\Definition\DefinitionException;
use Moss\Storage\NormalizeNamespaceTrait;

/**
 * One to Many relation
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Many extends DirectRelation
{
    use NormalizeNamespaceTrait;

    protected $type = 'many';
}
