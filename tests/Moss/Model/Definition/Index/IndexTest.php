<?php
namespace Moss\Storage\Model\Definition\Index;

use Moss\Storage\Model\ModelInterface;

class IndexTest extends \PHPUnit_Framework_TestCase
{


    public function testName()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertEquals('foo', $index->name());
    }

    public function testType()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertEquals(ModelInterface::INDEX_INDEX, $index->type());
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
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
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
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertFalse($index->isPrimary());
    }

    public function testIsUnique()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertFalse($index->isUnique());
    }
}
