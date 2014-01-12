<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\ModelInterface;

class UniqueTest extends \PHPUnit_Framework_TestCase
{


    public function testName()
    {
        $index = new Unique('foo', array('foo', 'bar'));
        $this->assertEquals('foo', $index->name());
    }

    public function testType()
    {
        $index = new Unique('foo', array('foo', 'bar'));
        $this->assertEquals(ModelInterface::INDEX_UNIQUE, $index->type());
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testFields($fields)
    {
        $index = new Unique('foo', $fields);
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
        new Unique('foo', array());
    }

    public function testHasField()
    {
        $index = new Unique('foo', array('foo', 'bar'));
        $this->assertTrue($index->hasField('foo'));
        $this->assertTrue($index->hasField('bar'));
    }

    public function testWithoutField()
    {
        $index = new Unique('foo', array('foo', 'bar'));
        $this->assertFalse($index->hasField('yada'));
    }

    public function testIsPrimary()
    {
        $index = new Unique('foo', array('foo', 'bar'));
        $this->assertFalse($index->isPrimary());
    }

    public function testIsUnique()
    {
        $index = new Unique('foo', array('foo', 'bar'));
        $this->assertTrue($index->isUnique());
    }
}
