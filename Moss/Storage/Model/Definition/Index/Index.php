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
use Moss\Storage\Model\Definition\IndexInterface;

/**
 * Index definition describing ordinary index
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Model
 */
class Index implements IndexInterface
{
    protected $table;
    protected $name;
    protected $type;
    protected $fields = array();

    public function __construct($name, array $fields)
    {
        $this->name = $name;
        $this->type = 'index';

        if (empty($fields)) {
            throw new DefinitionException(sprintf('No fields in index "%s" definition', $this->name));
        }

        $this->fields = $fields;
    }

    /**
     * Returns name of table
     *
     * @param string $table
     *
     * @return string
     */
    public function table($table = null)
    {
        if ($table !== null) {
            $this->table = $table;
        }

        return $this->table;
    }

    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns relation type
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns array containing field names (unmapped) that are included in index
     *
     * @return array
     */
    public function fields()
    {
        return $this->fields;
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
        return in_array($field, $this->fields);
    }

    /**
     * Returns true if index is primary index
     *
     * @return bool
     */
    public function isPrimary()
    {
        return $this->type == 'primary';
    }

    /**
     * Returns true if index is unique
     *
     * @return bool
     */
    public function isUnique()
    {
        return $this->type == 'unique' || $this->type == 'primary';
    }
}
