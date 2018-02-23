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

use BackBee\Security\Context\StatelessContext;
use BackBee\Security\Listeners\ContextListener;
use BackBee\Security\SecurityContext;

/**
 * Test suite for class StatelessContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Context\StatelessContext
 */
class StatelessContextTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::loadListeners()
     */
    public function testLoadListeners()
    {
        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserProviders', 'getLogger', 'getDispatcher'])
            ->getMock();

        $context = new StatelessContext($securityContext);
        $this->assertEquals([], $context->loadListeners(['stateless' => true]));

        $securityContext->expects($this->any())
            ->method('getUserProviders')
            ->willReturn([]);

        $config = [
            'stateless' => false,
            'context' => 'key',
            'firewall_name' => 'name'
        ];

        $listeners = $context->loadListeners($config);
        $this->assertInstanceOf(ContextListener::class, $listeners[0]);

        unset($config['context']);
        $newListeners = $context->loadListeners($config);
        $this->assertInstanceOf(ContextListener::class, $newListeners[0]);
    }
}
