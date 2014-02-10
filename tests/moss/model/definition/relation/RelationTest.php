<?php
namespace moss\storage\model\definition\relation;

use moss\storage\model\ModelInterface;

class RelationTest extends \PHPUnit_Framework_TestCase
{

    public function testDefaultName()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'));
        $this->assertEquals('Foo', $relation->name());
    }

    public function testForcedName()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'), 'Foobar', array(), array());
        $this->assertEquals('Foobar', $relation->name());
    }

    public function testEntity()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'));
        $this->assertEquals('\Foo', $relation->entity());
    }

    public function testType()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'));
        $this->assertEquals(ModelInterface::RELATION_ONE, $relation->type());
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     */
    public function testUnsupportedType()
    {
        new Relation('\Foo', 'yada', array('foo' => 'bar'));
    }

    public function testDefaultTable()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'));
        $this->assertEquals('Foo', $relation->container());
    }

    public function testForcedTable()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'), 'Foobar');
        $this->assertEquals('Foobar', $relation->container());
    }

    public function testKeys()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'));
        $this->assertEquals(array('foo' => 'bar'), $relation->keys());
    }

    /**
     * @expectedException \moss\storage\model\definition\DefinitionException
     */
    public function testWithoutKeys()
    {
        $relation = new Relation('\Foo', 'one', array());
        $this->assertEquals(array('foo' => 'bar'), $relation->keys());
    }

    public function testLocalValues()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'), 'Foobar');
        $relation->localValues(array('yada' => 'yada'));
        $this->assertEquals(array('yada' => 'yada'), $relation->localValues());
    }

    public function testReferencedValues()
    {
        $relation = new Relation('\Foo', 'one', array('foo' => 'bar'), 'Foobar');
        $relation->foreignValues(array('yada' => 'yada'));
        
        $this->assertEquals(array('yada' => 'yada'), $relation->foreignValues());
    }
}
