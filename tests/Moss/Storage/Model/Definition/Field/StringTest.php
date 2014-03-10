<?php
namespace Moss\Storage\Model\Definition\Field;

class StringTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider tableProvider
     */
    public function testTable($table, $expected)
    {
        $field = new String('foo');
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
        $field = new String('foo');
        $this->assertEquals('foo', $field->name());
    }

    public function testType()
    {
        $field = new String('foo');
        $this->assertEquals('string', $field->type());
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapping($mapping, $expected)
    {
        $field = new String('foo', array('length' => 10), $mapping);
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
        $field = new String('foo', array('length' => 128), 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new String('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttributes($attribute, $key, $value = true)
    {
        $field = new String('foo', $attribute, 'bar');
        $this->assertEquals(array($key => $value), $field->attributes($key));
    }

    public function attributeProvider()
    {
        return array(
            array(array('length' => 4), 'length', 4),
            array(array('null'), 'null'),
            array(array('comment' => 'foo'), 'comment', 'foo'),
            array(array('default' => 1), 'default', 1),
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new String('foo', array($attribute), 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return array(
            array('precision'),
            array('unsigned'),
            array('auto_increment')
        );
    }
}
