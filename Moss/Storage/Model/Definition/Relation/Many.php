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

/**
 * One to Many relation
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Many extends Relation
{
    /**
     * Constructor
     *
     * @param string $entity
     * @param array $keys
     * @param null|string  $container
     *
     * @throws DefinitionException
     */
    public function __construct($entity, array $keys, $container = null)
    {
        $this->entity = $entity ? ltrim($entity, '\\') : null;
        $this->type = 'many';
        $this->container = $this->containerName($container);

        $this->assertKeys($keys);

        $this->assignKeys($keys, $this->keys);
        $this->in = array_keys($this->keys);
        $this->out = array_values($this->keys);
    }
}
