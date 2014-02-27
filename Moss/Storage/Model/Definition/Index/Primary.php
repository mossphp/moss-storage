<?php
namespace Moss\Storage\Model\Definition\Index;

use Moss\Storage\Model\Definition\DefinitionException;
use Moss\Storage\Model\ModelInterface;

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
