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
 * One to one trough mediator table (with pivot table)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class OneTrough extends Relation
{
    use NormalizeClassNameTrait;

    /**
     * @param string      $entity
     * @param array       $in
     * @param array       $out
     * @param null|string $mediator
     * @param null|string $container
     */
    public function __construct($entity, array $in, array $out, $mediator, $container = null)
    {
        $this->entity = $this->normalizeClassName($entity);
        $this->type = 'oneTrough';
        $this->container = $this->containerName($container);

        $this->mediator = $this->normalizeClassName($entity);

        $this->assertTroughKeys($in, $out);
        $this->assignKeys($in, $this->in);
        $this->assignKeys($out, $this->out);
        $this->keys = array_combine(array_keys($this->in), array_values($this->out));
    }
}
