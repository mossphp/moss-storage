<?php
namespace Moss\Storage\Model\Definition\Relation;

use Moss\Storage\Model\ModelInterface;

class RelationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultName($name, $expected)
    {
        $relation = new Relation($name, ModelInterface::RELATION_ONE, array('foo' => 'bar'));
        $this->assertEquals($expected, $relation->name());
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
        $relation = new Relation('\Foo', ModelInterface::RELATION_ONE, array('foo' => 'bar'), 'Foobar');
        $this->assertEquals('Foobar', $relation->name());
    }

    public function testEntity()
    {
        $relation = new Relation('\Foo', ModelInterface::RELATION_ONE, array('foo' => 'bar'));
        $this->assertEquals('Foo', $relation->entity());
    }

    /**
     * @dataProvider typeProvider
     */
    public function testType($type, $keys)
    {
        $relation = new Relation('\Foo', $type, $keys, 'foo', '\Bar');
        $this->assertEquals($type, $relation->type());
    }

    public function typeProvider()
    {
        return array(
            array(
                ModelInterface::RELATION_ONE,
                array('foo' => 'bar'),
            ),
            array(
                ModelInterface::RELATION_MANY,
                array('foo' => 'bar'),
            ),
            array(
                ModelInterface::RELATION_ONE_TROUGH,
                array(array('foo' => 'bar'), array('bar' => 'foo')),
            ),
            array(
                ModelInterface::RELATION_MANY_TROUGH,
                array(array('foo' => 'bar'), array('bar' => 'foo')),
            ),
        );
    }

    /**
     * @dataProvider keyProvider
     */
    public function testKeys($type, $keys, $expectedKeys, $expectedLocal, $expectedForeign)
    {
        $relation = new Relation('\Foo', $type, $keys, 'foo', '\Bar');
        $this->assertEquals($expectedKeys, $relation->keys());
    }

    /**
     * @dataProvider keyProvider
     */
    public function testLocalKeys($type, $keys, $expectedKeys, $expectedLocal, $expectedForeign)
    {
        $relation = new Relation('\Foo', $type, $keys, 'foo', '\Bar');
        $this->assertEquals($expectedLocal, $relation->localKeys());
    }

    /**
     * @dataProvider keyProvider
     */
    public function testForeignKeys($type, $keys, $expectedKeys, $expectedLocal, $expectedForeign)
    {
        $relation = new Relation('\Foo', $type, $keys, 'foo', '\Bar');
        $this->assertEquals($expectedForeign, $relation->foreignKeys());
    }

    public function keyProvider()
    {
        return array(
            array(
                ModelInterface::RELATION_ONE,
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                array('foo'),
                array('bar')
            ),
            array(
                ModelInterface::RELATION_MANY,
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                array('foo'),
                array('bar')
            ),
            array(
                ModelInterface::RELATION_ONE_TROUGH,
                array(array('foo' => 'bar'), array('bar' => 'foo')),
                array('foo' => 'foo'),
                array('foo' => 'bar'),
                array('bar' => 'foo'),
            ),
            array(
                ModelInterface::RELATION_MANY_TROUGH,
                array(array('foo' => 'bar'), array('bar' => 'foo')),
                array('foo' => 'foo'),
                array('foo' => 'bar'),
                array('bar' => 'foo'),
            ),
        );
    }

    public function testDefaultTable()
    {
        $relation = new Relation('\Foo', ModelInterface::RELATION_ONE, array('foo' => 'bar'));
        $this->assertEquals('Foo', $relation->container());
    }

    public function testForcedTable()
    {
        $relation = new Relation('\Foo', ModelInterface::RELATION_ONE, array('foo' => 'bar'), 'Foobar');
        $this->assertEquals('Foobar', $relation->container());
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     */
    public function testWithoutKeys()
    {
        new Relation('\Foo', ModelInterface::RELATION_ONE, array());
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @dataProvider invalidKeysProvider
     */
    public function testWithInvalidKeys($keys)
    {
        new Relation('\Foo', ModelInterface::RELATION_ONE, $keys);
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
        $relation = new Relation('\Foo', ModelInterface::RELATION_ONE, array('foo' => 'bar'), 'Foobar');
        $relation->localValues(array('yada' => 'yada'));
        $this->assertEquals(array('yada' => 'yada'), $relation->localValues());
    }

    public function testReferencedValues()
    {
        $relation = new Relation('\Foo', ModelInterface::RELATION_ONE, array('foo' => 'bar'), 'Foobar');
        $relation->foreignValues(array('yada' => 'yada'));

        $this->assertEquals(array('yada' => 'yada'), $relation->foreignValues());
    }
}
