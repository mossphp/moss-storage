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


class QueryTest extends QueryMocks
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertSame($dbal, $query->connection());
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testCount()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\CountInterface', $query->count('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testRead()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\ReadInterface', $query->read('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testReadOne()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\ReadInterface', $query->readOne('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testClear()
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\ClearInterface', $query->clear('\\stdClass'));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testWrite($entity, $instance)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\WriteInterface', $query->write($entity, $instance));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testInsert($entity, $instance)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\InsertInterface', $query->insert($entity, $instance));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testUpdate($entity, $instance)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\UpdateInterface', $query->update($entity, $instance));
    }

    /**
     * @dataProvider instanceProvider
     */
    public function testDelete($entity, $instance)
    {
        $dbal = $this->mockDBAL();
        $model = $this->mockModel('\\stdClass', 'table');
        $bag = $this->mockBag([$model]);
        $converter = $this->mockConverter();
        $factory = $this->mockRelFactory();

        $query = new Query($dbal, $bag, $converter, $factory);
        $this->assertInstanceOf('\Moss\Storage\Query\DeleteInterface', $query->delete($entity, $instance));
    }

    public function instanceProvider()
    {
        return [
            ['\\stdClass', new \stdClass()],
            [new \stdClass(), null],
            ['table', new \stdClass()]
        ];
    }
}
