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
 * Defines unique index for model
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Model
 */
class Unique extends Index
{
    protected $table;
    protected $name;
    protected $type;
    protected $fields = array();

    public function __construct($name, array $fields)
    {
        $this->name = $name;
        $this->type = 'unique';

        if (empty($fields)) {
            throw new DefinitionException(sprintf('No fields in index "%s" definition', $this->name));
        }

        $this->fields = $fields;
    }
}
