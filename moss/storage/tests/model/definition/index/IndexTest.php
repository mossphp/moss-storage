<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\ModelInterface;

class IndexTest extends \PHPUnit_Framework_TestCase
{


    public function testName()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertEquals('foo', $index->name());
    }

    /**
     * @dataProvider typeProvider
     */
    public function testType($type)
    {
        $index = new Index('foo', array('foo', 'bar'), $type);
        $this->assertEquals($type, $index->type());
    }

    public function typeProvider() {
        return array(
            array(ModelInterface::INDEX_INDEX),
            array(ModelInterface::INDEX_UNIQUE)
        );
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     * @expectedExceptionMessage Invalid type
     */
    public function testInvalidType()
    {
        new Index('foo', array('foo', 'bar'), 'yadayada');
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testFields($fields)
    {
        $index = new Index('foo', $fields);
        $this->assertEquals($fields, $index->fields());
    }

    public function fieldsProvider() {
        return array(
            array(array('foo')),
            array(array('foo', 'bar')),
            array(array('foo', 'bar', 'yada'))
        );
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     * @expectedExceptionMessage No fields in
     */
    public function testWithoutAnyFields()
    {
        new Index('foo', array());
    }

    public function testHasField()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertTrue($index->hasField('foo'));
        $this->assertTrue($index->hasField('bar'));
    }

    public function testWithoutField()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertFalse($index->hasField('yada'));
    }

    public function testIsPrimary()
    {
        $index = new Index('foo', array('foo', 'bar'), 'index');
        $this->assertFalse($index->isPrimary());
    }

    public function testIsNotUnique()
    {
        $index = new Index('foo', array('foo', 'bar'), 'index');
        $this->assertFalse($index->isUnique());
    }

    public function testIsUnique()
    {
        $index = new Index('foo', array('foo', 'bar'), 'unique');
        $this->assertTrue($index->isUnique());
    }
}
