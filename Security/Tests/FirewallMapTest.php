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

namespace BackBee\Security\Tests;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;

use BackBee\Security\FirewallMap;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class FirewallMap
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\FirewallMap
 */
class FirewallMapTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @covers ::unshift()
     */
    public function testUnshift()
    {
        $matcher1 = $this->getMockForAbstractClass(RequestMatcherInterface::class);
        $matcher2 = $this->getMockForAbstractClass(RequestMatcherInterface::class);

        $map = new FirewallMap();
        $map->unshift($matcher1);
        $map->unshift($matcher2);

        $property = $this->invokeProperty($map, 'map');
        $this->assertEquals($matcher2, $property[0][0]);
        $this->assertEquals($matcher1, $property[1][0]);
    }
}
