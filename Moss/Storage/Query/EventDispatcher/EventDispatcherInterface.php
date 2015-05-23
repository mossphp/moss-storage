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
 * Event dispatcher interface
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
interface EventDispatcherInterface
{
    /**
     * Adds listener to single event or array of events
     *
     * @param string|array $event
     * @param callable     $listener
     * @param null|int     $priority
     *
     * @return $this
     */
    public function register($event, callable $listener, $priority = null);

    /**
     * Fires event
     *
     * @param string $eventName
     * @param mixed  $subject
     *
     * @return mixed
     * @throws \Exception
     */
    public function fire($eventName, $subject = null);
}
