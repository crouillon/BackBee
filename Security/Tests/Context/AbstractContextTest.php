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

namespace BackBee\Security\Tests\Context;

use BackBee\Security\Context\AbstractContext;
use BackBee\Security\SecurityContext;

/**
 * Test suite for class AbstractContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Context\AbstractContext
 */
class AbstractContextTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getDefaultProvider()
     */
    public function testGetDefaultProvider()
    {
        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserProviders'])
            ->getMock();

        $providers = [
            'provider1' => 'provider1',
            'provider2' => 'provider2',
        ];

        $securityContext->expects($this->any())
            ->method('getUserProviders')
            ->willReturn($providers);

        $context = $this->getMockForAbstractClass(AbstractContext::class, [$securityContext]);

        $this->assertEquals('provider1', $context->getDefaultProvider([]));
        $this->assertEquals('provider2', $context->getDefaultProvider(['provider' => 'provider2']));
    }
}
