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

namespace BackBee\Security\Tests\Listeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Listeners\PublicKeyAuthenticationListener;
use BackBee\Security\Token\PublicKeyToken;

/**
 * Test suite for class PublicKeyAuthenticationListener
 *
 * @author Charles Rouillon <charles.rouilon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Listeners\PublicKeyAuthenticationListener
 */
class PublicKeyAuthenticationListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManaggerInterface
     */
    private $authManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetResponseEvent
     */
    private $event;

    /**
     * @var PublicKeyAuthenticationListener
     */
    private $listener;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this->getMockForAbstractClass(
            TokenStorageInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['setToken']
        );
        $this->authManager = $this->getMockForAbstractClass(
            AuthenticationManagerInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['authenticate']
        );
        $this->logger = $this->getMockForAbstractClass(
            LoggerInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['info', 'error']
        );
        $this->event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();
        $this->listener = new PublicKeyAuthenticationListener($this->tokenStorage, $this->authManager, $this->logger);

        $request = Request::createFromGlobals();
        $request->headers->set(PublicKeyAuthenticationListener::AUTH_PUBLIC_KEY_TOKEN, 'public-key');
        $request->headers->set(PublicKeyAuthenticationListener::AUTH_SIGNATURE_TOKEN, 'signature');

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
    }

    /**
     * @covers ::handle()
     */
    public function testHandle()
    {
        $token = new PublicKeyToken();
        $this->authManager
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn($token);

        $this->tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->will($this->returnCallback(function ($token) {
                        \PHPUnit_Framework_Assert::assertInstanceOf(PublicKeyToken::class, $token);
            }));

        $this->logger->expects($this->once())->method('info');

        $this->listener->handle($this->event);
    }

    /**
     * @covers            ::handle()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testHandleThrowsSecurityException()
    {
        $this->authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new SecurityException()));

        $this->logger->expects($this->once())->method('info');

        $this->listener->handle($this->event);
    }

    /**
     * @covers            ::handle()
     * @expectedException \Exception
     */
    public function testHandleThrowsException()
    {
        $this->authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new \Exception()));

        $this->logger->expects($this->once())->method('error');

        $this->listener->handle($this->event);
    }
}
