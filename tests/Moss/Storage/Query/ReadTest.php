<?php
namespace Moss\Storage\Query;

use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\ModelBag;

class ReadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Query\QueryException
     * @expectedExceptionMessage Unable to access field
     */
    public function testInvalidField()
    {
        $driver = $this->getMock('\Moss\Storage\Driver\DriverInterface');

        $builder = $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface');

        $bag = $this->mockModelBag();

        $query = new ReadQuery($driver, $builder, $bag, 'table');

        $query->reset()
            ->field('foobar');
    }

    public function testReadFields()
    {
        $driver = $this->getMock('\Moss\Storage\Driver\DriverInterface');

        $builder = $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface');
        $builder->expects($this->at(0))->method('reset');
        $builder->expects($this->at(1))->method('reset');
        $builder->expects($this->at(2))->method('select');
        $builder->expects($this->at(3))->method('field')->with('test_table.id', null);
        $builder->expects($this->at(4))->method('field')->with('test_table.text', 'alias');

        $bag = $this->mockModelBag();

        $query = new ReadQuery($driver, $builder, $bag, 'table');

        $query->reset()
            ->fields(['id', 'text'])
            ->queryString();
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testReadFieldsSimpleJoinWithConditions($fields)
    {
        $driver = $this->getMock('\Moss\Storage\Driver\DriverInterface');

        $builder = $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface');
        $builder->expects($this->at(0))->method('reset');
        $builder->expects($this->at(1))->method('reset');
        $builder->expects($this->at(2))->method('select');
        $builder->expects($this->at(3))->method('field')->with('test_table.id', null);
        $builder->expects($this->at(4))->method('field')->with('test_table.text', 'alias');

        $bag = $this->mockModelBag('one', ['id' => ':foo'], ['id' => ':bar']);

        $query = new ReadQuery($driver, $builder, $bag, 'table');
        $query->reset()
            ->join('left', 'other')
            ->fields($fields);

    }

//
//    /**
//     * @dataProvider whereFieldValueProvider
//     */
//    public function testReadWithWhere($field, $value)
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->where($field, $value);
//
//    }
//
//    public function whereFieldValueProvider()
//    {
//        return [];
//    }
//
//    /**
//     * @expectedException \Moss\Storage\Query\QueryException
//     * @expectedExceptionMessage Query does not supports comparison operator
//     */
//    public function testInvalidWhereComparison()
//    {
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->where('id', 1, '!!');
//    }
//
//    /**
//     * @expectedException \Moss\Storage\Query\QueryException
//     * @expectedExceptionMessage Query does not supports logical operator
//     */
//    public function testInvalidWhereLogical()
//    {
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->where('id', 1, '=', ':foo');
//    }
//
//    /**
//     * @dataProvider havingFieldValueProvider
//     */
//    public function testReadWithHaving($field, $value)
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->count('id', 'count')
//            ->having($field, $value);
//
//    }
//
//    public function havingFieldValueProvider()
//    {
//        return [];
//    }
//
//    /**
//     * @expectedException \Moss\Storage\Query\QueryException
//     * @expectedExceptionMessage Query does not supports comparison operator
//     */
//    public function testInvalidHavingComparison()
//    {
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->having('id', 1, '!!');
//    }
//
//    /**
//     * @expectedException \Moss\Storage\Query\QueryException
//     * @expectedExceptionMessage Query does not supports logical operator
//     */
//    public function testInvalidHavingLogical()
//    {
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->having('id', 1, '=', 'foo');
//    }
//
//    /**
//     * @dataProvider joinProvider
//     */
//    public function testJoin($type, $join)
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag($type));
//        $query->reset()
//            ->read('table')
//            ->join($join, 'other')
//            ->field('table.id')
//            ->field('other.id');
//
//    }
//
//    /**
//     * @dataProvider joinProvider
//     */
//    public function testJoinAliases($type, $join)
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag($type));
//        $query->reset()
//            ->read('table');
//
//        call_user_func([$query, $join . 'Join'], 'other');
//
//        $query->field('table.id')
//            ->field('other.id');
//
//    }
//
//    public function joinProvider()
//    {
//        return [];
//    }
//
//    public function testReadGroup()
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->group('id');
//
//    }
//
//    /**
//     * @expectedException \Moss\Storage\Query\QueryException
//     * @expectedExceptionMessage Unsupported sorting method
//     */
//    public function testInvalidOrder()
//    {
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->order('foo', 'bar');
//    }
//
//    /**
//     * @dataProvider orderProvider
//     */
//    public function testReadOrder($order)
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->order('id', $order);
//
//    }
//
//    public function orderProvider()
//    {
//        return [];
//    }
//
//    /**
//     * @dataProvider limitProvider
//     */
//    public function testReadLimit($limit, $offset)
//    {
//        $this->markTestIncomplete();
//
//        $query = new ReadQuery($this->getMock('\Moss\Storage\Driver\DriverInterface'), $this->getMock('\Moss\Storage\Builder\QueryBuilderInterface'), $this->mockModelBag());
//        $query->reset()
//            ->read('table')
//            ->limit($limit, $offset);
//
//    }
//
//    public function limitProvider()
//    {
//        return [];
//    }
//
    protected function mockModelBag()
    {
        $table = new Model(
            '\stdClass',
            'test_table',
            [
                new Integer('id', ['auto_increment']),
                new String('text', ['length' => '128', 'null'], 'alias'),
            ],
            [
                new Primary(['id']),
            ]
        );

        $other = new Model(
            '\altClass',
            'test_other',
            [
                new Integer('id', ['auto_increment']),
                new String('text', ['length' => '128', 'null'], 'alias'),
            ],
            [
                new Primary(['id']),
            ]
        );

        $mediator = new Model(
            null,
            'test_mediator',
            [
                new Integer('in'),
                new Integer('out'),
            ],
            [
                new Primary(['in', 'out']),
            ]
        );

        $bag = new ModelBag();
        $bag->set($table, 'table');
        $bag->set($other, 'other');
        $bag->set($mediator, 'mediator');

        return $bag;
    }
}
