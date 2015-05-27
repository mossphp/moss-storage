<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query;


class StorageTest extends QueryMocks
{
    /**
     * @var \Doctrine\DBAL\Query\QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $builder;

    /**
     * @var \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dbal;

    /**
     * @var \Moss\Storage\Model\ModelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $model;

    /**
     * @var \Moss\Storage\Model\ModelBag|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bag;

    /**
     * @var \Moss\Storage\Query\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    public function setUp()
    {
        $this->builder = $this->mockQueryBuilder();
        $this->dbal = $this->mockDBAL($this->builder);
        $this->model = $this->mockModel('\\stdClass', 'table');
        $this->bag = $this->mockBag([$this->model]);
        $this->dispatcher = $this->mockEventDispatcher();
    }

    public function testConnection()
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertSame($this->dbal, $query->connection());
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testRead()
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertInstanceOf('\Moss\Storage\Query\ReadQueryInterface', $query->read('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testReadOne()
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertInstanceOf('\Moss\Storage\Query\ReadQueryInterface', $query->readOne('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testWrite($instance, $entity)
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertInstanceOf('\Moss\Storage\Query\WriteQueryInterface', $query->write($instance, $entity));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testInsert($instance, $entity)
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertInstanceOf('\Moss\Storage\Query\InsertQueryInterface', $query->insert($instance, $entity));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testUpdate($instance, $entity)
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertInstanceOf('\Moss\Storage\Query\UpdateQueryInterface', $query->update($instance, $entity));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testDelete($instance, $entity)
    {
        $query = new Storage($this->dbal, $this->bag, $this->dispatcher);
        $this->assertInstanceOf('\Moss\Storage\Query\DeleteQueryInterface', $query->delete($instance, $entity));
    }

    public function instanceProvider()
    {
        return [
            [new \stdClass(), '\\stdClass'],
            [new \stdClass(), null],
            [new \stdClass(), 'table']
        ];
    }
}
