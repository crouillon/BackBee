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

use Doctrine\ORM\EntityRepository;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;
use BackBee\Security\Encoder\RequestSignatureEncoder;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;
use BackBee\Util\Registry\Registry;

/**
 * Test suite for class BBAuthenticationProvider.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authentication\Provider\BBAuthenticationProvider
 */
class BBAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;
    use InvokeMethodTrait;

    /**
     * @var BBAuthenticationProvider
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

        vfsStream::setup('root', 0777, []);

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
        $this->provider = new BBAuthenticationProvider(
            $this->userProvider,
            vfsStream::url('root').'/nonce/dir',
            30,
            null,
            $this->encoder
        );
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $this->assertTrue(file_exists(vfsStream::url('root').'/nonce/dir'));
    }

    /**
     * @covers ::authenticate()
     */
    public function testAuthenticate()
    {
        $user = new User('login', 'password');
        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $token = new BBUserToken();
        $token->setUser($user);

        $provider = $this->getMock(
            BBAuthenticationProvider::class,
            ['checkNonce', 'writeNonceValue'],
            [$this->userProvider, vfsStream::url('root')]
        );
        $provider->expects($this->once())->method('checkNonce');
        $provider->expects($this->once())->method('writeNonceValue');

        $this->assertInstanceOf(BBUserToken::class, $provider->authenticate($token));
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testInvalidToken()
    {
        $this->provider->authenticate(new AnonymousToken('secret', 'user'));
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testInvalidUser()
    {
        $provider = $this->getMock(
            BBAuthenticationProvider::class,
            ['clearNonce'],
            [$this->userProvider, vfsStream::url('root')]
        );
        $provider->expects($this->once())->method('clearNonce');

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willThrowException(new UsernameNotFoundException());

        $provider->authenticate(new BBUserToken());
    }

    /**
     * @covers            ::authenticate()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testInvalidNonce()
    {
        $user = new User('login', 'password');
        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $token = new BBUserToken();
        $token->setUser($user);

        $provider = $this->getMock(
            BBAuthenticationProvider::class,
            ['checkNonce', 'clearNonce'],
            [$this->userProvider, vfsStream::url('root')]
        );
        $provider->expects($this->once())->method('checkNonce')->willThrowException(new SecurityException());
        $provider->expects($this->once())->method('clearNonce');

        $provider->authenticate($token);
    }

    /**
     * @covers ::supports()
     */
    public function testSupports()
    {
        $this->assertFalse($this->provider->supports(new AnonymousToken('secret', 'user')));
        $this->assertTrue($this->provider->supports(new BBUserToken()));
    }

    /**
     * @covers ::clearNonce()
     */
    public function testClearNonce()
    {
        $token = new BBUserToken();
        $token->setNonce('nonce');

        $provider = $this->getMock(BBAuthenticationProvider::class, ['removeNonce'], [], '', false);
        $provider->expects($this->once())->method('removeNonce');
        $provider->clearNonce($token);
    }

    /**
     * @covers ::checkNonce()
     * @covers ::readNonceValue()
     */
    public function testCheckNonce()
    {
        $token = new BBUserToken();
        $token->setCreated(new \DateTime());
        $token->setNonce('nonce');
        $token->setDigest(md5($token->getNonce().$token->getCreated().'secret'));

        file_put_contents(vfsStream::url('root').'/nonce/dir/nonce', time().';secret');

        $this->assertTrue($this->invokeMethod($this->provider, 'checkNonce', [$token, 'secret']));
    }

    /**
     * @covers                   ::checkNonce()
     * @expectedException        \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Request expired
     */
    public function testCheckInvalidDate()
    {
        $token = new BBUserToken();
        $token->setCreated('2000-01-01 00:00:00');
        $this->invokeMethod($this->provider, 'checkNonce', [$token, 'secret']);
    }

    /**
     * @covers                   ::checkNonce()
     * @expectedException        \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Invalid authentication informations
     */
    public function testCheckInvalidDigest()
    {
        $token = new BBUserToken();
        $token->setCreated(new \DateTime());
        $this->invokeMethod($this->provider, 'checkNonce', [$token, 'secret']);
    }

    /**
     * @covers                   ::checkNonce()
     * @expectedException        \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Prior authentication expired
     */
    public function testCheckInvalidNonce()
    {
        $token = new BBUserToken();
        $token->setCreated(new \DateTime());
        $token->setNonce('nonce');
        $token->setDigest(md5($token->getNonce().$token->getCreated().'secret'));

        file_put_contents(vfsStream::url('root').'/nonce/dir/nonce', '0;secret');

        $this->invokeMethod($this->provider, 'checkNonce', [$token, 'secret']);
    }

    /**
     * @covers ::readNonceValue()
     */
    public function testReadNonceValueByRegistry()
    {
        $registry = new Registry();
        $registry->setValue('time;secret');

        $repository = $this->getMock(EntityRepository::class, ['findOneBy'], [], '', false);
        $repository->expects($this->once())->method('findOneBy')->willReturn($registry);

        $this->invokeProperty($this->provider, 'registryRepository', $repository);

        $this->assertEquals(
            ['time', 'secret'],
            $this->invokeMethod($this->provider, 'readNonceValue', ['nonce'])
        );
    }

    /**
     * @covers ::writeNonceValue()
     */
    public function testWriteNonceValue()
    {
        $token = new BBUserToken();
        $token->setUser(new User());
        $token->setNonce('nonce');
        $token->setCreated('2000-01-01 00:00:00');

        $now = strtotime($token->getCreated());
        $signature = (new RequestSignatureEncoder())->createSignature($token);

        $expected = sprintf('%s;%s', $now, $signature);

        $this->invokeMethod($this->provider, 'writeNonceValue', [$token]);
        $this->assertEquals($expected, file_get_contents(vfsStream::url('root').'/nonce/dir/nonce'));

        $repository = $this->getMock(EntityRepository::class, ['findOneBy', 'save'], [], '', false);
        $this->invokeProperty($this->provider, 'registryRepository', $repository);

        $repository->expects($this->once())
            ->method('save')
            ->will($this->returnCallback(function ($registry) {
                \PHPUnit_Framework_Assert::assertInstanceOf(Registry::class, $registry);
            }));
        $this->invokeMethod($this->provider, 'writeNonceValue', [$token]);
    }

    /**
     * @covers ::removeNonce()
     */
    public function testRemoveNonce()
    {
        file_put_contents(vfsStream::url('root').'/nonce/dir/nonce', 'nonce');
        $this->invokeMethod($this->provider, 'removeNonce', ['nonce']);

        $this->assertFalse(is_file(vfsStream::url('root').'/nonce/dir/nonce'));

        $repository = $this->getMock(EntityRepository::class, ['findOneBy', 'remove'], [], '', false);
        $this->invokeProperty($this->provider, 'registryRepository', $repository);

        $repository->expects($this->once())
            ->method('remove');

        $this->invokeMethod($this->provider, 'removeNonce', ['nonce']);
    }

    /**
     * @covers ::getRegistry()
     */
    public function testGetRegistry()
    {
        $repository = $this->getMock(EntityRepository::class, ['findOneBy'], [], '', false);
        $repository->expects($this->once())->method('findOneBy');
        $this->invokeProperty($this->provider, 'registryRepository', $repository);

        $registry = $this->invokeMethod($this->provider, 'getRegistry', ['nonce']);

        $this->assertEquals('nonce', $registry->getKey());
        $this->assertEquals('SECURITY.NONCE', $registry->getScope());
    }

    /**
     * @covers ::getSecret()
     */
    public function testGetSecret()
    {
        $user = new User('login', 'password');
        $this->userProvider
            ->expects($this->any())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $this->encoder
            ->expects($this->once())
            ->method('getEncoder')
            ->willReturn(new PlaintextPasswordEncoder());

        $this->assertEquals('password', $this->invokeMethod($this->provider, 'getSecret', [new BBUserToken()]));

        $this->invokeProperty($this->provider, 'encoderFactory', 0);
        $this->assertEquals(md5('password'), $this->invokeMethod($this->provider, 'getSecret', [new BBUserToken()]));
    }

    /**
     * @covers ::getSecret()
     */
    public function testGetSecretWithoutEncoder()
    {
        $user = new User('login', 'password');
        $this->userProvider
            ->expects($this->any())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $this->encoder
            ->expects($this->once())
            ->method('getEncoder')
            ->willThrowException(new \RuntimeException());

        $this->assertEquals(md5('password'), $this->invokeMethod($this->provider, 'getSecret', [new BBUserToken()]));
    }
}
