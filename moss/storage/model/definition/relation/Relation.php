<?php
namespace moss\storage\model\definition\relation;

use moss\storage\model\definition\DefinitionException;
use moss\storage\model\definition\RelationInterface;
use moss\storage\model\ModelInterface;

class Relation implements RelationInterface
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

    private $trough = false;

    public function __construct($entity, $type, array $keys, $container = null, $mediator = null)
    {
        $this->entity = $entity ? ltrim($entity, '\\') : null;

        $this->assertType($type);

        $this->type = $type;
        $this->container = $this->containerName($container);

        if (empty($keys)) {
            throw new DefinitionException(sprintf('No keys in "%s" relation definition', $this->entity));
        }

        $this->trough = in_array($type, array(ModelInterface::RELATION_ONE_TROUGH, ModelInterface::RELATION_MANY_TROUGH));

        if ($this->trough) {
            if (empty($mediator)) {
                throw new DefinitionException(sprintf('Missing mediator name for relation %s', $type, $this->entity));
            }

            $this->mediator = $mediator ? ltrim($mediator, '\\') : $mediator;

            $this->assertTroughKeys($keys);
            $this->assignKeys($keys[0], $this->in);
            $this->assignKeys($keys[1], $this->out);
            $this->keys = array_combine(array_keys($this->in), array_values($this->out));

            return;
        }

        $this->assignKeys($keys, $this->keys);
        $this->in = array_keys($this->keys);
        $this->out = array_values($this->keys);
    }

    private function containerName($container)
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

    private function assignKeys(array $keys, array &$container)
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
            ModelInterface::RELATION_ONE,
            ModelInterface::RELATION_MANY,
            ModelInterface::RELATION_ONE_TROUGH,
            ModelInterface::RELATION_MANY_TROUGH
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
