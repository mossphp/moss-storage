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
 * Foreign defines foreign key in model
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Foreign extends Index
{
    protected $table;

    /**
     * Constructor
     *
     * @param string $name
     * @param array  $fields
     * @param string $table
     *
     * @throws DefinitionException
     */
    public function __construct($name, array $fields, $table)
    {
        $this->name = $name;
        $this->type = 'foreign';

        $this->assertFields($fields);

        $this->fields = $fields;

        $this->table = $table;
    }

    /**
     * Checks if index uses field (unmapped)
     * Returns true if it does
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasField($field)
    {
        return isset($this->fields[$field]);
    }
}
