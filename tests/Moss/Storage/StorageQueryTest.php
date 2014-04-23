<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage;


use Moss\Storage\Driver\DriverException;

class StorageQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDriver()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $this->assertInstanceOf('\Moss\Storage\Driver\DriverInterface', $storage->getDriver());
    }

    public function testGetModels()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $this->assertInstanceOf('\Moss\Storage\Model\ModelBag', $storage->getModels());
    }

    public function testGetBuilder()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $this->assertInstanceOf('\Moss\Storage\Builder\QueryBuilderInterface', $storage->getBuilder());
    }

    public function testNum()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->num('entity');

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('num', 'operation', $query);
    }

    public function testReadOne()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->readOne('entity');

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('readOne', 'operation', $query);
    }

    public function testRead()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->read('entity');

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('read', 'operation', $query);
    }

    public function testWriteInsert()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->write(new \stdClass());

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('insert', 'operation', $query);
    }

    public function testWriteUpdate()
    {
        $storage = new StorageQuery($this->mockDriver(1), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->write(new \stdClass());

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('update', 'operation', $query);
    }

    public function testInsert()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->insert(new \stdClass());

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('insert', 'operation', $query);
    }

    public function testUpdate()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->update(new \stdClass());

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('update', 'operation', $query);
    }

    public function testDelete()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->delete(new \stdClass());

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('delete', 'operation', $query);
    }

    public function testClear()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->register($this->mockModel(), 'entity');
        $query = $storage->clear('entity');

        $this->assertInstanceOf('Moss\Storage\Query\Query', $query);
        $this->assertAttributeEquals('clear', 'operation', $query);
    }

    public function testTransactionStart()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->transactionStart();
        $this->assertTrue($storage->transactionCheck());
    }

    public function testTransactionCommit()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->transactionStart();
        $this->assertTrue($storage->transactionCheck());
        $storage->transactionCommit();
        $this->assertFalse($storage->transactionCheck());
    }

    public function testTransactionRollback()
    {
        $storage = new StorageQuery($this->mockDriver(), $this->mockBuilder());
        $storage->transactionStart();
        $this->assertTrue($storage->transactionCheck());
        $storage->transactionRollback();
        $this->assertFalse($storage->transactionCheck());
    }

    protected function mockDriver($affectedRows = 0, $transaction = false)
    {
        $mock = $this->getMock('Moss\Storage\Driver\DriverInterface');

        $mock->expects($this->any())
            ->method('prepare')
            ->will($this->returnSelf());

        $mock->expects($this->any())
            ->method('execute')
            ->will($this->returnSelf());

        $mock->expects($this->any())
            ->method('affectedRows')
            ->will($this->returnValue($affectedRows));

        $mock->expects($this->any())
            ->method('transactionStart')
            ->will(
                $this->returnCallback(
                    function () use (&$transaction) {
                        if ($transaction) {
                            throw new DriverException();
                        }

                        return $transaction = true;
                    }
                )
            );

        $mock->expects($this->any())
            ->method('transactionCommit')
            ->will(
                $this->returnCallback(
                    function () use (&$transaction) {
                        if (!$transaction) {
                            throw new DriverException();
                        }

                        return $transaction = false;
                    }
                )
            );

        $mock->expects($this->any())
            ->method('transactionRollback')
            ->will(
                $this->returnCallback(
                    function () use (&$transaction) {
                        if (!$transaction) {
                            throw new DriverException();
                        }

                        return $transaction = false;
                    }
                )
            );

        $mock->expects($this->any())
            ->method('transactionCheck')
            ->will($this->returnCallback(function () use (&$transaction) { return $transaction; }));

        return $mock;
    }

    protected function mockBuilder()
    {
        $mock = $this->getMock('Moss\Storage\Builder\QueryBuilderInterface');

        $mock->expects($this->any())
            ->method('reset')
            ->will($this->returnSelf());

        $mock->expects($this->any())
            ->method('select')
            ->will($this->returnSelf());

        return $mock;
    }

    protected function mockModel()
    {
        $mock = $this->getMock('Moss\Storage\Model\ModelInterface');

        $mock->expects($this->any())
            ->method('entity')
            ->will($this->returnValue('stdClass'));

        $mock->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('entity'));

        $mock->expects($this->any())
            ->method('primaryFields')
            ->will($this->returnValue(array()));

        $mock->expects($this->any())
            ->method('fields')
            ->will($this->returnValue(array()));

        return $mock;
    }
}
 