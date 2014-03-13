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
        return array(
            array(null, null),
            array('foo', 'foo'),
            array('bar', 'bar'),
            array('yada', 'yada'),
        );
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
        $field = new DateTime('foo', array(), $mapping);
        $this->assertEquals($expected, $field->mapping());
    }

    public function mappingProvider()
    {
        return array(
            array(null, 'foo'),
            array('', 'foo'),
            array('foo', 'foo'),
            array('bar', 'bar'),
            array('yada', 'yada'),
        );
    }

    public function testNonExistentAttribute()
    {
        $field = new DateTime('foo', array(), 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new DateTime('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttributes($attribute, $key, $value = true)
    {
        $field = new DateTime('foo', $attribute, 'bar');
        $this->assertEquals(array($key => $value), $field->attributes($key));
    }

    public function attributeProvider()
    {
        return array(
            array(array('null'), 'null'),
            array(array('default' => '2010-10-10 10:10:10'), 'default', '2010-10-10 10:10:10'),
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new DateTime('foo', array($attribute), 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return array(
            array('length'),
            array('precision'),
            array('unsigned'),
            array('auto_increment'),
            array('comment')
        );
    }
}