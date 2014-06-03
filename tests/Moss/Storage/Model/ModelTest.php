<?php
namespace Moss\Storage\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Field must be an instance of FieldInterface
     */
    public function testConstructorWithInvalidFieldInstance()
    {
        new Model('Foo', 'foo', array(new \stdClass()));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Index must be an instance of IndexInterface
     */
    public function testConstructorWithInvalidIndexInstance()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        new Model('Foo', 'foo', array($field), array(new \stdClass()));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Relation must be an instance of RelationInterface
     */
    public function testConstructorWithInvalidRelationInstance()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('fields')
            ->will($this->returnValue(array('foo')));

        new Model('Foo', 'foo', array($field), array($index), array(new \stdClass()));
    }

    public function testTable()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = new Model('Foo', 'foo', array($field));
        $this->assertEquals('foo', $model->table());
    }

    public function testEntity()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = new Model('\Foo', 'foo', array($field));
        $this->assertEquals('Foo', $model->entity());
    }

    public function testAlias()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = new Model('\Foo', 'foo', array($field));
        $this->assertEquals('foofoo', $model->alias('foofoo'));
    }

    public function testIsNamedByEntityName()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = new Model('\Foo', 'foo', array($field));

        $this->assertTrue($model->isNamed('\Foo'));
    }

    public function testIsNamedByTableName()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = new Model('\Foo', 'foo', array($field));

        $this->assertTrue($model->isNamed('foo'));
    }

    public function testIsNamedByItsAlias()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');

        $model = new Model('\Foo', 'foo', array($field));
        $model->alias('foofoo');

        $this->assertTrue($model->isNamed('foofoo'));
    }

    public function testHasField()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $model = new Model('Foo', 'foo', array($field));
        $this->assertTrue($model->hasField('foo'));
    }

    public function testFields()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $model = new Model('Foo', 'foo', array($field));
        $this->assertEquals(array('foo' => $field), $model->fields());
    }

    public function testField()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $model = new Model('Foo', 'foo', array($field));
        $this->assertEquals($field, $model->field('foo'));
    }

    public function testIsPrimary()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('isPrimary')
            ->will($this->returnValue(true));
        $index->expects($this->exactly(2))
            ->method('fields')
            ->will($this->returnValue(array('foo')));

        $model = new Model('Foo', 'foo', array($field), array($index));
        $this->assertTrue($model->isPrimary('foo'));
    }

    public function testPrimaryFields()
    {
        $foo = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $foo->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $bar = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $bar->expects($this->once())
            ->method('name')
            ->will($this->returnValue('bar'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('isPrimary')
            ->will($this->returnValue(true));
        $index->expects($this->exactly(2))
            ->method('fields')
            ->will($this->returnValue(array('foo', 'bar')));

        $model = new Model('Foo', 'foo', array($foo, $bar), array($index));
        $this->assertEquals(array($foo, $bar), $model->primaryFields());
    }

    public function testIsIndex()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->exactly(2))
            ->method('fields')
            ->will($this->returnValue(array('foo')));

        $model = new Model('Foo', 'foo', array($field), array($index));
        $this->assertTrue($model->isIndex('foo'));
    }

    public function testInIndex()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('fields')
            ->will($this->returnValue(array('foo')));
        $index->expects($this->once())
            ->method('hasField')
            ->will($this->returnValue(true));

        $model = new Model('Foo', 'foo', array($field), array($index));
        $this->assertEquals(array($index), $model->inIndex('foo'));
    }

    public function testIndexFields()
    {
        $foo = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $foo->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $bar = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $bar->expects($this->once())
            ->method('name')
            ->will($this->returnValue('bar'));

        $yada = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $yada->expects($this->once())
            ->method('name')
            ->will($this->returnValue('yada'));

        $fooBar = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $fooBar->expects($this->exactly(1))
            ->method('name')
            ->will($this->returnValue('fooBar'));
        $fooBar->expects($this->exactly(2))
            ->method('fields')
            ->will($this->returnValue(array('foo', 'bar')));

        $barYada = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $barYada->expects($this->exactly(1))
            ->method('name')
            ->will($this->returnValue('barYada'));
        $barYada->expects($this->exactly(2))
            ->method('fields')
            ->will($this->returnValue(array('bar', 'yada')));

        $model = new Model('Foo', 'foo', array($foo, $bar, $yada), array($fooBar, $barYada));
        $this->assertEquals(array($foo, $bar, $yada), $model->indexFields());
    }

    public function testHasIndex()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));
        $index->expects($this->once())
            ->method('fields')
            ->will($this->returnValue(array('foo')));

        $model = new Model('Foo', 'foo', array($field), array($index));
        $this->assertTrue($model->hasIndex('foo'));
    }

    public function testIndexes()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));
        $index->expects($this->once())
            ->method('fields')
            ->will($this->returnValue(array('foo')));

        $model = new Model('Foo', 'foo', array($field), array($index));
        $this->assertEquals(array('foo' => $index), $model->indexes());
    }

    public function testIndex()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $index = $this->getMock('\Moss\Storage\Model\Definition\IndexInterface');
        $index->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));
        $index->expects($this->once())
            ->method('fields')
            ->will($this->returnValue(array('foo')));

        $model = new Model('Foo', 'foo', array($field), array($index));
        $this->assertEquals($index, $model->index('foo'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Unknown index, index "yada" not found in model "Foo"
     */
    public function testUndefinedIndex()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $model = new Model('Foo', 'foo', array($field));
        $model->index('yada');
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Relation field "yada" does not exist in entity model "Foo"
     */
    public function testUndefinedRelationKeyField()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $relation = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $relation->expects($this->once())
            ->method('keys')
            ->will($this->returnValue(array('yada' => 'yada')));

        $model = new Model('Foo', 'foo', array($field), array(), array($relation));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Relation field "yada" does not exist in entity model "Foo"
     */
    public function testUndefinedRelationLocalField()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $relation = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $relation->expects($this->once())
            ->method('keys')
            ->will($this->returnValue(array('foo' => 'foo')));
        $relation->expects($this->once())
            ->method('localValues')
            ->will($this->returnValue(array('yada' => 'yada')));

        $model = new Model('Foo', 'foo', array($field), array(), array($relation));
    }

    public function testRelations()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $relation = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $relation->expects($this->once())
            ->method('name')
            ->will($this->returnValue('Bar'));
        $relation->expects($this->once())
            ->method('keys')
            ->will($this->returnValue(array('foo' => 'foo')));
        $relation->expects($this->once())
            ->method('localValues')
            ->will($this->returnValue(array()));

        $model = new Model('Foo', 'foo', array($field), array(), array($relation));
        $this->assertEquals(array('Bar' => $relation), $model->relations());
    }

    public function testHasRelations()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $relation = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $relation->expects($this->once())
            ->method('entity')
            ->will($this->returnValue('Bar'));
        $relation->expects($this->once())
            ->method('keys')
            ->will($this->returnValue(array('foo' => 'foo')));
        $relation->expects($this->once())
            ->method('localValues')
            ->will($this->returnValue(array()));

        $model = new Model('Foo', 'foo', array($field), array(), array($relation));
        $this->assertTrue($model->hasRelation('Bar'));
    }

    public function testRelation()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $relation = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $relation->expects($this->once())
            ->method('entity')
            ->will($this->returnValue('Bar'));
        $relation->expects($this->once())
            ->method('keys')
            ->will($this->returnValue(array('foo' => 'foo')));
        $relation->expects($this->once())
            ->method('localValues')
            ->will($this->returnValue(array()));

        $model = new Model('Foo', 'foo', array($field), array(), array($relation));
        $this->assertEquals($relation, $model->relation('Bar'));
    }

    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Unknown relation, relation "Bar" not found in model "Foo"
     */
    public function testUndefinedRelation()
    {
        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())
            ->method('name')
            ->will($this->returnValue('foo'));

        $model = new Model('Foo', 'foo', array($field));
        $model->relation('Bar');
    }
}
