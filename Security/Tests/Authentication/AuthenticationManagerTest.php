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

namespace BackBee\Security\Tests\Authentication;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use BackBee\Security\Authentication\AuthenticationManager;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\BBUserToken;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class AuthenticationManager
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authentication\AuthenticationManager
 */
class AuthenticationManagerTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @var AuthenticationManager
     */
    private $manager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var AuthenticationProviderInterface
     */
    private $provider;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->manager = new AuthenticationManager([]);

        $this->dispatcher = $this->getMockForAbstractClass(
            EventDispatcherInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['dispatch']
        );

        $this->provider = $this->getMockForAbstractClass(
            AuthenticationProviderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['supports', 'authenticate']
        );
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $dispatcher = $this->getMockForAbstractClass(EventDispatcherInterface::class);

        $manager = new AuthenticationManager([], $dispatcher);

        $this->assertEquals($dispatcher, $this->invokeProperty($manager, 'eventDispatcher'));
    }

    /**
     * @covers ::setEventDispatcher()
     */
    public function testSetEventDisptacher()
    {
        $dispatcher = $this->getMockForAbstractClass(EventDispatcherInterface::class);

        $this->assertEquals($this->manager, $this->manager->setEventDispatcher($dispatcher));
        $this->assertEquals($dispatcher, $this->invokeProperty($this->manager, 'eventDispatcher'));
    }

    /**
     * @covers ::addProvider()
     */
    public function testAddProvider()
    {
        $provider = $this->getMockForAbstractClass(AuthenticationProviderInterface::class);

        $this->assertEquals($this->manager, $this->manager->addProvider($provider));
        $this->assertEquals([$provider], $this->invokeProperty($this->manager, 'providers'));
    }

    /**
     * @covers ::addProviders()
     */
    public function testAddProviders()
    {
        $provider1 = $this->getMockForAbstractClass(AuthenticationProviderInterface::class);
        $provider2 = $this->getMockForAbstractClass(AuthenticationProviderInterface::class);

        $this->assertEquals($this->manager, $this->manager->addProviders([$provider1, $provider2]));
        $this->assertEquals([$provider1, $provider2], $this->invokeProperty($this->manager, 'providers'));
    }

    /**
     * @covers            ::addProviders()
     * @expectedException \InvalidArgumentException
     */
    public function testAddInvalidProviders()
    {
        $this->manager->addProviders([new \stdClass()]);
    }

    /**
     * @covers            ::authenticate()
     */
    public function testAuthenticate()
    {
        $token = $this->getMockBuilder(BBUserToken::class)
            ->setMethods(['eraseCredentials'])
            ->getMock();
        $token->expects($this->once())->method('eraseCredentials');

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function ($name, $event) {
                \PHPUnit_Framework_Assert::assertEquals(AuthenticationEvents::AUTHENTICATION_SUCCESS, $name);
                \PHPUnit_Framework_Assert::assertInstanceOf(AuthenticationEvent::class, $event);
            }));

        $this->provider
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->provider
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn($token);

        $this->manager
            ->addProvider($this->provider)
            ->setEventDispatcher($this->dispatcher);

        $this->assertEquals($token, $this->manager->authenticate(new BBUserToken()));
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \Symfony\Component\Security\Core\Exception\AccountStatusException
     */
    public function testInvalidAccountAuthenticate()
    {
        $this->provider
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->provider
            ->expects($this->once())
            ->method('authenticate')
            ->willThrowException($this->getMockForAbstractClass(AccountStatusException::class));

        $this->manager
            ->addProvider($this->provider)
            ->authenticate(new BBUserToken());
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testInvalidAuthenticationAuthenticate()
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function ($name, $event) {
                \PHPUnit_Framework_Assert::assertEquals(AuthenticationEvents::AUTHENTICATION_FAILURE, $name);
                \PHPUnit_Framework_Assert::assertInstanceOf(AuthenticationFailureEvent::class, $event);
            }));

        $this->provider
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->provider
            ->expects($this->once())
            ->method('authenticate')
            ->willThrowException(new AuthenticationException(''));

        $this->manager
            ->addProvider($this->provider)
            ->setEventDispatcher($this->dispatcher)
            ->authenticate(new BBUserToken());
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testInvalidSecurityAuthenticate()
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function ($name, $event) {
                \PHPUnit_Framework_Assert::assertEquals(AuthenticationEvents::AUTHENTICATION_FAILURE, $name);
                \PHPUnit_Framework_Assert::assertInstanceOf(AuthenticationFailureEvent::class, $event);
            }));

        $this->provider
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->provider
            ->expects($this->once())
            ->method('authenticate')
            ->willThrowException(new SecurityException(''));

        $this->manager
            ->addProvider($this->provider)
            ->setEventDispatcher($this->dispatcher)
            ->authenticate(new BBUserToken());
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \Symfony\Component\Security\Core\Exception\ProviderNotFoundException
     */
    public function testInvalidProviderAuthenticate()
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function ($name, $event) {
                \PHPUnit_Framework_Assert::assertEquals(AuthenticationEvents::AUTHENTICATION_FAILURE, $name);
                \PHPUnit_Framework_Assert::assertInstanceOf(AuthenticationFailureEvent::class, $event);
            }));

        $this->provider
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $this->manager
            ->addProvider($this->provider)
            ->setEventDispatcher($this->dispatcher)
            ->authenticate(new BBUserToken());
    }
}
