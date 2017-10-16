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

namespace BackBee\Security\Tests\Listeners;

use Symfony\Component\HttpFoundation\Request;

use BackBee\Security\Listeners\LogoutListener;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Test suite for class LogoutListener
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Listeners\LogoutListener
 */
class LogoutListenerTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;

    /**
     * @covers ::requiresLogout()
     */
    public function testRequiresLogout()
    {
        $request = $this->getMockBuilder(Request::class)
                ->disableOriginalConstructor()
                ->getMock();

        $listener = $this->getMockBuilder(LogoutListener::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->assertTrue($this->invokeMethod($listener, 'requiresLogout', [$request]));
    }
}
