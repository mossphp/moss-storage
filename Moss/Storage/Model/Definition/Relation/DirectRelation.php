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
 * One to one relation
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class DirectRelation extends AbstractRelation
{
    use NormalizeNamespaceTrait;

    /**
     * Constructor
     *
     * @param string      $entity
     * @param array       $keys
     * @param null|string $container
     */
    public function __construct($entity, array $keys, $container = null)
    {
        $this->entity = $this->normalizeNamespace($entity);
        $this->container = $this->containerName($container);

        $this->assertKeys($keys);

        $this->assignKeys($keys, $this->keys);
        $this->in = array_keys($this->keys);
        $this->out = array_values($this->keys);
    }
}
