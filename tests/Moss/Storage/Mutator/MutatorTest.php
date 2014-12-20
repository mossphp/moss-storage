<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Mutator;


class MutatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider storeProvider
     */
    public function testStore($value, $expected, $type)
    {
        $driver = new Mutator();
        $this->assertEquals($expected, $driver->store($value, $type));
    }

    public function storeProvider()
    {
        $time = new \DateTime();

        return array(
            array(1, 1, 'boolean'),
            array('1', 1, 'boolean'),
            array('abc', 1, 'boolean'),
            array(false, 0, 'boolean'),
            array('0', 0, 'boolean'),
            array(0, 0, 'boolean'),

            array(1, 1, 'integer'),
            array('1', 1, 'integer'),
            array('1.234', 1, 'integer'),
            array('1abc', 1, 'integer'),

            array(1.234, 1.234, 'decimal'),
            array('1.234', 1.234, 'decimal'),
            array('1 , 234', 1.234, 'decimal'),

            array('123', '123', 'string'),
            array('abc', 'abc', 'string'),

            array($time->getTimestamp(), $time->getTimestamp(), 'datetime'),
            array($time->format('Y-m-d'), $time->format('Y-m-d'), 'datetime'),
            array($time, $time->format('Y-m-d H:i:s'), 'datetime'),

            array(array('1', '2'), base64_encode(serialize(array('1', '2'))), 'serial'),
            array((object) array('1', '2'), base64_encode(serialize((object) array('1', '2'))), 'serial'),
        );
    }

    /**
     * @dataProvider restoreProvider
     */
    public function testRestore($value, $expected, $type)
    {
        $driver = new Mutator();
        $this->assertEquals($expected, $driver->restore($value, $type));
    }

    public function restoreProvider()
    {
        $time = new \DateTime();
        $timestamp = new \DateTime('@' . time());

        return array(
            array(1, true, 'boolean'),
            array('1', true, 'boolean'),
            array('abc', true, 'boolean'),
            array(false, false, 'boolean'),
            array('0', false, 'boolean'),
            array(0, false, 'boolean'),

            array(1, 1, 'integer'),
            array('1', 1, 'integer'),
            array('1.234', 1, 'integer'),
            array('1abc', 1, 'integer'),

            array(1.234, 1.234, 'decimal'),
            array('1.234', 1.234, 'decimal'),
            array('1 , 234', 1.234, 'decimal'),

            array('123', '123', 'string'),
            array('abc', 'abc', 'string'),

            array(
                $timestamp->getTimestamp(),
                $timestamp,
                'datetime'
            ),
            array(
                $time->format('Y-m-d H:i:s'),
                $time,
                'datetime'
            ),

            array(base64_encode(serialize(array('1', '2'))), array('1', '2'), 'serial'),
            array(base64_encode(serialize((object) array('1', '2'))), (object) array('1', '2'), 'serial'),
        );
    }
} 
