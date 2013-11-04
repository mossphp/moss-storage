<?php
namespace moss\storage\model\definition;

use moss\storage\model\ModelInterface;

class Relation implements RelationInterface
{
    private $entity;
    private $type;
    private $container;
    private $keys = array();
    private $local = array();
    private $referenced = array();

    public function __construct($entity, $type, array $keys, $container = null)
    {
        if (!in_array($type, array(ModelInterface::RELATION_ONE, ModelInterface::RELATION_MANY))) {
            throw new DefinitionException(sprintf('Invalid relation type %s in relation for %s', $type, $entity));
        }

        $this->entity = $entity;
        $this->type = $type;
        $this->container = $container ? $container : substr($entity, strrpos($entity, '\\') + 1);

        if (empty($keys)) {
            throw new DefinitionException(sprintf('No keys in "%s" relation definition', $this->entity));
        }

        foreach ($keys as $local => $reference) {
            if (empty($local)) {
                throw new DefinitionException(sprintf('Invalid local field name "%s" for "%s" relation definition', $local, $this->entity));
            }

            if (empty($reference)) {
                throw new DefinitionException(sprintf('Invalid reference field name "%s" for "%s" relation definition', $reference, $this->entity));
            }

            $this->keys[$local] = $reference;
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
     * Returns associative array containing local key - referenced key pairs
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
     * Returns associative array containing referenced key - value pairs
     *
     * @param array $referencedValues ;
     *
     * @return array
     * @throws DefinitionException
     */
    public function referencedValues($referencedValues = array())
    {
        if ($referencedValues !== array()) {
            foreach ($referencedValues as $field => $value) {
                if (empty($field)) {
                    throw new DefinitionException(sprintf('Invalid reference field name "%s" in "%s" relation', $field, $this->entity));
                }

                $this->referenced[$field] = $value;
            }
        }

        return $this->referenced;
    }
}
