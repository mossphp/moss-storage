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
use Moss\Storage\Model\Definition\RelationInterface;
use Moss\Storage\Model\ModelInterface;

/**
 * Relation definition describing relationship between entities in model
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Model
 */
abstract class Relation implements RelationInterface
{
    protected $entity;

    protected $mediator;

    protected $type;
    protected $container;

    protected $keys = array();
    protected $in = array();
    protected $out = array();

    protected $local = array();
    protected $foreign = array();

    protected function containerName($container)
    {
        if ($container) {
            return $container;
        }

        $pos = strrpos($this->entity, '\\');
        if ($pos === false) {
            return $this->entity;
        }

        return substr($this->entity, strrpos($this->entity, '\\') + 1);
    }

    protected function assignKeys(array $keys, array &$container)
    {
        foreach ($keys as $local => $foreign) {
            $this->assertField($local);
            $this->assertField($foreign);

            $container[$local] = $foreign;
        }
    }

    protected function assertType($type)
    {
        $types = array(
            'one',
            'many',
            'oneTrough',
            'manyTrough'
        );

        if (!in_array($type, $types)) {
            throw new DefinitionException(sprintf('Invalid relation type %s in relation for %s', $type, $this->entity));
        }
    }

    protected function assertTroughKeys($keys)
    {
        if (!isset($keys[0], $keys[1])) {
            throw new DefinitionException(sprintf('Invalid keys for relation "%s", must have two arrays, got %u', $this->entity, count($keys)));
        }

        if (count($keys[0]) !== count($keys[1])) {
            throw new DefinitionException(sprintf('Both key arrays for relation "%s", must same number of elements', $this->entity));
        }
    }

    protected function assertField($field)
    {
        if (!is_string($field)) {
            throw new DefinitionException(sprintf('Field name for relation "%s.%s" must be string, %s given', $this->entity, $field, gettype($field)));
        }

        if (empty($field)) {
            throw new DefinitionException(sprintf('Field name for "%s.%s" can not be empty', $this->entity, $field));
        }

        if (is_numeric($field)) {
            throw new DefinitionException(sprintf('Field name for "%s.%s" can not be numeric', $this->entity, $field));
        }
    }

    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name()
    {
        return $this->container();
    }

    /**
     * Returns relation mediating instance
     *
     * @return string
     */
    public function mediator()
    {
        return $this->mediator;
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
     * Returns relation entity class name
     *
     * @return string
     */
    public function entity()
    {
        return $this->entity;
    }

    /**
     * Returns table name
     *
     * @return string
     */
    public function container()
    {
        return $this->container;
    }

    /**
     * Returns associative array containing local key - foreign key pairs
     *
     * @return array
     */
    public function keys()
    {
        return $this->keys;
    }

    /**
     * Returns array containing local keys
     *
     * @return array
     */
    public function localKeys()
    {
        return $this->in;
    }

    /**
     * Returns array containing foreign keys
     *
     * @return array
     */
    public function foreignKeys()
    {
        return $this->out;
    }

    /**
     * Returns associative array containing local key - value pairs
     *
     * @param array $localValues ;
     *
     * @return array
     * @throws DefinitionException
     */
    public function localValues($localValues = array())
    {
        if ($localValues !== array()) {
            foreach ($localValues as $field => $value) {
                $this->assertField($field);

                $this->local[$field] = $value;
            }
        }

        return $this->local;
    }

    /**
     * Returns associative array containing foreign key - value pairs
     *
     * @param array $foreignValues ;
     *
     * @return array
     * @throws DefinitionException
     */
    public function foreignValues($foreignValues = array())
    {
        if ($foreignValues !== array()) {
            foreach ($foreignValues as $field => $value) {
                $this->assertField($field);

                $this->foreign[$field] = $value;
            }
        }

        return $this->foreign;
    }
}
