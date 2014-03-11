<?php
namespace Moss\Storage\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Field must be an instance of FieldInterface
     */
    public function testConstructorInvalidFieldInstance()
    {
        new Model('foo', 'Foo', array(new \stdClass()));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Index must be an instance of IndexInterface
     */
    public function testConstructorInvalidIndexInstance()
    {
        new Model('foo', 'Foo', array($this->mockField('foo')), array(new \stdClass()));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Relation must be an instance of RelationInterface
     */
    public function testConstructorInvalidRelationInstance()
    {
        new Model('foo', 'Foo', array($this->mockField('foo')), array($this->mockIndex('foo', 'index', array('foo'))), array(new \stdClass()));
    }

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
        $field = $this->mockField('foo');
        $model = new Model('foo', 'Foo', array($field));
        $this->assertEquals(array('foo' => $field), $model->fields());
    }

    public function testField()
    {
        $field = $this->mockField('foo');
        $model = new Model('foo', 'Foo', array($field));
        $this->assertEquals($field, $model->field('foo'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Field "yada" not found in model "foo"
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

    /**
     * @dataProvider isIndexProvider
     */
    public function testIsIndex($field, $expected)
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertEquals($expected, $model->isIndex($field));
    }

    public function isIndexProvider()
    {
        return array(
            array('foo', true),
            array('bar', false)
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Unknown field
     */
    public function testIsIndexWithInvalidField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'index', array('foo'))));
        $model->isIndex('yada');
    }

    /**
     * @dataProvider inIndexProvider
     */
    public function testInIndex($field, $expected)
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'index', array('foo'))));
        $this->assertEquals($expected, $model->inIndex($field));
    }

    public function inIndexProvider()
    {
        return array(
            array('foo', array($this->mockIndex('foo', 'index', array('foo')))),
            array('bar', array())
        );
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Unknown field
     */
    public function testInIndexWithInvalidField()
    {
        $model = new Model('foo', 'Foo', array($this->mockField('foo'), $this->mockField('bar')), array($this->mockIndex('foo', 'index', array('foo'))));
        $model->inIndex('yada');
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

    public function testIndexFieldsWithoutReps()
    {
        $fields = array($this->mockField('foo'), $this->mockField('bar'));
        $indexes = array($this->mockIndex('foo', 'index', array('foo')), $this->mockIndex('foobar', 'index', array('foo', 'bar')), $this->mockIndex('bar', 'index', array('bar')));

        $model = new Model('foo', 'Foo', $fields, $indexes);
        $this->assertEquals($fields, $model->indexFields());
    }

    public function testIndex()
    {
        $index = $this->mockIndex('foo', 'index', array('foo'));

        $model = new Model('foo', 'Foo', array($this->mockField('foo')), array($index));
        $this->assertEquals($index, $model->index('foo'));
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

        $mock->expects($this->any())
            ->method('table')
            ->will($this->returnValue(null));

        $mock->expects($this->any())
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

        $mock->expects($this->any())
            ->method('table')
            ->will($this->returnValue(null));

        $mock->expects($this->any())
            ->method('name')
            ->will($this->returnValue($index));

        $mock->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));

        $mock->expects($this->any())
            ->method('isPrimary')
            ->will($this->returnValue($type =='primary'));

        $mock->expects($this->any())
            ->method('fields')
            ->will($this->returnValue($fields));

        $mock->expects($this->any())
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

        $mock->expects($this->any())
            ->method('table')
            ->will($this->returnValue(null));

        $mock->expects($this->any())
            ->method('name')
            ->will($this->returnValue($relation));

        $mock->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));

        $mock->expects($this->any())
            ->method('keys')
            ->will($this->returnValue($keys));

        $mock->expects($this->any())
            ->method('localValues')
            ->will($this->returnValue($local));

        $mock->expects($this->any())
            ->method('foreignValues')
            ->will($this->returnValue($foreign));

        return $mock;
    }
}
