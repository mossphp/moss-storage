<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Relation;


class OneTroughRelationTest extends \PHPUnit_Framework_TestCase
{
    public function testRead()
    {
        $expected = [
            (object) [
                'id' => 1,
                'rel' => (object) ['rel_id' => 1]
            ]
        ];

        $mediatorReadQuery = $this->getMock('\Moss\Storage\Query\ReadQueryInterface');
        $mediatorReadQuery->expects($this->once())->method('execute')->willReturn([(object) ['l_id' => 1, 'f_id' => 1]]);

        $entityReadQuery = $this->getMock('\Moss\Storage\Query\ReadQueryInterface');
        $entityReadQuery->expects($this->once())->method('execute')->willReturn([(object) ['rel_id' => 1]]);

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->exactly(2))->method('read')->willReturnMap(
            [
                ['mediator', $mediatorReadQuery],
                ['entity', $entityReadQuery]
            ]
        );

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->once())->method('entity')->willReturn('entity');
        $definition->expects($this->once())->method('mediator')->willReturn('mediator');
        $definition->expects($this->once())->method('container')->willReturn('rel');
        $definition->expects($this->exactly(3))->method('localKeys')->willReturn(['id' => 'l_id']);
        $definition->expects($this->exactly(3))->method('foreignKeys')->willReturn(['f_id' => 'rel_id']);

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $factory = $this->getMock('\Moss\Storage\Query\Relation\RelationFactoryInterface');

        $collection = [
            (object) ['id' => 1, 'rel' => null],
        ];

        $definition = new OneTroughRelation($query, $definition, $models, $factory);
        $result = $definition->read($collection);

        $this->assertEquals($expected, $result);
    }

    public function testWriteRelationalEntity()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => (object) ['rel_id' => 1]
        ];

        $entityUpdateQuery = $this->getMock('\Moss\Storage\Query\UpdateQueryInterface');
        $entityUpdateQuery->expects($this->once())->method('execute')->willReturn(true);

        $mediatorUpdateQuery = $this->getMock('\Moss\Storage\Query\UpdateQueryInterface');
        $mediatorUpdateQuery->expects($this->once())->method('execute')->willReturn(true);

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->exactly(2))->method('write')->willReturnMap(
            [
                ['entity', $entity->rel, $entityUpdateQuery],
                ['mediator', ['l_id' => 1, 'f_id' => 1], $mediatorUpdateQuery]
            ]
        );

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->once())->method('entity')->willReturn('entity');
        $definition->expects($this->once())->method('mediator')->willReturn('mediator');
        $definition->expects($this->exactly(2))->method('container')->willReturn('rel');
        $definition->expects($this->exactly(2))->method('localKeys')->willReturn(['id' => 'l_id']);
        $definition->expects($this->once())->method('foreignKeys')->willReturn(['f_id' => 'rel_id']);

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $factory = $this->getMock('\Moss\Storage\Query\Relation\RelationFactoryInterface');

        $relation = new OneTroughRelation($query, $definition, $models, $factory);
        $relation->write($entity);
    }

    public function testWriteRemovingRelationalEntity()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => null
        ];

        $readQuery = $this->getMock('\Moss\Storage\Query\UpdateQueryInterface');
        $readQuery->expects($this->once())->method('execute')->willReturn([['l_id' => 1, 'r_id' => 1]]);

        $deleteQuery = $this->getMock('\Moss\Storage\Query\UpdateQueryInterface');
        $deleteQuery->expects($this->once())->method('execute')->willReturn(true);

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->once())->method('read')->willReturn($readQuery);
        $query->expects($this->once())->method('delete')->willReturn($deleteQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->once())->method('mediator')->willReturn('mediator');
        $definition->expects($this->once())->method('container')->willReturn('rel');
        $definition->expects($this->once())->method('localKeys')->willReturn(['id' => 'l_id']);

        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())->method('name')->willReturn('id');

        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->once())->method('primaryFields')->willReturn([$field]);

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');
        $models->expects($this->once())->method('get')->willReturn($model);

        $factory = $this->getMock('\Moss\Storage\Query\Relation\RelationFactoryInterface');

        $relation = new OneTroughRelation($query, $definition, $models, $factory);
        $relation->write($entity);
    }

    public function testDelete()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => (object) ['rel_id' => 1]
        ];

        $deleteQuery = $this->getMock('\Moss\Storage\Query\UpdateQueryInterface');
        $deleteQuery->expects($this->once())->method('execute')->willReturn(true);

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->once())->method('delete')->willReturn($deleteQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->once())->method('mediator')->willReturn('mediator');
        $definition->expects($this->exactly(2))->method('container')->willReturn('rel');
        $definition->expects($this->once(1))->method('localKeys')->willReturn(['id' => 'l_id']);
        $definition->expects($this->once())->method('foreignKeys')->willReturn(['f_id' => 'rel_id']);

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $factory = $this->getMock('\Moss\Storage\Query\Relation\RelationFactoryInterface');

        $relation = new OneTroughRelation($query, $definition, $models, $factory);
        $relation->delete($entity);
    }

    public function testDeleteWithoutRelationalEntity()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => null
        ];

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->once())->method('container')->willReturn('rel');

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $factory = $this->getMock('\Moss\Storage\Query\Relation\RelationFactoryInterface');

        $relation = new OneTroughRelation($query, $definition, $models, $factory);
        $relation->delete($entity);
    }
}
