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

use Moss\Storage\NormalizeClassNameTrait;

/**
 * One to one relation
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class One extends Relation
{
    use NormalizeClassNameTrait;

    /**
     * Constructor
     *
     * @param string      $entity
     * @param array       $keys
     * @param null|string $container
     */
    public function __construct($entity, array $keys, $container = null)
    {
        $this->entity = $this->normalizeClassName($entity);
        $this->type = 'one';
        $this->container = $this->containerName($container);

        $this->assertKeys($keys);

        $this->assignKeys($keys, $this->keys);
        $this->in = array_keys($this->keys);
        $this->out = array_values($this->keys);
    }
}
