<?php

/*
 * This file is part of the Moss micro-framework
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Query\EventDispatcher;

/**
 * Event dispatcher
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array
     */
    private $events = [];

    /**
     * Adds listener to single event or array of events
     *
     * @param string|array $event
     * @param callable     $listener
     * @param null|int     $priority
     *
     * @return $this
     */
    public function register($event, callable $listener, $priority = null)
    {
        foreach ((array) $event as $e) {
            $this->registerListener($e, $listener, $priority);
        }

        return $this;
    }

    /**
     * Register listener to event
     *
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     */
    private function registerListener($event, callable $listener, $priority)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        if ($priority === null) {
            $this->events[$event][] = $listener;

            return;
        }

        array_splice($this->events[$event], (int) $priority, 0, [$listener]);
    }

    /**
     * Fires event
     *
     * @param string $eventName
     * @param mixed  $subject
     *
     * @return mixed
     * @throws \Exception
     */
    public function fire($eventName, $subject = null)
    {
        if (!isset($this->events[$eventName])) {
            return $subject;
        }

        $event = new Event($eventName, $subject);

        foreach ($this->events[$eventName] as $listener) {
            if ($event->isStopped()) {
                break;
            }

            $listener($event);
        }

        return $event->getSubject();
    }
}
