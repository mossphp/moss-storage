<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Join;


use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\Many;
use Moss\Storage\Model\Definition\Relation\ManyTrough;
use Moss\Storage\Model\Definition\Relation\One;
use Moss\Storage\Model\Definition\Relation\OneTrough;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;

class JoinFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider typeProvider
     */
    public function testJoinType($type)
    {
        $factory = new JoinFactory($this->mockModelBag());
        $result = $factory->create('table', $type, 'other');

        $this->assertInstanceOf('Moss\Storage\Query\Join\Join', $result);
        $this->assertEquals($type, $result->type());
    }

    public function typeProvider()
    {
        return array(
            array('inner'),
            array('left'),
            array('right')
        );
    }

    /**
     * @dataProvider relationJointsProvider
     */
    public function testRelationTypeJoints($type, $expected)
    {
        $factory = new JoinFactory($this->mockModelBag($type));
        $result = $factory->create('table', 'left', 'other');

        $this->assertInstanceOf('Moss\Storage\Query\Join\Join', $result);
        $this->assertEquals($expected, $result->joints());
    }

    public function relationJointsProvider()
    {
        return array(
            array(
                'one',
                array(array('left', 'test_other', array('test_table.id' => 'test_other.id'))),
            ),
            array(
                'many',
                array(array('left', 'test_other', array('test_table.id' => 'test_other.id'))),
            ),
            array(
                'oneTrough',
                array(
                    array('left', 'test_mediator', array('test_table.id' => 'test_mediator.in_id')),
                    array('left', 'test_other', array('test_mediator.out_id' => 'test_other.id'))
                ),
            ),
            array(
                'manyTrough',
                array(
                    array('left', 'test_mediator', array('test_table.id' => 'test_mediator.in_id')),
                    array('left', 'test_other', array('test_mediator.out_id' => 'test_other.id'))
                ),
            )
        );
    }

    /**
     * @dataProvider relationConditionsProvider
     */
    public function testRelationTypeConditions($type, $expected)
    {
        $factory = new JoinFactory($this->mockModelBag($type));
        $result = $factory->create('table', 'left', 'other');

        $this->assertInstanceOf('Moss\Storage\Query\Join\Join', $result);
        $this->assertEquals($expected, $result->conditions());
    }

    public function relationConditionsProvider()
    {
        return array(
            array(
                'one',
                array(
                    array('test_table.id', 1, '=', 'and'),
                    array('test_other.id', 2, '=', 'and')
                )
            ),
            array(
                'many',
                array(
                    array('test_table.id', 1, '=', 'and'),
                    array('test_other.id', 2, '=', 'and')
                )
            ),
            array(
                'oneTrough',
                array(
                    array('test_table.id', 1, '=', 'and'),
                    array('test_other.id', 2, '=', 'and')
                )
            ),
            array(
                'manyTrough',
                array(
                    array('test_table.id', 1, '=', 'and'),
                    array('test_other.id', 2, '=', 'and')
                )
            )
        );
    }

    protected function mockModelBag($relType = 'one')
    {
        $table = new Model(
            '\stdClass',
            'test_table',
            array(
                new Integer('id', array('auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                $this->mockRelation($relType)
            )
        );

        $other = new Model(
            '\altClass',
            'test_other',
            array(
                new Integer('id', array('auto_increment')),
                new String('text', array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            )
        );

        $mediator = new Model(
            null,
            'test_mediator',
            array(
                new Integer('in'),
                new Integer('out'),
            ),
            array(
                new Primary(array('in', 'out')),
            )
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');
        $bag->set($mediator, 'mediator');

        return $bag;
    }

    protected function mockRelation($relType)
    {
        switch ($relType) {
            case 'one':
            default:
                $relation = new One('\altClass', array('id' => 'id'), 'other');
                break;
            case 'many':
                $relation = new Many('\altClass', array('id' => 'id'), 'other');
                break;
            case 'oneTrough':
                $relation = new OneTrough('\altClass', array('id' => 'in_id'), array('out_id' => 'id'), 'mediator', 'other');
                break;
            case 'manyTrough':
                $relation = new ManyTrough('\altClass', array('id' => 'in_id'), array('out_id' => 'id'), 'mediator', 'other');
                break;
        }

        $relation->localValues(array('id' => 1));
        $relation->foreignValues(array('id' => 2));

        return $relation;
    }
}
 