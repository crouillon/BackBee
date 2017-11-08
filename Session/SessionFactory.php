<?php

/*
 * Copyright (c) 2011-2017 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Session;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

use BackBee\Config\Config;

/**
 * Allow to configure the session regarding the environment.
 *
 * @author Mickaël Andrieu
 */
class SessionFactory
{
    /**
     * An array of session storage options.
     *
     * @var array
     */
    protected $sessionConfig;

    /**
     * A session storage.
     *
     * @var SessionStorageInterface
     */
    protected $sessionStorage;

    /**
     * A session handler.
     *
     * @var \SessionHandlerInterface
     */
    protected $sessionHandler;

    /**
     * Factory construtor.
     *
     * @param Config                        $config         A BackBee config instance.
     * @param SessionStorageInterface       $sessionStorage A session storage
     * @param \SessionHandlerInterface|null $sessionHandler An optional session handler.
     */
    public function __construct(
        Config $config,
        SessionStorageInterface $sessionStorage,
        \SessionHandlerInterface $sessionHandler = null
    ) {
        $this->sessionConfig = is_array($config->getSessionConfig()) ? $config->getSessionConfig() : [];
        $this->sessionStorage = $sessionStorage;
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * Creates and start a new session.
     *
     * @return Session
     */
    public function createSession()
    {
        if ($this->sessionStorage instanceof NativeSessionStorage) {
            $this->sessionStorage->setOptions($this->sessionConfig);

            if ($this->sessionHandler !== null) {
                $this->sessionStorage->setSaveHandler($this->sessionHandler);
            }
        }

        $session = new Session($this->sessionStorage);
        $session->start();

        return $session;
    }
}
