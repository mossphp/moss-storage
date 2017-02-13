<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\EventDispatcher;


class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $subject = new \stdClass();
        $event = new Event('eventName', $subject);
        $this->assertEquals('eventName', $event->event());
    }

    public function testStop()
    {
        $subject = new \stdClass();
        $event = new Event('eventName', $subject);
        $this->assertFalse($event->isStopped());

        $event->stop();
        $this->assertTrue($event->isStopped());
    }

    public function testSubject()
    {
        $subject = new \stdClass();
        $event = new Event('eventName', $subject);
        $this->assertSame($subject, $event->getSubject());

        $altSubject = new \stdClass();
        $event->setSubject($altSubject);
        $this->assertSame($altSubject, $event->getSubject());
    }
}
