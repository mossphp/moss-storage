<?php
namespace Moss\Storage\Model\Definition\Field;

class BooleanTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Boolean('foo');
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
        $field = new Boolean('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Boolean('foo');
        $this->assertEquals('boolean', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Boolean('foo', [], $mapping);
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
        $field = new Boolean('foo', [], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Boolean('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return [
            [['null'], 'null'],
            [['default' => 0], 'default', 0],
        ];
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected = [])
    {
        $field = new Boolean('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return [
            [['null'], ['null' => true]],
            [['default' => 0], ['null' => true, 'default' => 0]],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Boolean('foo', [$attribute], 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return [
            ['length'],
            ['precision'],
            ['auto_increment'],
        ];
    }
}
