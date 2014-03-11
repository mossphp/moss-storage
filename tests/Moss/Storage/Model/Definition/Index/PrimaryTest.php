<?php
namespace Moss\Storage\Model\Definition\Index;

use Moss\Storage\Model\ModelInterface;

class PrimaryTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $index = new Primary(array('foo', 'bar'));
        $this->assertEquals('primary', $index->name());
    }

    public function testType()
    {
        $index = new Primary(array('foo', 'bar'));
        $this->assertEquals('primary', $index->type());
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testFields($fields)
    {
        $index = new Primary($fields);
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
        new Primary(array());
    }

    public function testHasField()
    {
        $index = new Primary(array('foo', 'bar'));
        $this->assertTrue($index->hasField('foo'));
        $this->assertTrue($index->hasField('bar'));
    }

    public function testWithoutField()
    {
        $index = new Primary(array('foo', 'bar'));
        $this->assertFalse($index->hasField('yada'));
    }

    public function testIsPrimary()
    {
        $index = new Primary(array('foo', 'bar'));
        $this->assertTrue($index->isPrimary());
    }

    public function testIsUnique()
    {
        $index = new Primary(array('foo', 'bar'));
        $this->assertTrue($index->isUnique());
    }
}
