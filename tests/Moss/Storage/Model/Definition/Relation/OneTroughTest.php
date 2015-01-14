<?php
namespace Moss\Storage\Model\Definition\Relation;

class OneTroughTest extends \PHPUnit_Framework_TestCase
{
    public function testType()
    {
        $relation = new OneTrough('\Foo', ['id' => 'in'], ['out' => 'id'], 'mediator');
        $this->assertEquals('oneTrough', $relation->type());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultName($name, $expected)
    {
        $relation = new OneTrough($name, ['id' => 'in'], ['out' => 'id'], 'mediator');
        $this->assertEquals($expected, $relation->name());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultContainer($name, $expected)
    {
        $relation = new OneTrough($name, ['id' => 'in'], ['out' => 'id'], 'mediator');
        $this->assertEquals($expected, $relation->container());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testForcedContainer($name)
    {
        $relation = new OneTrough($name, ['id' => 'in'], ['out' => 'id'], 'mediator', 'Foobar');
        $this->assertEquals('Foobar', $relation->container());
    }

    public function defaultNameProvider()
    {
        return [
            ['Foo', 'Foo'],
            ['\Foo', 'Foo'],
            ['\\Foo', 'Foo'],
            ['\\\\Foo', 'Foo'],
            ['\\Foo\\Bar', 'Bar'],
        ];
    }

    public function testForcedName()
    {
        $relation = new OneTrough('\Foo', ['id' => 'in'], ['out' => 'id'], 'mediator', 'Foobar');
        $this->assertEquals('Foobar', $relation->name());
    }

    public function testEntity()
    {
        $relation = new OneTrough('\Foo', ['id' => 'in'], ['out' => 'id'], 'mediator');
        $this->assertEquals('Foo', $relation->entity());
    }

    /**
     * @dataProvider keyProvider
     */
    public function testKeys($keys, $expectedKeys, $expectedLocal, $expectedForeign)
    {
        $relation = new OneTrough('\Foo', $keys[0], $keys[1], 'foo', '\Bar');
        $this->assertEquals($expectedKeys, $relation->keys());
        $this->assertEquals($expectedLocal, $relation->localKeys());
        $this->assertEquals($expectedForeign, $relation->foreignKeys());
    }

    public function keyProvider()
    {
        return [
            [
                [['id' => 'in'], ['out' => 'id']],
                ['id' => 'id'],
                ['id' => 'in'],
                ['out' => 'id']
            ],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Invalid keys for relation "Foo", must be two arrays with key-value pairs
     */
    public function testWithoutInKeys()
    {
        new OneTrough('\Foo', [], ['out' => 'id'], 'mediator');
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Invalid keys for relation "Foo", must be two arrays with key-value pairs
     */
    public function testWithoutOutKeys()
    {
        new OneTrough('\Foo', ['id' => 'in'], [], 'mediator');
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Both key arrays for relation "Foo", must have the same number of elements
     */
    public function testKeysWithoutSameNumberOfElements()
    {
        new OneTrough('\Foo', ['id' => 'in'], ['foo' => 'foo', 'bar' => 'bar'], 'mediator');
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     * @expectedExceptionMessage Invalid field name for relation
     * @dataProvider      invalidKeysProvider
     */
    public function testWithInvalidKeys($keys)
    {
        new OneTrough('\Foo', $keys, $keys, 'mediator');
    }

    public function invalidKeysProvider()
    {
        return [
            [['' => 1]],
            [['foo' => 1]],
            [[1 => null]],
            [[1 => 'foo']],
            [[1 => new \stdClass()]],
            [[1 => [1, 2]]],
        ];
    }

    public function testLocalValues()
    {
        $relation = new OneTrough('\Foo', ['id' => 'in'], ['out' => 'id'], 'mediator', 'Foobar');
        $relation->localValues(['yada' => 'yada']);
        $this->assertEquals(['yada' => 'yada'], $relation->localValues());
    }

    public function testReferencedValues()
    {
        $relation = new OneTrough('\Foo', ['id' => 'in'], ['out' => 'id'], 'mediator', 'Foobar');
        $relation->foreignValues(['yada' => 'yada']);

        $this->assertEquals(['yada' => 'yada'], $relation->foreignValues());
    }
}
