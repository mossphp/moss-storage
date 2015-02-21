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

use Moss\Storage\GetTypeTrait;
use Moss\Storage\Model\Definition\DefinitionException;
use Moss\Storage\Model\Definition\RelationInterface;

/**
 * Relation definition describing relationship between entities in model
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
abstract class Relation implements RelationInterface
{
    use GetTypeTrait;

    protected $entity;

    protected $mediator;

    protected $type;
    protected $container;

    protected $keys = [];
    protected $in = [];
    protected $out = [];

    /**
     * Returns container name or builds it from namespaced class name if passed name is empty string
     *
     * @param null|string $container
     *
     * @return string
     */
    protected function containerName($container = null)
    {
        if ($container !== null) {
            return $container;
        }

        $pos = strrpos($this->entity, '\\');
        if ($pos === false) {
            return $this->entity;
        }

        return substr($this->entity, strrpos($this->entity, '\\') + 1);
    }

    /**
     * Assigns key pairs to passed container
     *
     * @param array $keys
     * @param array $container
     */
    protected function assignKeys(array $keys, array &$container)
    {
        foreach ($keys as $local => $foreign) {
            $this->assertField($local);
            $this->assertField($foreign);

            $container[$local] = $foreign;
        }
    }

    /**
     * Asserts keys (non empty array)
     *
     * @param $keys
     *
     * @throws DefinitionException
     */
    protected function assertKeys($keys)
    {
        if (empty($keys)) {
            throw new DefinitionException(sprintf('No keys in "%s" relation definition', $this->entity));
        }

    }

    /**
     * Asserts trough keys, must be same number in both arrays
     *
     * @param array $inKeys
     * @param array $outKeys
     *
     * @throws DefinitionException
     */
    protected function assertTroughKeys($inKeys, $outKeys)
    {
        if (empty($inKeys) || empty($outKeys)) {
            throw new DefinitionException(sprintf('Invalid keys for relation "%s", must be two arrays with key-value pairs', $this->entity, count($inKeys)));
        }

        if (count($inKeys) !== count($outKeys)) {
            throw new DefinitionException(sprintf('Both key arrays for relation "%s", must have the same number of elements', $this->entity));
        }
    }

    /**
     * Asserts field name
     *
     * @param string $field
     *
     * @throws DefinitionException
     */
    protected function assertField($field)
    {
        if (empty($field)) {
            throw new DefinitionException(sprintf('Invalid field name for relation "%s.%s" can not be empty', $this->entity, $field));
        }

        if (is_numeric($field)) {
            throw new DefinitionException(sprintf('Invalid field name for relation "%s.%s" can not be numeric', $this->entity, $field));
        }

        if (!is_string($field)) {
            throw new DefinitionException(sprintf('Invalid field name for relation "%s.%s" must be string, %s given', $this->entity, $field, $this->getType($field)));
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
}
