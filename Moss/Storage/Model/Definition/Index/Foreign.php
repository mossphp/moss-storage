<?php
namespace Moss\Storage\Model\Definition\Index;

use Moss\Storage\Model\Definition\DefinitionException;
use Moss\Storage\Model\ModelInterface;

class Foreign extends Index
{
    protected $table;

    public function __construct($name, array $fields, $table)
    {
        $this->name = $name;
        $this->type = ModelInterface::INDEX_FOREIGN;

        if (empty($fields)) {
            throw new DefinitionException('No fields in foreign key definition');
        }

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
