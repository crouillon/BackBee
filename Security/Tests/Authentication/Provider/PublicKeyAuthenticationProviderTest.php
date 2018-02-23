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

namespace BackBee\Security\Tests\Authentication\Provider;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\Authentication\Provider\PublicKeyAuthenticationProvider;
use BackBee\Security\Encoder\RequestSignatureEncoder;
use BackBee\Security\Token\PublicKeyToken;
use BackBee\Security\User;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for the class PublicKeyAuthenticationProvider
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authentication\Provider\PublicKeyAuthenticationProvider
 */
class PublicKeyAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;
    use InvokeMethodTrait;

    /**
     * @var PublicKeyAuthenticationProvider
     */
    private $provider;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        vfsStream::setup('root', 0777, []);

        $this->userProvider = $this->getMockForAbstractClass(
            UserProviderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['loadUserByPublicKey']
        );
        $this->provider = new PublicKeyAuthenticationProvider($this->userProvider, vfsStream::url('root').'/nonce/dir');
    }

    /**
     * @covers ::authenticate()
     */
    public function testAuthenticate()
    {
        $provider = $this->getMockBuilder(PublicKeyAuthenticationProvider::class)
            ->setMethods(['readNonceValue', 'writeNonceValue'])
            ->setConstructorArgs([$this->userProvider, vfsStream::url('root').'/nonce/dir'])
            ->getMock();
        $this->assertNull($provider->authenticate(new AnonymousToken('secret', 'user')));

        $user = new User('username');
        $user->setApiKeyEnabled(true);
        $user->setApiKeyPublic('apiKeyPublic');
        $user->setApiKeyPrivate('apiKeyPrivate');
        $this->userProvider->expects($this->once())->method('loadUserByPublicKey')->willReturn($user);
        $provider->expects($this->any())->method('writeNonceValue');

        $token = new PublicKeyToken();
        $token->setUser($user);

        $encoder = new RequestSignatureEncoder();
        $provider->expects($this->once())
                ->method('readNonceValue')
                ->willReturn([time(), $encoder->createSignature($token)]);

        $this->assertInstanceOf(PublicKeyToken::class, $provider->authenticate($token));
    }

    /**
     * @covers            ::authenticate()
     * @covers            ::onInvalidAuthentication()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testInvalidNonce()
    {
        $provider = $this->getMockBuilder(PublicKeyAuthenticationProvider::class)
            ->setMethods(['readNonceValue'])
            ->setConstructorArgs([$this->userProvider, vfsStream::url('root').'/nonce/dir'])
            ->getMock();
        $provider->expects($this->once())->method('readNonceValue');
        $provider->authenticate(new PublicKeyToken());
    }

    /**
     * @covers            ::authenticate()
     * @covers            ::onInvalidAuthentication()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testInvalidUser()
    {
        $provider = $this->getMockBuilder(PublicKeyAuthenticationProvider::class)
            ->setMethods(['readNonceValue'])
            ->setConstructorArgs([$this->userProvider, vfsStream::url('root').'/nonce/dir'])
            ->getMock();
        $provider->expects($this->once())->method('readNonceValue')->willReturn([time(), 'nonce']);
        $provider->authenticate(new PublicKeyToken());
    }

    /**
     * @covers            ::authenticate()
     * @covers            ::onInvalidAuthentication()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testInvalidSignature()
    {
        $provider = $this->getMockBuilder(PublicKeyAuthenticationProvider::class)
            ->setMethods(['readNonceValue'])
            ->setConstructorArgs([$this->userProvider, vfsStream::url('root').'/nonce/dir'])
            ->getMock();
        $provider->expects($this->once())->method('readNonceValue')->willReturn([time(), 'nonce']);

        $user = new User('username');
        $this->userProvider->expects($this->once())->method('loadUserByPublicKey')->willReturn($user);

        $provider->authenticate(new PublicKeyToken());
    }

    /**
     * @covers                   ::authenticate()
     * @expectedException        \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Prior authentication expired
     */
    public function testInvalidTime()
    {
        $provider = $this->getMockBuilder(PublicKeyAuthenticationProvider::class)
            ->setMethods(['readNonceValue', 'writeNonceValue'])
            ->setConstructorArgs([$this->userProvider, vfsStream::url('root').'/nonce/dir'])
            ->getMock();

        $user = new User('username');
        $user->setApiKeyEnabled(true);
        $user->setApiKeyPublic('apiKeyPublic');
        $user->setApiKeyPrivate('apiKeyPrivate');
        $this->userProvider->expects($this->once())->method('loadUserByPublicKey')->willReturn($user);
        $provider->expects($this->any())->method('writeNonceValue');

        $token = new PublicKeyToken();
        $token->setUser($user);

        $encoder = new RequestSignatureEncoder();
        $provider->expects($this->once())
                ->method('readNonceValue')
                ->willReturn([strtotime('2000-01-01 00:00:00'), $encoder->createSignature($token)]);

        $provider->authenticate(new PublicKeyToken());
    }

    /**
     * @covers ::__construct()
     * @covers ::getRoles()
     */
    public function testGetRoles()
    {
        $user = new User();
        $user->setApiKeyEnabled(true);

        $this->assertEquals(
            ['ROLE_API_USER'],
            $this->invokeMethod($this->provider, 'getRoles', [$user])
        );
    }

    /**
     * @covers ::supports()
     */
    public function testSupports()
    {
        $this->assertFalse($this->provider->supports(new AnonymousToken('secret', 'user')));
        $this->assertTrue($this->provider->supports(new PublicKeyToken()));
    }
}
