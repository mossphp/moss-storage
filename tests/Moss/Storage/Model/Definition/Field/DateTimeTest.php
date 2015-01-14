<?php
namespace Moss\Storage\Model\Definition\Field;

class DateTimeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new DateTime('foo');
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
        $field = new DateTime('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new DateTime('foo');
        $this->assertEquals('datetime', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new DateTime('foo', [], $mapping);
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
        $field = new DateTime('foo', [], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new DateTime('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return [
            [['notnull'], 'notnull'],
            [['notnull' => true, 'default' => '2010-10-10 10:10:10'], 'default', '2010-10-10 10:10:10'],
        ];
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected)
    {
        $field = new DateTime('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return [
            [['null'], ['notnull' => false]],
            [['notnull'], ['notnull' => true]],
            [['notnull' => false], ['notnull' => false]],
            [['default' => '2010-10-10 10:10:10'], ['default' => '2010-10-10 10:10:10']],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new DateTime('foo', [$attribute], 'bar');
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
