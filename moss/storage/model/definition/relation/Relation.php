<?php
namespace moss\storage\model\definition\relation;

use moss\storage\model\definition\DefinitionException;
use moss\storage\model\definition\RelationInterface;
use moss\storage\model\ModelInterface;

class Relation implements RelationInterface
{
    private $entity;
    private $type;
    private $container;
    private $keys = array();
    private $local = array();
    private $foreign = array();

    public function __construct($entity, $type, array $keys, $container = null)
    {
        if (!in_array($type, array(ModelInterface::RELATION_ONE, ModelInterface::RELATION_MANY))) {
            throw new DefinitionException(sprintf('Invalid relation type %s in relation for %s', $type, $entity));
        }

        $this->entity = '\\'.ltrim($entity, '\\');
        $this->type = $type;
        $this->container = $container ? $container : substr($this->entity, strrpos($this->entity, '\\') + 1);

        if (empty($keys)) {
            throw new DefinitionException(sprintf('No keys in "%s" relation definition', $this->entity));
        }

        foreach ($keys as $local => $foreign) {
            if (empty($local)) {
                throw new DefinitionException(sprintf('Invalid local field name "%s" for "%s" relation definition', $local, $this->entity));
            }

            if (empty($foreign)) {
                throw new DefinitionException(sprintf('Invalid foreign field name "%s" for "%s" relation definition', $foreign, $this->entity));
            }

            $this->keys[$local] = $foreign;
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
     * Returns container name
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
                if (empty($field)) {
                    throw new DefinitionException(sprintf('Invalid local field name "%s" in "%s" relation', $field, $this->entity));
                }

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
                if (empty($field)) {
                    throw new DefinitionException(sprintf('Invalid foreign field name "%s" in "%s" relation', $field, $this->entity));
                }

                $this->foreign[$field] = $value;
            }
        }

        return $this->foreign;
    }
}
