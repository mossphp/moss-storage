<?php
namespace Moss\Storage\Model\Definition\Field;

use Moss\Storage\Model\ModelInterface;

class FieldTest extends \PHPUnit_Framework_TestCase
{


    public function testName()
    {
        $field = new Field('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testDefaultType()
    {
        $field = new Field('foo');
        $this->assertEquals(ModelInterface::FIELD_STRING, $field->type());
    }

    /**
     * @dataProvider typeProvider
     */
    public function testType($value)
    {
        $field = new Field('foo', $value);
        $this->assertEquals($value, $field->type());
    }

    public function typeProvider()
    {
        return array(
            array(ModelInterface::FIELD_BOOLEAN),
            array(ModelInterface::FIELD_INTEGER),
            array(ModelInterface::FIELD_DECIMAL),
            array(ModelInterface::FIELD_STRING),
            array(ModelInterface::FIELD_DATETIME),
            array(ModelInterface::FIELD_SERIAL)
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     */
    public function testUnsupportedType()
    {
        new Field('foo', false, 'yadayada');
    }

    public function testWithoutMapping()
    {
        $field = new Field('foo', 'string', array('length' => 128), null);
        $this->assertEquals('foo', $field->mapping());
    }

    public function testMapping()
    {
        $field = new Field('foo', 'string', array('length' => 128), 'bar');
        $this->assertEquals('bar', $field->mapping());
    }

    public function testNonExistentAttribute()
    {
        $field = new Field('foo', 'string', array('length' => 128), 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttribute($attribute, $key, $expected)
    {
        $field = new Field('foo', 'integer', $attribute, 'bar');
        $this->assertEquals($expected, $field->attribute($key));
    }

    public function attributeProvider()
    {
        return array(
            array(array(ModelInterface::ATTRIBUTE_UNSIGNED), ModelInterface::ATTRIBUTE_UNSIGNED, true),
            array(array(ModelInterface::ATTRIBUTE_DEFAULT => 1), ModelInterface::ATTRIBUTE_DEFAULT, 1),
            array(array(ModelInterface::ATTRIBUTE_AUTO), ModelInterface::ATTRIBUTE_AUTO, true),
            array(array(ModelInterface::ATTRIBUTE_NULL), ModelInterface::ATTRIBUTE_NULL, true),
            array(array(ModelInterface::ATTRIBUTE_LENGTH => 4), ModelInterface::ATTRIBUTE_LENGTH, 4),
            array(array(ModelInterface::ATTRIBUTE_PRECISION => 2), ModelInterface::ATTRIBUTE_PRECISION, 2),
        );
    }

    /**
     * @dataProvider attributesProvider
     */
    public function testAttributes($attribute, $key, $expected)
    {
        $field = new Field('foo', 'integer', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes($key));
    }

    public function attributesProvider()
    {
        return array(
            array(array(ModelInterface::ATTRIBUTE_UNSIGNED), ModelInterface::ATTRIBUTE_UNSIGNED, array(ModelInterface::ATTRIBUTE_UNSIGNED => true)),
            array(array(ModelInterface::ATTRIBUTE_DEFAULT => 1), ModelInterface::ATTRIBUTE_DEFAULT, array(ModelInterface::ATTRIBUTE_DEFAULT => 1)),
            array(array(ModelInterface::ATTRIBUTE_AUTO), ModelInterface::ATTRIBUTE_AUTO, array(ModelInterface::ATTRIBUTE_AUTO => true)),
            array(array(ModelInterface::ATTRIBUTE_NULL), ModelInterface::ATTRIBUTE_NULL, array(ModelInterface::ATTRIBUTE_NULL => true)),
            array(array(ModelInterface::ATTRIBUTE_LENGTH => 4), ModelInterface::ATTRIBUTE_LENGTH, array(ModelInterface::ATTRIBUTE_LENGTH => 4)),
            array(array(ModelInterface::ATTRIBUTE_PRECISION => 2), ModelInterface::ATTRIBUTE_PRECISION, array(ModelInterface::ATTRIBUTE_PRECISION => 2)),
        );
    }
}
