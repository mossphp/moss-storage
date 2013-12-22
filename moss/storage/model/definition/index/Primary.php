<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\definition\DefinitionException;
use moss\storage\model\ModelInterface;

class Primary extends Index
{
    public function __construct(array $fields)
    {
        $this->name = ModelInterface::INDEX_PRIMARY;
        $this->type = ModelInterface::INDEX_PRIMARY;

        if (empty($fields)) {
            throw new DefinitionException('No fields in primary key definition');
        }

        $this->fields = $fields;
    }
}
