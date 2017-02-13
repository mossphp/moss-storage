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


class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testFireEventWithoutListeners()
    {
        $subject = new \stdClass();
        $subject->foo = 0;

        $dispatcher = new EventDispatcher();
        $dispatcher->fire('event', $subject);

        $this->assertEquals(0, $subject->foo);
    }

    public function testFireEventWithListeners()
    {
        $subject = new \stdClass();
        $subject->foo = 0;

        $dispatcher = new EventDispatcher();
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->fire('event', $subject);

        $this->assertEquals(4, $subject->foo);
    }

    public function testRegisterForMultiple()
    {
        $subject = new \stdClass();
        $subject->foo = 0;

        $dispatcher = new EventDispatcher();
        $dispatcher->register(['eventA', 'eventB'], function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->fire('eventA', $subject);
        $dispatcher->fire('eventB', $subject);

        $this->assertEquals(2, $subject->foo);
    }

    public function testRegisterWithPriority()
    {
        $subject = new \stdClass();
        $subject->foo = 0;

        $dispatcher = new EventDispatcher();
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->stop(); }, 1);
        $dispatcher->fire('event', $subject);

        $this->assertEquals(1, $subject->foo);
    }

    public function testStopPropagation()
    {
        $subject = new \stdClass();
        $subject->foo = 0;

        $dispatcher = new EventDispatcher();
        $dispatcher->register('event', function(Event $event) { $event->stop(); });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->register('event', function(Event $event) { $event->getSubject()->foo += 1; });
        $dispatcher->fire('event', $subject);

        $this->assertEquals(0, $subject->foo);
    }
}
