<?php
namespace Moss\Storage\Model\Definition\Relation;

class ManyTest extends \PHPUnit_Framework_TestCase
{
    public function testType() {
        $relation = new Many('\Foo', array('foo' => 'bar'));
        $this->assertEquals('many', $relation->type());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultName($name, $expected)
    {
        $relation = new Many($name, array('foo' => 'bar'));
        $this->assertEquals($expected, $relation->name());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultContainer($name, $expected)
    {
        $relation = new Many($name, array('foo' => 'bar'));
        $this->assertEquals($expected, $relation->container());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testForcedContainer($name)
    {
        $relation = new Many($name, array('foo' => 'bar'), 'Foobar');
        $this->assertEquals('Foobar', $relation->container());
    }

    public function defaultNameProvider()
    {
        return array(
            array('Foo', 'Foo'),
            array('\Foo', 'Foo'),
            array('\\Foo', 'Foo'),
            array('\\\\Foo', 'Foo'),
            array('\\Foo\\Bar', 'Bar'),
        );
    }

    public function testForcedName()
    {
        $relation = new Many('\Foo', array('foo' => 'bar'), 'Foobar');
        $this->assertEquals('Foobar', $relation->name());
    }

    public function testEntity()
    {
        $relation = new Many('\Foo', array('foo' => 'bar'));
        $this->assertEquals('Foo', $relation->entity());
    }

    /**
     * @dataProvider keyProvider
     */
    public function testKeys($keys, $expectedKeys, $expectedLocal, $expectedForeign)
    {
        $relation = new Many('\Foo', $keys, 'foo', '\Bar');
        $this->assertEquals($expectedKeys, $relation->keys());
        $this->assertEquals($expectedLocal, $relation->localKeys());
        $this->assertEquals($expectedForeign, $relation->foreignKeys());
    }

    public function keyProvider()
    {
        return array(
            array(
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                array('foo'),
                array('bar')
            ),
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     */
    public function testWithoutKeys()
    {
        new Many('\Foo', array());
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @dataProvider invalidKeysProvider
     */
    public function testWithInvalidKeys($keys)
    {
        new Many('\Foo', $keys);
    }

    public function invalidKeysProvider()
    {
        return array(
            array(array('' => 1)),
            array(array('foo' => 1)),
            array(array(1 => null)),
            array(array(1 => 'foo')),
            array(array(1 => new \stdClass())),
            array(array(1 => array(1, 2))),
        );
    }

    public function testLocalValues()
    {
        $relation = new Many('\Foo', array('foo' => 'bar'), 'Foobar');
        $relation->localValues(array('yada' => 'yada'));
        $this->assertEquals(array('yada' => 'yada'), $relation->localValues());
    }

    public function testReferencedValues()
    {
        $relation = new Many('\Foo', array('foo' => 'bar'), 'Foobar');
        $relation->foreignValues(array('yada' => 'yada'));

        $this->assertEquals(array('yada' => 'yada'), $relation->foreignValues());
    }
}
