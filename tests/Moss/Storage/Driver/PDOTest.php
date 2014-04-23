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

    public function testAffectedRows()
    {
        $driver = new PDODriverMock($this->mockPDO(array('rowCount' => 10)));
        $result = $driver->prepare('SELECT foo, bar FROM table')
            ->execute()
            ->affectedRows();
        $this->assertEquals(10, $result);
    }

    public function testLastInsertId()
    {
        $driver = new PDODriverMock($this->mockPDO(array('lastInsertId' => 10)));
        $result = $driver->prepare('INSERT INTO table (foo, bar) VALUES (\'foo\', \'bar\')')
            ->execute()
            ->lastInsertId();
        $this->assertEquals(10, $result);
    }

    /**
     * @dataProvider objectProvider
     */
    public function testFetchObject($expected)
    {
        $driver = new PDODriverMock($this->mockPDO(array('result' => $expected)));
        $result = $driver->prepare('SELECT foo, bar FROM table')
            ->execute()
            ->fetchObject('\stdClass');

        $this->assertEquals($expected[0], $result);
    }

    /**
     * @dataProvider objectProvider
     */
    public function testFetchAllAsObject($expected)
    {
        $driver = new PDODriverMock($this->mockPDO(array('result' => $expected)));
        $result = $driver->prepare('SELECT foo, bar FROM table')
            ->execute()
            ->fetchAll();
        $this->assertEquals($expected, $result);
    }

    public function objectProvider()
    {
        $obj = new \stdClass();
        $obj->foo = 'foo';
        $obj->bar = 'bar';

        return array(
            array(array($obj)),
            array(array($obj))
        );
    }

    /**
     * @dataProvider assocProvider
     */
    public function testFetchAssoc($expected)
    {
        $driver = new PDODriverMock($this->mockPDO(array('result' => $expected)));
        $result = $driver->prepare('SELECT foo, bar FROM table')
            ->execute()
            ->fetchAssoc();

        $this->assertEquals($expected[0], $result);
    }

    public function testFetchAllAsAssoc()
    {
        $expected = $this->assocProvider();

        $driver = new PDODriverMock($this->mockPDO(array('result' => $expected)));
        $result = $driver->prepare('SELECT foo, bar FROM table')
            ->execute()
            ->fetchAll();

        $this->assertEquals($expected, $result);
    }

    public function assocProvider()
    {
        return array(
            array(array(array('foo' => 'foo', 'bar' => 'bar'))),
            array(array(array('foo' => 'foo', 'bar' => 'bar')))
        );
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testFetchField($expected)
    {
        $driver = new PDODriverMock($this->mockPDO(array('result' => $expected)));
        $result = $driver->prepare('SELECT foo, bar FROM table')
            ->execute()
            ->fetchField(1);

        $this->assertEquals($expected[0], $result);
    }

    public function fieldProvider()
    {
        return array(
            array(array('foo', 'bar')),
            array(array('foo', 'bar')),
        );
    }

    public function testTransactionStart()
    {
        $driver = new PDODriverMock($this->mockPDO());
        $driver->transactionStart();
        $this->assertTrue($driver->transactionCheck());
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Unable to start transaction, already started
     */
    public function testTransactionStartAlreadyStarted()
    {
        $driver = new PDODriverMock($this->mockPDO(array('transaction' => true)));
        $driver->transactionStart();
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Unable to commit, no transactions started
     */
    public function testTransactionCommitWithoutStart()
    {
        $driver = new PDODriverMock($this->mockPDO());
        $driver->transactionCommit();
    }

    public function testTransactionCommit()
    {
        $driver = new PDODriverMock($this->mockPDO(array('transaction' => true)));
        $driver->transactionCommit();
        $this->assertFalse($driver->transactionCheck());
    }

    /**
     * @expectedException \Moss\Storage\Driver\DriverException
     * @expectedExceptionMessage Unable to rollback, no transactions started
     */
    public function testTransactionRollbackWithoutStart()
    {
        $driver = new PDODriverMock($this->mockPDO());
        $driver->transactionRollback();
    }

    public function testTransactionRollback()
    {
        $driver = new PDODriverMock($this->mockPDO(array('transaction' => true)));
        $driver->transactionRollback();
        $this->assertFalse($driver->transactionCheck());
    }

    protected function mockPDO($args = array())
    {
        $args = array_merge(
            array(
                'queryString' => null,
                'transaction' => false,
                'rowCount' => 0,
                'lastInsertId' => null,
                'result' => array(),
            ),
            $args
        );

        $mock = $this->getMock('\Moss\Storage\Driver\PDOMock');

        $mock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($this->mockPDOStatement($args['queryString'], $args['rowCount'], $args['result'])));

        $mock->expects($this->any())
            ->method('lastInsertId')
            ->will($this->returnValue($args['lastInsertId']));

        $mock->expects($this->any())
            ->method('beginTransaction')
            ->will(
                $this->returnCallback(
                    function () use (&$args) {
                        if ($args['transaction']) {
                            throw new \PDOException();
                        }

                        return $args['transaction'] = true;
                    }
                )
            );

        $mock->expects($this->any())
            ->method('commit')
            ->will(
                $this->returnCallback(
                    function () use (&$args) {
                        if (!$args['transaction']) {
                            throw new \PDOException();
                        }

                        return $args['transaction'] = false;
                    }
                )
            );

        $mock->expects($this->any())
            ->method('rollBack')
            ->will(
                $this->returnCallback(
                    function () use (&$args) {
                        if (!$args['transaction']) {
                            throw new \PDOException();
                        }

                        return $args['transaction'] = false;
                    }
                )
            );

        $mock->expects($this->any())
            ->method('inTransaction')
            ->will($this->returnCallback(function () use (&$args) { return $args['transaction']; }));

        return $mock;
    }

    protected function mockPDOStatement($queryString = null, $rowCount = 0, $result = array())
    {
        $mock = $this->getMock('\PDOStatement');

        $i = 0;
        $callback = function () use (&$result, &$i) {
            if (isset($result[$i])) {
                return $result[$i++];
            }

            return false;
        };

        $mock->expects($this->any())
            ->method('rowCount')
            ->will($this->returnValue($rowCount));

        $mock->expects($this->any())
            ->method('fetchObject')
            ->will($this->returnCallback($callback));

        $mock->expects($this->any())
            ->method('fetch')
            ->will($this->returnCallback($callback));

        $mock->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnCallback($callback));

        return $mock;
    }
}
 