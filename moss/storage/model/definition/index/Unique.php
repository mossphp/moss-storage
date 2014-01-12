<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\definition\DefinitionException;
use moss\storage\model\ModelInterface;

class Unique extends Index
{
    protected $table;
    protected $name;
    protected $type;
    protected $fields = array();

    public function __construct($name, array $fields)
    {
        $this->name = $name;
        $this->type = ModelInterface::INDEX_UNIQUE;

        if (empty($fields)) {
            throw new DefinitionException(sprintf('No fields in index "%s" definition', $this->name));
        }

        $this->fields = $fields;
    }
}
