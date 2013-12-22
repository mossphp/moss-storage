<?php
namespace moss\storage\model\definition\index;

use moss\storage\model\ModelInterface;

class ForeignTest extends \PHPUnit_Framework_TestCase
{


    public function testName()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertEquals('foo', $index->name());
    }

    public function testType()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertEquals(ModelInterface::INDEX_FOREIGN, $index->type());
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testFields($local)
    {
        $index = new Foreign('foo', $local, 'table');
        $this->assertEquals($local, $index->fields());
    }

    public function fieldsProvider() {
        return array(
            array(array('foo' => 'tfoo')),
            array(array('foo' => 'tfoo', 'bar' => 'tbar')),
            array(array('foo' => 'tfoo', 'bar' => 'tbar', 'yada' => 'tyada'))
        );
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     * @expectedExceptionMessage No fields in
     */
    public function testWithoutAnyFields()
    {
        new Foreign('foo', array(), 'table');
    }


    public function testHasField()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertTrue($index->hasField('foo'));
        $this->assertTrue($index->hasField('bar'));
    }

    public function testWithoutField()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertFalse($index->hasField('yada'));
    }

    public function testIsPrimary()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertFalse($index->isPrimary());
    }

    public function testIsNotUnique()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertFalse($index->isUnique());
    }

    public function testIsUnique()
    {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertFalse($index->isUnique());
    }

    public function testForeignContainer() {
        $index = new Foreign('foo', array('foo' => 'tfoo', 'bar' => 'tbar'), 'table');
        $this->assertEquals('table', $index->container());
    }
}
