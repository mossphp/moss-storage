<?php
namespace Moss\Storage\Model\Definition\Field;

use Moss\Storage\Model\Definition\DefinitionException;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelInterface;

class Field implements FieldInterface
{
    protected $table;
    protected $name;
    protected $type;
    protected $mapping;
    protected $attributes;

    public function __construct($field, $type = ModelInterface::FIELD_STRING, $attributes = array(), $mapping = null)
    {
        if (!in_array($type, array(ModelInterface::FIELD_BOOLEAN, ModelInterface::FIELD_INTEGER, ModelInterface::FIELD_DECIMAL, ModelInterface::FIELD_STRING, ModelInterface::FIELD_DATETIME, ModelInterface::FIELD_SERIAL))) {
            throw new DefinitionException(sprintf('Invalid type "%s" for field "%s"', $type, $field));
        }

        foreach ($attributes as $key => $value) {
            if (!is_numeric($key)) {
                continue;
            }

            unset($attributes[$key]);
            $attributes[$value] = true;
        }

        $this->name = $field;
        $this->type = $type;
        $this->mapping = $mapping;
        $this->attributes = $attributes;
    }

    public function table($table = null)
    {
        if ($table !== null) {
            $this->table = $table;
        }

        return $this->table;
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
     * Returns field table mapping or null when no mapping
     *
     * @return null|string
     */
    public function mapping()
    {
        return $this->mapping ? $this->mapping : $this->name;
    }

    /**
     * Returns attribute value or null if not set
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function attribute($attribute)
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            return null;
        }

        return $this->attributes[$attribute];
    }

    /**
     * Returns array containing field attributes
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }
}
