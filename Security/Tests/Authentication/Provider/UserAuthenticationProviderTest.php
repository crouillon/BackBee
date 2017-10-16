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

namespace BackBee\Security\Tests\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\Authentication\Provider\UserAuthenticationProvider;
use BackBee\Security\Repository\UserRepository;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class UserAuthenticationProvider
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authentication\Provider\UserAuthenticationProvider
 */
class UserAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var UserAuthenticationProvider
     */
    private $provider;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoder;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userProvider = $this->getMockForAbstractClass(
            UserProviderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['loadUserByUsername']
        );
        $this->encoder = $this->getMockForAbstractClass(
            EncoderFactoryInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getEncoder']
        );
        $this->provider = new UserAuthenticationProvider($this->userProvider, $this->encoder, 'providerKey');
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $userProvider = $this->getMockBuilder(UserRepository::class)
                ->disableOriginalConstructor()
                ->setMethods(['checkPreAuth'])
                ->getMock();

        $userProvider->expects($this->once())->method('checkPreAuth')->willThrowException(new \Exception());
        $provider = $this->getMockBuilder(UserAuthenticationProvider::class)
                ->setConstructorArgs([$userProvider, null, 'providerKey'])
                ->setMethods(['retrieveUser'])
                ->getMock();

        $provider->expects($this->once())
                ->method('retrieveUser')
                ->willReturn($this->getMockForAbstractClass(UserInterface::class));

        try {
            $provider->authenticate(new UsernamePasswordToken('user', 'credentials', 'providerKey'));
        } catch (\Exception $ex) {
        }
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testAuthenticate()
    {
        $this->userProvider->expects($this->once())->method('loadUserByUsername');
        $this->provider->authenticate(new UsernamePasswordToken('user', 'credentials', 'providerKey'));
    }

    /**
     * @covers ::retrieveUser()
     */
    public function testRetrieveUser()
    {
        $user = $this->getMockForAbstractClass(UserInterface::class);
        $this->userProvider->expects($this->once())->method('loadUserByUsername')->willReturn($user);

        $this->assertEquals($user, $this->invokeMethod(
            $this->provider,
            'retrieveUser',
            ['username', new UsernamePasswordToken('user', 'credentials', 'providerKey')]
        ));
    }

    /**
     * @covers                   ::retrieveUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage Unknown user with username `username`.
     */
    public function testRetrieveInvalidUser()
    {
        $this->userProvider->expects($this->once())->method('loadUserByUsername');

        $this->invokeMethod(
            $this->provider,
            'retrieveUser',
            ['username', new UsernamePasswordToken('user', 'credentials', 'providerKey')]
        );
    }

    /**
     * @covers ::checkAuthentication()
     * @covers ::checkAuthenticationWithEncoder()
     * @covers ::checkAuthenticationWithoutEncoder()
     */
    public function testCheckAuthentication()
    {
        $user = $this->getMockForAbstractClass(UserInterface::class, [], '', false, false, true, ['getPassword']);
        $token = new UsernamePasswordToken('user', 'credentials', 'providerKey');

        $pEncoder = $this->getMockForAbstractClass(
            PasswordEncoderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isPasswordValid']
        );
        $pEncoder->expects($this->once())->method('isPasswordValid')->willReturn(true);
        $this->encoder->expects($this->once())->method('getEncoder')->willReturn($pEncoder);

        $this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]);

        $this->invokeProperty($this->provider, 'encoderFactory', 0);
        $user->expects($this->once())->method('getPassword')->willReturn('credentials');
        $this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]);
    }

    /**
     * @covers            ::checkAuthentication()
     * @covers            ::checkAuthenticationWithEncoder()
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckInvalidAuthenticationWithEncoder()
    {
        $user = $this->getMockForAbstractClass(UserInterface::class);
        $token = new UsernamePasswordToken('user', 'credentials', 'providerKey');

        $pEncoder = $this->getMockForAbstractClass(
            PasswordEncoderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isPasswordValid']
        );
        $pEncoder->expects($this->once())->method('isPasswordValid')->willReturn(false);
        $this->encoder->expects($this->once())->method('getEncoder')->willReturn($pEncoder);

        $this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]);
    }

    /**
     * @covers            ::checkAuthentication()
     * @covers            ::checkAuthenticationWithoutEncoder()
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckInvalidAuthenticationWithoutEncoder()
    {
        $user = $this->getMockForAbstractClass(UserInterface::class, [], '', false, false, true, ['getPassword']);
        $token = new UsernamePasswordToken('user', 'credentials', 'providerKey');

        $this->invokeProperty($this->provider, 'encoderFactory', 0);
        $user->expects($this->once())->method('getPassword')->willReturn('different');
        $this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]);
    }
}
