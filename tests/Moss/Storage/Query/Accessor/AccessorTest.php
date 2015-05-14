<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Accessor;


use Moss\Storage\TestEntity;

class AccessorTest extends \PHPUnit_Framework_TestCase
{
    public function testIdentify()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->any())->method('name')->willReturn('foo');

        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())->method('primaryFields')->willReturn([$field]);

        $entity = [];

        $accessor = new Accessor();
        $accessor->identifyEntity($model, $entity, 'foo');

        $this->assertEquals(['foo' => 'foo'], $entity);
    }

    public function testIdentifyWithMultiColumnPK()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())->method('primaryFields')->willReturn([$field, $field]);

        $entity = [];

        $accessor = new Accessor();
        $accessor->identifyEntity($model, $entity, 'foo');

        $this->assertEquals([], $entity);
    }

    public function testGetArray()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $accessor = new Accessor();
        $this->assertEquals('foo', $accessor->getPropertyValue($entity, 'foo'));
    }

    public function testGetPublicProperties()
    {
        $entity = (object) ['foo' => 'foo', 'bar' => 'bar'];

        $accessor = new Accessor();
        $this->assertEquals('foo', $accessor->getPropertyValue($entity, 'foo'));
    }

    public function testGetProtectedProperties()
    {
        $entity = new TestEntity('foo', 'bar');

        $accessor = new Accessor();
        $this->assertEquals('foo', $accessor->getPropertyValue($entity, 'foo'));
    }

    public function testGetDefault()
    {
        $entity = ['foo' => 'foo', 'bar' => 'bar'];

        $accessor = new Accessor();
        $this->assertEquals('foo', $accessor->getPropertyValue($entity, 'foo'));
    }

    public function testSetArray()
    {
        $entity = ['foo' => 'foo'];
        $expected = ['foo' => 'foo', 'bar' => 'bar'];

        $accessor = new Accessor();
        $accessor->setPropertyValue($entity, 'bar', 'bar');

        $this->assertEquals($expected, $entity);
    }

    public function testSetPublicProperties()
    {
        $entity = (object) ['foo' => 'foo'];
        $expected = (object) ['foo' => 'foo', 'bar' => 'bar'];

        $accessor = new Accessor();
        $accessor->setPropertyValue($entity, 'bar', 'bar');

        $this->assertEquals($expected, $entity);
    }

    public function testSetProtectedProperties()
    {
        $entity = new TestEntity('foo');
        $expected = new TestEntity('foo', 'bar');

        $accessor = new Accessor();
        $accessor->setPropertyValue($entity, 'bar', 'bar');

        $this->assertEquals($expected, $entity);
    }

    public function testAddToArray()
    {
        $entity = ['foo' => 'foo'];
        $expected = ['foo' => ['foo', 'bar']];

        $accessor = new Accessor();
        $accessor->addPropertyValue($entity, 'foo', 'bar');

        $this->assertEquals($expected, $entity);
    }

    public function testAddToPublicProperties()
    {
        $entity = (object) ['foo' => 'foo'];
        $expected = (object) ['foo' => ['foo', 'bar']];

        $accessor = new Accessor();
        $accessor->addPropertyValue($entity, 'foo', 'bar');

        $this->assertEquals($expected, $entity);
    }

    public function testAddToProtectedProperties()
    {
        $entity = new TestEntity('foo');
        $expected = new TestEntity(['foo', 'bar']);

        $accessor = new Accessor();
        $accessor->addPropertyValue($entity, 'foo', 'bar');

        $this->assertEquals($expected, $entity);
    }
}
