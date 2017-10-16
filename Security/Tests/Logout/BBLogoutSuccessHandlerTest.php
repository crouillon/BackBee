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

namespace BackBee\Security\Tests\Logout;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use BackBee\Security\Logout\BBLogoutSuccessHandler;

/**
 * Test suite for class BBLogoutSuccessHandler
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Logout\BBLogoutSuccessHandler
 */
class BBLogoutSuccessHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var BBLogoutSuccessHandler
     */
    private $handler;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->handler = new BBLogoutSuccessHandler();
    }

    /**
     * @covers ::onLogoutSuccess()
     */
    public function testOnLogoutSuccess()
    {
        $this->assertInstanceOf(
            RedirectResponse::class,
            $this->handler->onLogoutSuccess(new Request())
        );
    }
}
