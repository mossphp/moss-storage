<?php
namespace moss\storage\model;

class ModelTest extends \PHPUnit_Framework_TestCase
{


    public function testContainer()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $this->assertEquals('Foo', $model->container());
    }

    public function testEntity()
    {
        $model = new Model('\Foo', 'Foo', array($this->mockField('foo')));
        $this->assertEquals('Foo', $model->entity());
    }

    public function testAddField()
    {
        $model = new Model('foo', 'Foo', array());
        $model->setField($this->mockField('foo'));
        $this->assertTrue($model->hasField('foo'));
        $this->assertFalse($model->hasField('bar'));
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
     * @expectedException \moss\storage\model\ModelException
     */
    public function testUndefinedField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $model->field('yada');
    }

    public function testSetIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')));
        $model->setIndex($this->mockIndex('foo', 'primary', array('foo')));
        $this->assertTrue($model->isIndex('foo'));
    }

    public function testIsPrimary()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'primary', array('foo'))));
        $this->assertTrue($model->isPrimary('foo'));
        $this->assertFalse($model->isPrimary('bar'));
    }

    /**
     * @expectedException \moss\storage\model\ModelException
     */
    public function testIsNonExistingPrimaryField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'primary', array('foo'))));
        $model->isPrimary('yada');
    }

    public function testPrimaryFields()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'primary', array('foo'))));
        $this->assertEquals(array('foo'), $model->primaryFields());
    }

    public function testIsIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertTrue($model->isIndex('foo'));
        $this->assertFalse($model->isIndex('bar'));
    }

    /**
     * @expectedException \moss\storage\model\ModelException
     */
    public function testIsNonExistingIndexField()
    {
        new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('bar'))));
    }

    public function testIndexFields()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertEquals(array('foo'), $model->indexFields());
    }

    public function testIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertEquals($this->mockIndex('foo', 'index', array('foo')), $model->index('foo'));
    }

    /**
     * @expectedException \moss\storage\model\ModelException
     */
    public function testUndefinedIndex()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('foo'))));
        $model->index('yada');
    }

    public function testSetRelation()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array());
        $this->assertFalse($model->hasRelations());

        $model->setRelation($this->mockRelation('bar', 'one', array('foo' => 'bar')));

        $this->assertTrue($model->hasRelations());
        $this->assertTrue($model->hasRelation('bar'));
    }

    /**
     * @expectedException \moss\storage\model\ModelException
     */
    public function testUndefinedRelationKeyField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array(), array($this->mockRelation('bar', 'one', array('bar' => 'bar'))));
        $model->index('yada');
    }

    /**
     * @expectedException \moss\storage\model\ModelException
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
     * @expectedException \moss\storage\model\ModelException
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
     * @return \moss\storage\model\definition\FieldInterface
     */
    protected function mockField($field)
    {
        $mock = $this->getMock('\moss\storage\model\definition\FieldInterface');

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
     * @return \moss\storage\model\definition\IndexInterface
     */
    protected function mockIndex($index, $type, $fields)
    {
        $mock = $this->getMock('\moss\storage\model\definition\IndexInterface');

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
     * @return \moss\storage\model\definition\RelationInterface
     */
    protected function mockRelation($relation, $type, $keys, $local = array(), $foreign = array())
    {
        $mock = $this->getMock('\moss\storage\model\definition\RelationInterface');

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
