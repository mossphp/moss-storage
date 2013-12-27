<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\definition\DefinitionException;
use moss\storage\model\definition\IndexInterface;
use moss\storage\model\ModelInterface;

class Index implements IndexInterface
{
    protected $container;
    protected $name;
    protected $type;
    protected $fields = array();

    public function __construct($name, array $fields, $type = ModelInterface::INDEX_INDEX)
    {
        if (!in_array($type, array(ModelInterface::INDEX_INDEX, ModelInterface::INDEX_UNIQUE))) {
            throw new DefinitionException(sprintf('Invalid type "%s" for index "%s"', $type, $name));
        }

        $this->name = $name;
        $this->type = $type;

        if (empty($fields)) {
            throw new DefinitionException(sprintf('No fields in index "%s" definition', $this->name));
        }

        $this->fields = $fields;
    }

    /**
     * Returns name of container
     *
     * @param string $container
     *
     * @return string
     */
    public function container($container = null)
    {
        if ($container !== null) {
            $this->container = $container;
        }

        return $this->container;
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
