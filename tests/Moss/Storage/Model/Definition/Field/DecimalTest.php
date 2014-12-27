<?php
namespace Moss\Storage\Model\Definition\Field;

class DecimalTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Decimal('foo');
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
        $field = new Decimal('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new Decimal('foo');
        $this->assertEquals('decimal', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Decimal('foo', ['length' => 10], $mapping);
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
        $field = new Decimal('foo', ['length' => 10, 'precision' => 4], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Decimal('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    public function attributeValueProvider()
    {
        return [
            [['length' => 10], 'length', 10],
            [['precision' => 2], 'precision', 2],
            [['null'], 'null', true],
            [['default' => 12.34], 'default', 12.34]
        ];
    }

    /**
     * @dataProvider attributeArrayProvider
     */
    public function testAttributes($attribute, $expected = [])
    {
        $field = new Decimal('foo', $attribute, 'bar');
        $this->assertEquals($expected, $field->attributes());
    }

    public function attributeArrayProvider()
    {
        return [
            [['length' => 10], ['length' => 10, 'precision' => 4]],
            [['precision' => 2], ['length' => 11, 'precision' => 2]],
            [['null'], ['length' => 11, 'precision' => 4, 'null' => true]],
            [['default' => 12.34], ['length' => 11, 'precision' => 4, 'null' => true, 'default' => 12.34]]
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider             forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Decimal('foo', [$attribute], 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return [
            ['auto_increment'],
        ];
    }
}
