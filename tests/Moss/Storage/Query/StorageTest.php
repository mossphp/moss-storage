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
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
        $this->assertSame($dbal, $query->connection());
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testRead()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\ReadQueryInterface', $query->read('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testReadOne()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\ReadQueryInterface', $query->readOne('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testWrite($instance, $entity)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\WriteQueryInterface', $query->write($instance, $entity));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testInsert($instance, $entity)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\InsertQueryInterface', $query->insert($instance, $entity));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testUpdate($instance, $entity)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\UpdateQueryInterface', $query->update($instance, $entity));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testDelete($instance, $entity)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $factory = $this->mockRelFactory();

        $query = new Storage($dbal, $bag, $factory);
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
