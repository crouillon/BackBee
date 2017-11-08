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

namespace BackBee\Session\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

use BackBee\Session\PdoSessionHandlerFactory;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class PdoSessionHandlerFactory
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Session\PdoSessionHandlerFactory
 */
class PdoSessionHandlerFactoryTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @covers ::__construct()
     * @covers ::createPdoHandler()
     */
    public function testCreatePdoHandler()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWrappedConnection'])
            ->getMock();

        $entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        $connection->expects($this->once())
            ->method('getWrappedConnection')
            ->willReturn('dsn string');

        $entityMng->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $handler = (new PdoSessionHandlerFactory($entityMng, []))->createPdoHandler();

        $this->assertInstanceOf(PdoSessionHandler::class, $handler);
        $this->assertEquals('dsn string', $this->invokeProperty($handler, 'dsn'));
        $this->assertEquals(PdoSessionHandler::LOCK_NONE, $this->invokeProperty($handler, 'lockMode'));
    }
}
