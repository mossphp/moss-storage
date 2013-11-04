<?php
namespace moss\storage\model\definition;

use moss\storage\model\ModelInterface;

class IndexTest extends \PHPUnit_Framework_TestCase
{


    public function testName()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertEquals('foo', $index->name());
    }

    public function testDefaultType()
    {
        $index = new Index('foo', array('foo', 'bar'));
        $this->assertEquals(ModelInterface::INDEX_INDEX, $index->type());
    }

    public function testType()
    {
        $index = new Index('foo', array('foo', 'bar'), 'unique');
        $this->assertEquals(ModelInterface::INDEX_UNIQUE, $index->type());
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     */
    public function testUnsupportedType()
    {
        new Index('foo', array('foo', 'bar'), 'yadayada');
    }

    public function testFields()
    {
        $index = new Index('foo', array('foo', 'bar'), 'unique');
        $this->assertEquals(array('foo', 'bar'), $index->fields());
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     */
    public function testWithoutFields()
    {
        new Index('foo', array(), 'yadayada');
    }

    public function testHasField()
    {
        $index = new Index('foo', array('foo', 'bar'), 'unique');
        $this->assertTrue($index->hasField('foo'));
        $this->assertTrue($index->hasField('bar'));
    }

    public function testDoesNotHasField()
    {
        $index = new Index('foo', array('foo', 'bar'), 'unique');
        $this->assertFalse($index->hasField('yada'));
    }

    public function testIsPrimary()
    {
        $index = new Index('foo', array('foo', 'bar'), 'primary');
        $this->assertTrue($index->isPrimary());
    }

    public function testIsPrimaryUnique()
    {
        $index = new Index('foo', array('foo', 'bar'), 'primary');
        $this->assertTrue($index->isUnique());
    }

    public function testIsUnique()
    {
        $index = new Index('foo', array('foo', 'bar'), 'unique');
        $this->assertTrue($index->isUnique());
    }
}
