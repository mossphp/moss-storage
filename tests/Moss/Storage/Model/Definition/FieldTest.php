<?php
namespace Moss\Storage\Model\Definition;


class FieldTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new Field('foo');
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
        $field = new Field('foo');
        $this->assertEquals('foo', $field->name());
    }

    /**
     * @dataProvider typeProvider
     */
    public function testType($type)
    {
        $field = new Field('foo', $type);
        $this->assertEquals($type, $field->type());
    }

    public function typeProvider()
    {
        return [
            ['smallint'],
            ['integer'],
            ['bigint'],
            ['decimal'],
            ['float'],
            ['string'],
            ['text'],
            ['guid'],
            ['binary'],
            ['blob'],
            ['boolean'],
            ['date'],
            ['datetime'],
            ['datetimetz'],
            ['time'],
            ['array'],
            ['simple_array'],
            ['json_array'],
            ['object']
        ];
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new Field('foo', 'string', [], $mapping);
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

    /**
     * @dataProvider mappedNameProvider
     */
    public function testMappedName($mapping, $expected)
    {
        $field = new Field('foo', 'string', [], $mapping);
        $this->assertEquals($expected, $field->mappedName());
    }

    public function mappedNameProvider()
    {
        return [
            [null, 'foo'],
            ['', 'foo'],
            ['bar', 'bar'],
        ];
    }

    public function testNonExistentAttribute()
    {
        $field = new Field('foo', 'string', [], 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttribute($attribute, $key, $value)
    {
        $field = new Field('foo', 'string', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
        $this->assertEquals([$key => $value], $field->attributes());
    }

    public function attributeProvider()
    {
        return [
            [['null'], 'notnull', false],
            [['notnull'], 'notnull', true],
            [['default' => 'foo'], 'default', 'foo'],
            [['autoincrement'], 'autoincrement', true],
            [['length' => 10], 'length', 10],
            [['precision' => 10], 'precision', 10],
            [['scale' => 4], 'scale', 4],
            [['fixed'], 'fixed', true]
        ];
    }
}
