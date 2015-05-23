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

/**
 * Event for event dispatcher
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
final class Event
{
    private $event;
    private $stop = false;
    private $subject;

    /**
     * Constructor
     *
     * @param string $event
     * @param mixed $subject
     */
    public function __construct($event, $subject)
    {
        $this->event = $event;
        $this->subject = $subject;
    }

    /**
     * Returns event name
     *
     * @return string
     */
    public function event()
    {
        return $this->event;
    }

    /**
     * Stops event propagation
     */
    public function stop()
    {
        $this->stop = true;
    }

    /**
     * Returns true if event propagation
     *
     * @return mixed
     */
    public function isStopped()
    {
        return $this->stop;
    }

    /**
     * Returns subject instance
     *
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets subject
     *
     * @param mixed $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
}
