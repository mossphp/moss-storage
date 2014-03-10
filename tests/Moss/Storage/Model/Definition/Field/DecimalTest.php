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
        return array(
            array(null, null),
            array('foo', 'foo'),
            array('bar', 'bar'),
            array('yada', 'yada'),
        );
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
        $field = new Decimal('foo', array('length' => 10), $mapping);
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
        $field = new Decimal('foo', array('length' => 10, 'precision' => 4), 'bar');
        $this->assertNull($field->attribute('NonExistentAttribute'));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttribute($attribute, $key, $value = true)
    {
        $field = new Decimal('foo', $attribute, 'bar');
        $this->assertEquals($value, $field->attribute($key));
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testAttributes($attribute, $key, $value = true)
    {
        $field = new Decimal('foo', $attribute, 'bar');
        $this->assertEquals(array($key => $value), $field->attributes($key));
    }

    public function attributeProvider()
    {
        return array(
            array(array('length' => 10), 'length', 10),
            array(array('precision' => 4), 'precision', 4),
            array(array('null'), 'null'),
            array(array('unsigned'), 'unsigned'),
            array(array('default' => 12.34), 'default', 12.34),
            array(array('comment'), 'comment')
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Forbidden attribute
     * @dataProvider forbiddenAttributeProvider
     */
    public function testForbiddenAttributes($attribute)
    {
        new Decimal('foo', array($attribute), 'bar');
    }

    public function forbiddenAttributeProvider()
    {
        return array(
            array('auto_increment'),
        );
    }
}
