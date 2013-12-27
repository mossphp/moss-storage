<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\definition\DefinitionException;
use moss\storage\model\definition\ForeignInterface;
use moss\storage\model\ModelInterface;

class Foreign extends Index implements ForeignInterface
{
    protected $container;

    public function __construct($name, array $fields, $container)
    {
        $this->name = $name;
        $this->type = ModelInterface::INDEX_FOREIGN;

        if (empty($fields)) {
            throw new DefinitionException('No fields in foreign key definition');
        }

        $this->fields = $fields;

        $this->container = $container;
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
