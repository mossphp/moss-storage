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


class OneRelationTest extends \PHPUnit_Framework_TestCase
{
    public function testRead()
    {
        $expected = [
            (object) [
                'id' => 1,
                'rel' => (object) ['rel_id' => 1]
            ]
        ];

        $readQuery = $this->getMock('\Moss\Storage\Query\ReadQueryInterface');
        $readQuery->expects($this->once())->method('execute')->willReturn(
            [
                (object) ['rel_id' => 1],
                (object) ['rel_id' => 2] // this is ignored since no id is matching it
            ]
        );

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->once())->method('read')->willReturn($readQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->exactly(1))->method('container')->willReturn('rel');
        $definition->expects($this->exactly(4))->method('keys')->willReturn(['id' => 'rel_id']);
        $definition->expects($this->once())->method('entity')->willReturn('\stdClass');

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $collection = [
            (object) ['id' => 1, 'rel' => null],
        ];

        $definition = new OneRelation($query, $definition, $models);
        $result = $definition->read($collection);

        $this->assertEquals($expected, $result);
    }

    public function testWriteRelationalEntity()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => (object) ['rel_id' => 1, 'text']
        ];

        $updateQuery = $this->getMock('\Moss\Storage\Query\UpdateQueryInterface');
        $updateQuery->expects($this->once())->method('execute')->willReturn(true);

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->once())->method('write')->willReturn($updateQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->exactly(2))->method('container')->willReturn('rel');
        $definition->expects($this->exactly(1))->method('keys')->willReturn(['id' => 'rel_id']);
        $definition->expects($this->exactly(2))->method('entity')->willReturn('\stdClass');

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $relation = new OneRelation($query, $definition, $models);
        $relation->write($entity);
    }

    public function testWriteRemovingRelationalEntity()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => null
        ];

        $readQuery = $this->getMock('\Moss\Storage\Query\ReadQueryInterface');
        $readQuery->expects($this->once())->method('execute')->willReturn(
            [
                (object) ['rel_id' => 1],
            ]
        );

        $deleteQuery = $this->getMock('\Moss\Storage\Query\DeleteQueryInterface');

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->once())->method('read')->willReturn($readQuery);
        $query->expects($this->once())->method('delete')->willReturn($deleteQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->once())->method('container')->willReturn('rel');
        $definition->expects($this->once())->method('keys')->willReturn(['id' => 'rel_id']);
        $definition->expects($this->once())->method('entity')->willReturn('\stdClass');

        $field = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $field->expects($this->once())->method('name')->willReturn('id');

        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->once())->method('primaryFields')->willReturn([$field]);

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');
        $models->expects($this->once())->method('get')->willReturn($model);

        $relation = new OneRelation($query, $definition, $models);
        $relation->write($entity);
    }

    public function testDelete()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => (object) ['rel_id' => 1, 'text']
        ];

        $deleteQuery = $this->getMock('\Moss\Storage\Query\DeleteQueryInterface');
        $deleteQuery->expects($this->once())->method('execute');

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->once())->method('delete')->willReturn($deleteQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->exactly(1))->method('container')->willReturn('rel');
        $definition->expects($this->exactly(2))->method('entity')->willReturn('\stdClass');

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $relation = new OneRelation($query, $definition, $models);
        $relation->delete($entity);
    }

    public function testDeleteWithoutRelationalEntity()
    {
        $entity = (object) [
            'id' => 1,
            'rel' => null
        ];

        $deleteQuery = $this->getMock('\Moss\Storage\Query\DeleteQueryInterface');

        $query = $this->getMockBuilder('\Moss\Storage\Query\Query')->disableOriginalConstructor()->getMock();
        $query->expects($this->never())->method('delete')->willReturn($deleteQuery);

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->exactly(1))->method('container')->willReturn('rel');

        $models = $this->getMock('\Moss\Storage\Model\ModelBag');

        $definition = new OneRelation($query, $definition, $models);
        $definition->delete($entity);
    }
}
