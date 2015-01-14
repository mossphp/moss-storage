<?php
namespace Moss\Storage\Model\Definition\Field;

class BlobTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Blob('foo');
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
        $field = new Blob('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Blob('foo');
        $this->assertEquals('blob', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Blob('foo', [], $mapping);
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
        $field = new Blob('foo', [], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Blob('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return [
            [['notnull' => false], 'notnull', false],
        ];
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected)
    {
        $field = new Blob('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return [
            [['null'], ['notnull' => false]],
            [['notnull'], ['notnull' => true]],
            [['notnull' => false], ['notnull' => false]],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider             forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Blob('foo', [$attribute], 'bar');
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
