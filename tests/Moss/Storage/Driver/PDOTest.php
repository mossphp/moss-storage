<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Driver;

class PDODriverMock extends \Moss\Storage\Driver\PDO
{
    public function __construct(\PDO $connection)
    {
        $this->pdo = $connection;
    }
}

class PDOMock extends \PDO
{
    public function __construct() { }
}

class PDOTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage No statement to execute
     */
    public function testExecuteWithoutPrepare()
    {
        $mock = $this->getMock('Moss\Storage\Driver\PDOMock');

        $driver = new PDODriverMock($mock);
        $driver->execute();
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Missing query string or query string is empty
     */
    public function testExecuteFails()
    {
        $mock = $this->getMock('Moss\Storage\Driver\PDOMock');

        $driver = new PDODriverMock($mock);
        $driver->prepare('');
    }

    public function testAffectedRows()
    {
        $stmt = $this->getMock('\PDOStatement');
        $stmt->expects($this->once())
            ->method('rowCount')
            ->will($this->returnValue(10));

        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $driver = new PDODriverMock($pdo);
        $rows = $driver->prepare('SELECT * FROM table')
            ->execute()
            ->affectedRows();

        $this->assertEquals(10, $rows);
    }

    public function testLastInsertId()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($this->getMock('\PDOStatement')));

        $pdo->expects($this->once())
            ->method('lastInsertId')
            ->will($this->returnValue(1));

        $driver = new PDODriverMock($pdo);
        $rows = $driver->prepare('INSERT INTO table (id, text) VALUES (1, foo)')
            ->execute()
            ->lastInsertId();

        $this->assertEquals(1, $rows);
    }

    public function testFetchObject()
    {
        $obj = new \stdClass();
        $obj->id = 1;
        $obj->text = 'foo';

        $stmt = $this->getMock('\PDOStatement');
        $stmt->expects($this->once())
            ->method('fetchObject')
            ->will($this->returnValue($obj));

        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $driver = new PDODriverMock($pdo);
        $result = $driver->prepare('SELECT * FROM table')
            ->execute()
            ->fetchObject('\stdClass');

        $this->assertEquals($obj, $result);
    }

    public function testFetchAllAsObject()
    {
        $obj1 = new \stdClass();
        $obj1->id = 1;
        $obj1->text = 'foo';

        $obj2 = new \stdClass();
        $obj2->id = 2;
        $obj2->text = 'bar';

        $stmt = $this->getMock('\PDOStatement');
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array($obj1, $obj2)));

        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $driver = new PDODriverMock($pdo);
        $result = $driver->prepare('SELECT * FROM table')
            ->execute()
            ->fetchAll('\stdClass');

        $this->assertEquals(array($obj1, $obj2), $result);
    }

    public function testFetchAssoc()
    {
        $row = array('id' => 1, 'text' => 'foo');

        $stmt = $this->getMock('\PDOStatement');
        $stmt->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($row));

        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $driver = new PDODriverMock($pdo);
        $result = $driver->prepare('SELECT * FROM table')
            ->execute()
            ->fetchAssoc();

        $this->assertEquals($row, $result);
    }

    public function testFetchAllAsAssoc()
    {
        $row1 = array('id' => 1, 'text' => 'foo');
        $row2 = array('id' => 2, 'text' => 'bar');

        $stmt = $this->getMock('\PDOStatement');
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array($row1, $row2)));

        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $driver = new PDODriverMock($pdo);
        $result = $driver->prepare('SELECT * FROM table')
            ->execute()
            ->fetchAll();

        $this->assertEquals(array($row1, $row2), $result);
    }

    public function testFetchField()
    {
        $row = array(1, 'foo');

        $stmt = $this->getMock('\PDOStatement');
        $stmt->expects($this->once())
            ->method('fetchColumn')
            ->will(
                $this->returnCallback(
                    function ($fieldNum) use ($row) {
                        return $row[$fieldNum];
                    }
                )
            );

        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $driver = new PDODriverMock($pdo);
        $result = $driver->prepare('SELECT * FROM table')
            ->execute()
            ->fetchField(1);

        $this->assertEquals('foo', $result);
    }

    public function testTransactionStart()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(false));

        $pdo->expects($this->once())
            ->method('beginTransaction');

        $driver = new PDODriverMock($pdo);
        $driver->transactionStart();
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Unable to start transaction, already started
     */
    public function testTransactionStartAlreadyStarted()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(true));

        $driver = new PDODriverMock($pdo);
        $driver->transactionStart();
    }

    public function testTransactionCommit()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(true));

        $pdo->expects($this->once())
            ->method('commit');

        $driver = new PDODriverMock($pdo);
        $driver->transactionCommit();
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Unable to commit, no transactions started
     */
    public function testTransactionCommitWithoutStart()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(false));

        $driver = new PDODriverMock($pdo);
        $driver->transactionCommit();
    }

    public function testTransactionRollback()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(true));

        $pdo->expects($this->once())
            ->method('rollback');

        $driver = new PDODriverMock($pdo);
        $driver->transactionRollback();
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Unable to rollback, no transactions started
     */
    public function testTransactionRollbackWithoutStart()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(false));

        $driver = new PDODriverMock($pdo);
        $driver->transactionRollback();
    }

    public function testReset()
    {
        $pdo = $this->getMock('Moss\Storage\Driver\PDOMock');
        $pdo->expects($this->exactly(2))
            ->method('inTransaction')
            ->will($this->returnValue(true));

        $pdo->expects($this->once())
            ->method('rollback');

        $driver = new PDODriverMock($pdo);
        $driver->reset();
    }
}
 