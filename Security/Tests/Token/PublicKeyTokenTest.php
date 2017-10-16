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

namespace BackBee\Security\Tests\Token;

use Symfony\Component\Security\Core\User\User as sfUser;

use BackBee\Security\Token\PublicKeyToken;
use BackBee\Security\User;

/**
 * Test suite for class PublicKeyToken
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Token\PublicKeyToken
 */
class PublicKeyTokenTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var PublicKeyToken
     */
    private $token;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->token = new PublicKeyToken();
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $this->assertFalse($this->token->isAuthenticated());
        $this->assertTrue((new PublicKeyToken(['foo']))->isAuthenticated());
    }

    /**
     * @covers ::getUsername()
     */
    public function testGetUsername()
    {
        $user = $this->getMockBuilder(User::class)
                ->setMethods(['getApiKeyPublic'])
                ->getMock();

        $user->expects($this->once())
                ->method('getApiKeyPublic')
                ->willReturn('api-key');

        $this->token->setUser($user);

        $this->assertEquals('api-key', $this->token->getUsername());

        $token = new PublicKeyToken();
        $token->setUser(new sfUser('username', 'password'));
        $this->assertEquals('username', $token->getUsername());
    }

    /**
     * @covers ::getPublicKey()
     * @covers ::setPublicKey()
     */
    public function testPublicKey()
    {
        $this->assertEquals($this->token, $this->token->setPublicKey('key'));
        $this->assertEquals('key', $this->token->getPublicKey());
    }

    /**
     * @covers ::getSignature()
     * @covers ::setSignature()
     */
    public function testSignature()
    {
        $this->assertEquals($this->token, $this->token->setSignature('signature'));
        $this->assertEquals('signature', $this->token->getSignature());
    }
}
