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


class RelationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Query\Relation\RelationException
     * @expectedExceptionMessage Invalid relation type
     */
    public function testBuildWithInvalidRelationType()
    {
        $query = $this->getMockBuilder('\Moss\Storage\Query\StorageInterface')->disableOriginalConstructor()->getMock();
        $bag = $this->getMock('\Moss\Storage\Model\ModelBag');

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->any())->method('type')->willReturn('foo');

        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())->method('hasRelation')->willReturn(true);
        $model->expects($this->any())->method('relation')->willReturn($definition);

        $factory = new RelationFactory($query, $bag);
        $factory->build($model, 'relation');
    }

    /**
     * @dataProvider typeProvider
     */
    public function testBuild($type, $expected)
    {
        $query = $this->getMockBuilder('\Moss\Storage\Query\StorageInterface')->disableOriginalConstructor()->getMock();
        $bag = $this->getMock('\Moss\Storage\Model\ModelBag');

        $definition = $this->getMock('\Moss\Storage\Model\Definition\RelationInterface');
        $definition->expects($this->any())->method('type')->willReturn($type);

        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())->method('hasRelation')->willReturn(true);
        $model->expects($this->any())->method('relation')->willReturn($definition);

        $factory = new RelationFactory($query, $bag);
        $instance = $factory->build($model, 'relation');

        $this->assertInstanceOf('\Moss\Storage\Query\Relation\RelationInterface', $instance);
        $this->assertInstanceOf($expected, $instance);
    }

    public function typeProvider()
    {
        return [
            [RelationFactory::RELATION_ONE, '\Moss\Storage\Query\Relation\OneRelation'],
            [RelationFactory::RELATION_MANY, '\Moss\Storage\Query\Relation\ManyRelation'],
            [RelationFactory::RELATION_ONE_TROUGH, '\Moss\Storage\Query\Relation\OneTroughRelation'],
            [RelationFactory::RELATION_MANY_TROUGH, '\Moss\Storage\Query\Relation\ManyTroughRelation']
        ];
    }
}
