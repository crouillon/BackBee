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

namespace BackBee\Security\Tests\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;
use BackBee\Security\Logout\BBLogoutHandler;

/**
 * Test suite for class BBLogoutHandler
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Logout\BBLogoutHandler
 */
class BBLogoutHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var BBLogoutHandler
     */
    private $handler;

    /**
     * @var BBAuthenticationProvider
     */
    private $provider;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->getMockBuilder(BBAuthenticationProvider::class)
                ->disableOriginalConstructor()
                ->setMethods(['clearNonce'])
                ->getMock();

        $this->handler = new BBLogoutHandler($this->provider);
    }

    /**
     * @covers ::__construct()
     * @covers ::logout()
     */
    public function testLogout()
    {
        $this->provider
            ->expects($this->once())
            ->method('clearNonce');

        $this->handler->logout(
            new Request(),
            new Response(),
            new AnonymousToken('secret', 'user')
        );
    }
}
