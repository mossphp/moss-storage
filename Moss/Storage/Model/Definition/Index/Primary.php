<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model\Definition\Index;

use Moss\Storage\Model\Definition\DefinitionException;

/**
 * Defines primary key
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Model
 */
class Primary extends Index
{
    public function __construct(array $fields)
    {
        $this->name = 'primary';
        $this->type = 'primary';

        if (empty($fields)) {
            throw new DefinitionException('No fields in primary key definition');
        }

        $this->fields = $fields;
    }
}
