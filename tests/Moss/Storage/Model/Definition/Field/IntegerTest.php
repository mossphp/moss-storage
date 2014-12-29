<?php
namespace Moss\Storage\Model\Definition\Field;

class IntegerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Integer('foo');
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
        $field = new Integer('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Integer('foo');
        $this->assertEquals('integer', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Integer('foo', ['length' => 10], $mapping);
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
        $field = new Integer('foo', ['length' => 128], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Integer('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return [
            [['length' => 10], 'length', 10],
            [['notnull'], 'notnull'],
            [['autoincrement'], 'autoincrement', true],
            [['default' => 123], 'default', 123]
        ];
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected)
    {
        $field = new Integer('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return [
            [['length' => 10], ['length' => 10]],
            [['notnull'], ['length' => 11, 'notnull' => true]],
            [['autoincrement'], ['length' => 11, 'autoincrement' => true]],
            [['default' => 123], ['length' => 11, 'notnull' => true, 'default' => 123]]
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider             forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Integer('foo', [$attribute], 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return [
            ['precision'],
        ];
    }
}
