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

namespace BackBee\Security\Tests\Context;

use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;

use BackBee\Security\Context\AnonymousContext;
use BackBee\Security\SecurityContext;

/**
 * Test suite for class AnonymousContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Context\AnonymousContext
 */
class AnonymousContextTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::loadListeners()
     */
    public function testLoadListeners()
    {
        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAuthProvider', 'getLogger'])
            ->getMock();

        $context = new AnonymousContext($securityContext);
        $this->assertEquals([], $context->loadListeners([]));

        $securityContext->expects($this->once())
            ->method('addAuthProvider')
            ->will($this->returnCallback(function ($provider) {
                \PHPUnit_Framework_Assert::assertInstanceOf(
                    AnonymousAuthenticationProvider::class,
                    $provider
                );
            }));

        $listeners = $context->loadListeners(['anonymous' => '']);
        $this->assertInstanceOf(AnonymousAuthenticationListener::class, $listeners[0]);
    }
}
