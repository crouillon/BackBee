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

namespace BackBee\Security\Tests\Context;

use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;

use BackBee\Security\Context\LogoutContext;
use BackBee\Security\Listeners\LogoutListener;
use BackBee\Security\SecurityContext;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class LogoutContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Context\LogoutContext
 */
class LogoutContextTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @var LogoutContext
     */
    private $context;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLogoutListener', 'setLogoutListener'])
            ->getMock();

        $this->context = new LogoutContext($this->securityContext);
    }

    /**
     * @covers ::loadListeners()
     * @covers ::initLogoutListener()
     */
    public function testLoadListeners()
    {
        $config = [
            'logout' => [
                'handlers' => []
            ]
        ];

        $this->securityContext
            ->expects($this->once())
            ->method('setLogoutListener')
            ->will($this->returnCallback(function ($listener) {
                \PHPUnit_Framework_Assert::assertInstanceOf(
                    LogoutListener::class,
                    $listener
                );
            }));

        $this->assertEquals([], $this->context->loadListeners($config));
    }

    /**
     * @covers ::setHandlers()
     */
    public function testSetHandlers()
    {
        $listener = $this->getMockBuilder(LogoutListener::class)
                ->disableOriginalConstructor()
                ->setMethods(['addHandler'])
                ->getMock();

        $this->securityContext
            ->expects($this->once())
            ->method('getLogoutListener')
            ->willReturn($listener);

        $listener->expects($this->once())
            ->method('addHandler')
            ->will($this->returnCallback(function ($handler) {
                \PHPUnit_Framework_Assert::assertInstanceOf(
                    SessionLogoutHandler::class,
                    $handler
                );
            }));

        $this->context->setHandlers([SessionLogoutHandler::class]);
    }
}
