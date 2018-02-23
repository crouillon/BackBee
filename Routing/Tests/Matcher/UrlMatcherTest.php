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

namespace BackBee\Routing\Tests\Matcher;

use Symfony\Component\HttpFoundation\Request;

use BackBee\Routing\Matcher\UrlMatcher;
use BackBee\Routing\RequestContext;
use BackBee\Routing\Route;
use BackBee\Routing\RouteCollection;

/**
 * Test suite for class UrlMatcher
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Routing\Matcher\UrlMatcher
 */
class UrlMatcherTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var RouteCollection
     */
    private $routes;

    /**
     * @var UrlMatcher
     */
    private $matcher;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $route = new Route('/fake');
        $route->addRequirements(['HTTP-FAKE' => 'http-fake']);

        $this->routes = new RouteCollection();
        $this->routes->add('fake', $route);

        $this->matcher = new UrlMatcher($this->routes, new RequestContext());
    }

    /**
     * @covers ::handleRouteRequirements()
     */
    public function testWWithoutRequirements()
    {
        $request = new Request();
        $routes = new RouteCollection();
        $routes->add('root', new Route('/'));
        $matcher = new UrlMatcher($routes, new RequestContext());

        $this->assertEquals(['_route' => 'root'], $matcher->matchRequest($request));
    }

    /**
     * @covers            ::handleRouteRequirements()
     * @covers            ::match()
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testNotFoundRoute()
    {
        $routes = new RouteCollection();
        $routes->add('fake', new Route('/fake'));
        $matcher = new UrlMatcher($routes, new RequestContext());

        $matcher->match('/');
    }

    /**
     * @covers            ::handleRouteRequirements()
     * @covers            ::match()
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testNotFoundRequirements()
    {
        $this->matcher->match('/fake');
    }

    /**
     * @covers ::handleRouteRequirements()
     * @covers ::matchRequest()
     */
    public function testMatchRequest()
    {
        $request = new Request();
        $request->headers->set('HTTP-FAKE', 'http-fake');
        $request->server->set('REQUEST_URI', '/fake');

        $this->assertEquals(['_route' => 'fake'], $this->matcher->matchRequest($request));
    }
}
