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

        return [
            [1, 1, 'boolean'],
            ['1', 1, 'boolean'],
            ['abc', 1, 'boolean'],
            [false, 0, 'boolean'],
            ['0', 0, 'boolean'],
            [0, 0, 'boolean'],

            [1, 1, 'integer'],
            ['1', 1, 'integer'],
            ['1.234', 1, 'integer'],
            ['1abc', 1, 'integer'],

            [1.234, 1.234, 'decimal'],
            ['1.234', 1.234, 'decimal'],
            ['1 , 234', 1.234, 'decimal'],

            ['123', '123', 'string'],
            ['abc', 'abc', 'string'],

            [$time->getTimestamp(), $time->getTimestamp(), 'datetime'],
            [$time->format('Y-m-d'), $time->format('Y-m-d'), 'datetime'],
            [$time, $time->format('Y-m-d H:i:s'), 'datetime'],

            [['1', '2'], base64_encode(serialize(['1', '2'])), 'serial'],
            [(object) ['1', '2'], base64_encode(serialize((object) ['1', '2'])), 'serial'],
        ];
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

        return [
            [1, true, 'boolean'],
            ['1', true, 'boolean'],
            ['abc', true, 'boolean'],
            [false, false, 'boolean'],
            ['0', false, 'boolean'],
            [0, false, 'boolean'],

            [1, 1, 'integer'],
            ['1', 1, 'integer'],
            ['1.234', 1, 'integer'],
            ['1abc', 1, 'integer'],

            [1.234, 1.234, 'decimal'],
            ['1.234', 1.234, 'decimal'],
            ['1 , 234', 1.234, 'decimal'],

            ['123', '123', 'string'],
            ['abc', 'abc', 'string'],

            [
                $timestamp->getTimestamp(),
                $timestamp,
                'datetime'
            ],
            [
                $time->format('Y-m-d H:i:s'),
                $time,
                'datetime'
            ],

            [base64_encode(serialize(['1', '2'])), ['1', '2'], 'serial'],
            [base64_encode(serialize((object) ['1', '2'])), (object) ['1', '2'], 'serial'],
        ];
    }
} 
