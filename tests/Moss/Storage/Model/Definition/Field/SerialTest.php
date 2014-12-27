<?php
namespace Moss\Storage\Model\Definition\Field;

class SerialTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Serial('foo');
        $this->assertEquals($expected, $field->table($table));
    }

    public function tableProvider()
    {
        return [
            [null, null],
            ['foo', 'foo'],
            ['bar', 'bar'],
            ['yada', 'yada'],
        ];
    }

    public function testName()
    {
        $field = new Serial('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Serial('foo');
        $this->assertEquals('serial', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Serial('foo', [], $mapping);
        $this->assertEquals($expected, $field->mapping());
    }

    public function mappingProvider()
    {
        return [
            [null, null],
            ['', null],
            ['foo', 'foo'],
        ];
    }

    public function testNonExistentAttribute()
    {
        $field = new Serial('foo', [], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Serial('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return [
            [['null'], 'null'],
        ];
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected)
    {
        $field = new Serial('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return [
            [['null'], ['null' => true]],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider             forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Serial('foo', [$attribute], 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return [
            ['precision'],
            ['auto_increment'],
            ['default']
        ];
    }
}
