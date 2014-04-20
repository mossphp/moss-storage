<?php
namespace Moss\Storage\Model\Definition\Relation;

class ManyTroughTest extends \PHPUnit_Framework_TestCase
{
    public function testType()
    {
        $relation = new ManyTrough('\Foo', array('id' => 'in'), array('out' => 'id'), 'mediator');
        $this->assertEquals('manyTrough', $relation->type());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultName($name, $expected)
    {
        $relation = new ManyTrough($name, array('id' => 'in'), array('out' => 'id'), 'mediator');
        $this->assertEquals($expected, $relation->name());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultContainer($name, $expected)
    {
        $relation = new ManyTrough($name, array('id' => 'in'), array('out' => 'id'), 'mediator');
        $this->assertEquals($expected, $relation->container());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testForcedContainer($name)
    {
        $relation = new ManyTrough($name, array('id' => 'in'), array('out' => 'id'), 'mediator', 'Foobar');
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
        $relation = new ManyTrough('\Foo', array('id' => 'in'), array('out' => 'id'), 'mediator', 'Foobar');
        $this->assertEquals('Foobar', $relation->name());
    }

    public function testEntity()
    {
        $relation = new ManyTrough('\Foo', array('id' => 'in'), array('out' => 'id'), 'mediator');
        $this->assertEquals('Foo', $relation->entity());
    }

    /**
     * @dataProvider keyProvider
     */
    public function testKeys($keys, $expectedKeys, $expectedLocal, $expectedForeign)
    {
        $relation = new ManyTrough('\Foo', $keys[0], $keys[1], 'foo', '\Bar');
        $this->assertEquals($expectedKeys, $relation->keys());
        $this->assertEquals($expectedLocal, $relation->localKeys());
        $this->assertEquals($expectedForeign, $relation->foreignKeys());
    }

    public function keyProvider()
    {
        return array(
            array(
                array(array('id' => 'in'), array('out' => 'id')),
                array('id' => 'id'),
                array('id' => 'in'),
                array('out' => 'id')
            ),
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Invalid keys for relation "Foo", must be two arrays with key-value pairs
     */
    public function testWithoutInKeys()
    {
        new ManyTrough('\Foo', array(), array('out' => 'id'), 'mediator');
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Invalid keys for relation "Foo", must be two arrays with key-value pairs
     */
    public function testWithoutOutKeys()
    {
        new ManyTrough('\Foo', array('id' => 'in'), array(), 'mediator');
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Both key arrays for relation "Foo", must have the same number of elements
     */
    public function testKeysWithoutSameNumberOfElements()
    {
        new ManyTrough('\Foo', array('id' => 'in'), array('foo' => 'foo', 'bar' => 'bar'), 'mediator');
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Invalid field name for relation
     * @dataProvider      invalidKeysProvider
     */
    public function testWithInvalidKeys($keys)
    {
        new ManyTrough('\Foo', $keys, $keys, 'mediator');
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
        $relation = new ManyTrough('\Foo', array('id' => 'in'), array('out' => 'id'), 'mediator', 'Foobar');
        $relation->localValues(array('yada' => 'yada'));
        $this->assertEquals(array('yada' => 'yada'), $relation->localValues());
    }

    public function testReferencedValues()
    {
        $relation = new ManyTrough('\Foo', array('id' => 'in'), array('out' => 'id'), 'mediator', 'Foobar');
        $relation->foreignValues(array('yada' => 'yada'));

        $this->assertEquals(array('yada' => 'yada'), $relation->foreignValues());
    }
}
