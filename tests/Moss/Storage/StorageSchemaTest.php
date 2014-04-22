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


class StorageSchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testCheck()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
        $query = $storage->check();

        $this->assertInstanceOf('Moss\Storage\Schema\Schema', $query);
        $this->assertAttributeEquals('check', 'operation', $query);
    }

    public function testCreate()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
        $query = $storage->create();

        $this->assertInstanceOf('Moss\Storage\Schema\Schema', $query);
        $this->assertAttributeEquals('create', 'operation', $query);
    }

    public function testAlter()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
        $query = $storage->alter();

        $this->assertInstanceOf('Moss\Storage\Schema\Schema', $query);
        $this->assertAttributeEquals('alter', 'operation', $query);
    }

    public function testDrop()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
        $query = $storage->drop();

        $this->assertInstanceOf('Moss\Storage\Schema\Schema', $query);
        $this->assertAttributeEquals('drop', 'operation', $query);
    }

    public function testTransactionStart()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
        $storage->transactionStart();
        $this->assertTrue($storage->transactionCheck());
    }

    public function testTransactionCommit()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
        $storage->transactionStart();
        $this->assertTrue($storage->transactionCheck());
        $storage->transactionCommit();
        $this->assertFalse($storage->transactionCheck());
    }

    public function testTransactionRollback()
    {
        $storage = new StorageSchema($this->mockDriver(), $this->mockBuilder());
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
        $mock = $this->getMock('Moss\Storage\Builder\SchemaBuilderInterface');

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
            ->method('primaryFields')
            ->will($this->returnValue(array()));

        $mock->expects($this->any())
            ->method('fields')
            ->will($this->returnValue(array()));

        return $mock;
    }
}
 