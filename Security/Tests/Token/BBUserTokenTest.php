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

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\User;

use BackBee\Security\Token\BBUserToken;

/**
 * Test suite for class BBUserToken
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Token\BBUserToken
 */
class BBUserTokenTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var BBUserToken
     */
    private $token;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->token = new BBUserToken();
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $this->assertTrue($this->token->isAuthenticated());
    }

    /**
     * @covers ::getCreated()
     * @covers ::setCreated()
     */
    public function testCreated()
    {
        $this->assertEquals($this->token, $this->token->setCreated('2007-01-01 00:00:00'));
        $this->assertEquals('2007-01-01 00:00:00', $this->token->getCreated());

        $now = new \DateTime();
        $this->assertEquals($this->token, $this->token->setCreated($now));
        $this->assertEquals($now->format('Y-m-d H:i:s'), $this->token->getCreated());
    }

    /**
     * @covers            ::isExpired()
     * @expectedException \LogicException
     */
    public function testInvalidTokenExpired()
    {
        $this->token->isExpired();
    }

    /**
     * @covers            ::isExpired()
     * @expectedException \LogicException
     */
    public function testInvalidLifetimeExpired()
    {
        $this->token
            ->setLifetime('')
            ->setCreated(new \DateTime())
            ->isExpired();
    }

    /**
     * @covers ::isExpired()
     * @covers ::setLifetime()
     */
    public function testIsExpired()
    {
        $this->assertFalse(
            $this->token
                ->setLifetime(20)
                ->setCreated(new \DateTime())
                ->isExpired()
        );
        $this->assertTrue(
            $this->token
                ->setLifetime(20)
                ->setCreated(new \DateTime('yesterday'))
                ->isExpired()
        );
    }

    /**
     * @covers ::setUser()
     */
    public function testSetUser()
    {
        $this->assertEquals($this->token, $this->token->setUser('username'));
    }

    /**
     * @covers ::serialize()
     * @covers ::unserialize()
     * @covers ::getLifetime()
     */
    public function testSerialize()
    {
        $user = new User('name', 'password', ['ROLE_FOO', new Role('ROLE_BAR')]);

        $this->token
            ->setUser($user)
            ->setCreated(new \DateTime())
            ->setNonce('nonce')
            ->setLifetime(10)
            ->setAttributes(['foo' => 'bar']);

        $serialized = serialize($this->token);
        $unserialized = unserialize($serialized);

        $this->assertEquals($this->token->getRoles(), $unserialized->getRoles());
        $this->assertEquals($this->token->getCreated(), $unserialized->getCreated());
        $this->assertEquals($this->token->getNonce(), $unserialized->getNonce());
        $this->assertEquals($this->token->getLifetime(), $unserialized->getLifetime());
        $this->assertEquals($this->token->getAttributes(), $unserialized->getAttributes());
    }
}
