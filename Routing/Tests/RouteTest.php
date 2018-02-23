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

namespace BackBee\Routing\Tests;

use BackBee\Routing\Route;

/**
 * Test suite for class Route
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Routing\Route
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct()
     * @covers ::addHeaderRequirements()
     * @covers ::getRequirements()
     */
    public function testGetRequirements()
    {
        $requirements = ['fake' => 'fake', 'HTTP-FAKE' => 'http-fake'];
        $route = new Route('', [], $requirements, []);

        $this->assertEquals($requirements, $route->getRequirements());
        $this->assertEquals(['HTTP-FAKE' => 'http-fake'], $route->getRequirements('HTTP-'));
        $this->assertEquals([], $route->getRequirements(''));
    }

    /**
     * @covers ::addRequirements()
     */
    public function testAddRequirements()
    {
        $requirements = ['fake' => 'fake', 'HTTP-FAKE' => 'http-fake'];
        $route = new Route('', [], [], []);

        $this->assertEquals($route, $route->addRequirements($requirements));
        $this->assertEquals(['HTTP-FAKE' => 'http-fake'], $route->getRequirements('HTTP-'));
    }
}
