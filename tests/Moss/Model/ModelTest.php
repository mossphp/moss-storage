<?php
namespace Moss\Storage\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{


    public function testTable()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $this->assertEquals('Foo', $model->table());
    }

    public function testEntity()
    {
        $model = new Model('\Foo', 'Foo', array($this->mockField('foo')));
        $this->assertEquals('Foo', $model->entity());
    }

    public function testHasField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $this->assertTrue($model->hasField('foo'));
    }

    public function testFields()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $this->assertEquals(array('foo' => $this->mockField('foo')), $model->fields());
    }

    public function testField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $this->assertEquals($this->mockField('foo'), $model->field('foo'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testUndefinedField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $model->field('yada');
    }

    public function testIsPrimary()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'primary', array('foo'))));
        $this->assertTrue($model->isPrimary('foo'));
        $this->assertFalse($model->isPrimary('bar'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testIsNonExistingPrimaryField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'primary', array('foo'))));
        $model->isPrimary('yada');
    }

    public function testPrimaryFields()
    {
        $fields = array($this->mockField('foo'), $this->mockField('bar'));

        $model = new Model('foo', 'Foo', $fields, array($this->mockIndex('foo', 'primary', array('foo'))));
        $this->assertEquals(array($fields[0]), $model->primaryFields());
    }

    public function testIsIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertTrue($model->isIndex('foo'));
        $this->assertFalse($model->isIndex('bar'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testIsNonExistingIndexField()
    {
        new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('bar'))));
    }

    public function testIndexFields()
    {
        $fields = array($this->mockField('foo'), $this->mockField('bar'));

        $model = new Model('foo', 'Foo', $fields, array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertEquals(array($fields[0]), $model->indexFields());
    }

    public function testIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertEquals($this->mockIndex('foo', 'index', array('foo')), $model->index('foo'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testUndefinedIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('foo'))));
        $model->index('yada');
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testUndefinedRelationKeyField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array($this->mockRelation('bar', 'one', array('bar' => 'bar'))));
        $model->index('yada');
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testUndefinedRelationLocalField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array($this->mockRelation('bar', 'one', array('foo' => 'bar'), array('bar' => 'bar'))));
        $model->index('yada');
    }

    public function testRelations()
    {
        $rel = $this->mockRelation('bar', 'one', array('foo' => 'bar'));

        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array($rel));
        $this->assertEquals(array($rel->name() => $rel), $model->relations());
    }

    public function testRelation()
    {
        $rel = $this->mockRelation('bar', 'one', array('foo' => 'bar'));

        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array($rel));
        $this->assertEquals($rel, $model->relation($rel->name()));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     */
    public function testUndefinedRelation()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array($this->mockRelation('bar', 'one', array('foo' => 'bar'))));
        $model->relation('yada');
    }

    // mocks

    /**
     * @param string $field
     *
     * @return \Moss\Storage\Model\Definition\FieldInterface
     */
    protected function mockField($field)
    {
        $mock = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $mock
            ->expects($this->any())
            ->method('name')
            ->will($this->returnValue($field));

        return $mock;
    }

    /**
     * @param string $index
     * @param string $type
     * @param array  $fields
     *
     * @return \Moss\Storage\Model\Definition\IndexInterface
     */
    protected function mockIndex($index, $type, $fields)
    {
        $mock = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');

        $mock
            ->expects($this->any())
            ->method('name')
            ->will($this->returnValue($index));

        $mock
            ->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));

        $mock
            ->expects($this->any())
            ->method('isPrimary')
            ->will($this->returnValue($type == ModelInterface::INDEX_PRIMARY));

        $mock
            ->expects($this->any())
            ->method('fields')
            ->will($this->returnValue($fields));

        $mock
            ->expects($this->any())
            ->method('hasField')
            ->will(
            $this->returnCallback(
                 function ($field) use ($fields) {
                     return in_array($field, $fields);
                 }
            )
            );

        return $mock;
    }

    /**
     * @param string $relation
     * @param string $type
     * @param array  $keys
     * @param array  $local
     * @param array  $foreign
     *
     * @return \Moss\Storage\Model\Definition\RelationInterface
     */
    protected function mockRelation($relation, $type, $keys, $local = array(), $foreign = array())
    {
        $mock = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');

        $mock
            ->expects($this->any())
            ->method('name')
            ->will($this->returnValue($relation));

        $mock
            ->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));

        $mock
            ->expects($this->any())
            ->method('keys')
            ->will($this->returnValue($keys));

        $mock
            ->expects($this->any())
            ->method('localValues')
            ->will($this->returnValue($local));

        $mock
            ->expects($this->any())
            ->method('foreignValues')
            ->will($this->returnValue($foreign));

        return $mock;
    }
}
