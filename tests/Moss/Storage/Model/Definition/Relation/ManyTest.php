<?php
namespace Moss\Storage\Model\Definition\Relation;

class ManyTest extends \PHPUnit_Framework_TestCase
{
    public function testType() {
        $relation = new Many('\Foo', ['foo' => 'bar']);
        $this->assertEquals('many', $relation->type());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultName($name, $expected)
    {
        $relation = new Many($name, ['foo' => 'bar']);
        $this->assertEquals($expected, $relation->name());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testDefaultContainer($name, $expected)
    {
        $relation = new Many($name, ['foo' => 'bar']);
        $this->assertEquals($expected, $relation->container());
    }

    /**
     * @dataProvider defaultNameProvider
     */
    public function testForcedContainer($name)
    {
        $relation = new Many($name, ['foo' => 'bar'], 'Foobar');
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
        $relation = new Many('\Foo', ['foo' => 'bar'], 'Foobar');
        $this->assertEquals('Foobar', $relation->name());
    }

    public function testEntity()
    {
        $relation = new Many('\Foo', ['foo' => 'bar']);
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
        return [
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                ['foo'],
                ['bar']
            ],
        ];
    }

    /**
     * @expectedException \Moss\Storage\Model\Definition\DefinitionException
     */
    public function testWithoutKeys()
    {
        new Many('\Foo', []);
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
        return [
            [['' => 1]],
            [['foo' => 1]],
            [[1 => null]],
            [[1 => 'foo']],
            [[1 => new \stdClass()]],
            [[1 => [1, 2]]],
        ];
    }
}
