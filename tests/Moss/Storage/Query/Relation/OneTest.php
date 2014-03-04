<?php
namespace Moss\Storage\Query\Relation;


use Moss\Storage\Model\Definition\Field\Field;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Definition\Relation\Relation;
use Moss\Storage\Model\Model;

class OneTest extends \PHPUnit_Framework_TestCase
{

    public function testRead()
    {
        $bag = $this->mockModelBag();
        $rel = new One(
            $this->mockQuery(),
            $bag->get('table')
                ->relation('other'),
            $bag
        );
    }

    public function testWrite()
    {
        $this->markTestIncomplete();
    }

    public function testDelete()
    {
        $this->markTestIncomplete();
    }

    public function testClear()
    {
        $this->markTestIncomplete();
    }

    protected function mockQuery()
    {
        $mock = $this->getMock('\Moss\Storage\Query\QueryInterface');

        return $mock;
    }

    protected function mockRelation(array $keys)
    {
        $mock = $this->getMock('\Moss\Storage\Model\Definition\Relation\RelationInterface');

        return $mock;
    }

    protected function mockModelBag($keys = array('id' => 'id'))
    {
        $table = new Model(
            '\stdClass',
            'table',
            array(
                new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            ),
            array(
                $this->mockRelation($keys)
            )
        );

        $other = new Model(
            '\altClass',
            'other',
            array(
                new Field('id', Model::FIELD_INTEGER, array('unsigned', 'auto_increment')),
                new Field('text', Model::FIELD_STRING, array('length' => '128', 'null')),
            ),
            array(
                new Primary(array('id')),
            )
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');

        return $bag;
    }
}