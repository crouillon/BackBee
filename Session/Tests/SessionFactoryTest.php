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

namespace BackBee\Session\Tests;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

use BackBee\Config\Config;
use BackBee\Session\SessionFactory;

/**
 * Test suite for class SessionFactory
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Session\SessionFactory
 */
class SessionFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct()
     * @covers ::createSession()
     */
    public function testCreateSession()
    {
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSessionConfig'])
            ->getMock();

        $config->expects($this->any())
            ->method('getSessionConfig')
            ->willReturn(['session config']);

        $storage = $this->getMockBuilder(NativeSessionStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOptions', 'setSaveHandler', 'start'])
            ->getMock();

        $handler = $this->getMockForAbstractClass('SessionHandlerInterface');

        $factory = new SessionFactory($config, $storage, $handler);

        $storage->expects($this->once())->method('setOptions')->with(['session config']);
        $storage->expects($this->once())->method('setSaveHandler')->with($handler);
        $storage->expects($this->once())->method('start');

        $this->assertInstanceOf(Session::class, $factory->createSession());
    }
}
