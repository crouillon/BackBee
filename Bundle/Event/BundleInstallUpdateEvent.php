<?php

/*
 * Copyright (c) 2011-2018 Lp digital system
 *
 * This file is part of BackBee CMS.
 *
 * BackBee CMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee CMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee CMS. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Bundle\Event;

use BackBee\Bundle\BundleInterface;

/**
 * Event dispatch on bundle install or update.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class BundleInstallUpdateEvent extends AbstractBundleEvent
{

    /**
     * Is the force attribute activated?
     *
     * @var boolean
     */
    private $forced;

    /**
     * Log stack;
     *
     * @var string[]
     */
    private $logs;

    /**
     * BundleInstallUpdateEvent creator.
     *
     * @param BundleInterface $target    The bundle being installed or updated.
     * @param mixed|null      $eventArgs Optional, event arguments.
     */
    public function __construct($target, $eventArgs = null)
    {
        parent::__construct($target, $eventArgs);

        if (!is_array($eventArgs)) {
            $eventArgs = [$eventArgs];
        }

        $this->forced = isset($eventArgs['force']) ? (boolean) $eventArgs['force'] : false;
        $this->logs = isset($eventArgs['logs']) ? (array) $eventArgs['logs'] : [];
    }

    /**
     * Returns true if current update action is forced, else false.
     *
     * @return boolean
     */
    public function isForced()
    {
        return $this->forced;
    }

    /**
     * Adds new message to logs.
     *
     * @param  string $key               The key log.
     * @param  string $message           The message log.
     *
     * @return BundleInstallUpdateEvent  The current event instance.
     *
     * @throws \InvalidArgumentException if message argument is not type of string
     */
    public function addLog($key, $message)
    {
        if (!is_string($message)) {
            throw new \InvalidArgumentException(sprintf(
                '[%s]: "message" must be type of string, %s given.',
                __METHOD__,
                gettype($message)
            ));
        }

        if (!isset($this->logs[$key])) {
            $this->logs[$key] = [];
        }

        $this->logs[$key][] = $message;

        return $this;
    }

    /**
     * Returns bundle update logs.
     *
     * @return string[]
     */
    public function getLogs()
    {
        return $this->logs;
    }
}
