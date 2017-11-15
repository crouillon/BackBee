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

namespace BackBee\Routing\Tests;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

use BackBee\ApplicationInterface;
use BackBee\DependencyInjection\Container;
use BackBee\Routing\Route;
use BackBee\Routing\RouteCollection;
use BackBee\Site\Site;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class RouteCollection
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Routing\RouteCollection
 */
class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @var RouteCollection
     */
    private $collection;

    /**
     * @var ApplicationInterface
     */
    private $application;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $logger = $this->getMockForAbstractClass(
            LoggerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['log']
        );

        $container = new Container();
        $container->setParameter('bbapp.routing.image_uri_prefix', 'images');
        $container->setParameter('bbapp.routing.default_protocol', 'http');
        $container->set('logging', $logger);

        $this->application = $this->getMockForAbstractClass(
            ApplicationInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getContainer', 'isClientSAPI' , 'isStarted', 'getRequest', 'getSite']
        );
        $this->application->expects($this->any())->method('getContainer')->willReturn($container);

        $this->collection = new RouteCollection($this->application);
        $this->collection->add('default', new Route('/'));
    }

    /**
     * @covers ::__construct()
     * @covers ::readFromContainer()
     * @covers ::setValueIfParameterExists()
     */
    public function testConstruct()
    {
        $container = $this->application->getContainer();

        $this->assertEquals($this->application, $this->invokeProperty($this->collection, 'application'));
        $this->assertEquals($container->get('logging'), $this->invokeProperty($this->collection, 'logger'));
        $this->assertEquals(
            [1 => 'images', 2 => null, 3 => null],
            $this->invokeProperty($this->collection, 'uriPrefixes')
        );
        $this->assertEquals('http', $this->invokeProperty($this->collection, 'defaultScheme'));
    }

    /**
     * @covers ::pushRouteCollection()
     * @covers ::addRoute()
     * @covers ::moveDefaultRoute()
     * @covers ::log()
     */
    public function testPushRouteCollection()
    {
        $logger = $this->application->getContainer()->get('logging');

        $routes = [
            'invalid' => [],
            'images' => [
                'pattern' => '/images',
                'defaults' => [],
            ]
        ];

        $logger->expects($this->at(0))
            ->method('log')
            ->with('warning', 'Unable to parse the route definition `invalid`.');
        $logger->expects($this->at(1))
            ->method('log')
            ->with('debug', 'Route `images` with pattern `/images` defined.');

        $this->collection->pushRouteCollection($routes);

        $this->assertEquals(['images', 'default'], array_keys($this->collection->all()));
    }

    /**
     * @covers ::getRoutePath()
     */
    public function testGetRoutePath()
    {
        $this->assertNull($this->collection->getRoutePath('unknown'));
        $this->assertEquals('/', $this->collection->getRoutePath('default'));
    }

    /**
     * @covers ::getUrlByRouteName()
     * @covers ::applyRouteParameters()
     */
    public function testGetUrlByRouteName()
    {
        $this->collection->pushRouteCollection(['fake' => ['pattern' => '/fake/{param}', 'defaults' => []]]);

        $this->assertEquals(
            '/fake/param?paramsupp=paramsupp',
            $this->collection->getUrlByRouteName(
                'fake',
                ['param' => 'param', 'paramsupp' => 'paramsupp'],
                null,
                false,
                null,
                true
            )
        );
    }

    /**
     * @covers ::getUri()
     * @covers ::hasRequestAvailable()
     * @covers ::getUriForSite()
     * @covers ::getCurrentSite()
     * @covers ::getDefaultExtFromSite()
     */
    public function testGetUriWithoutApplication()
    {
        $routeCollection = new RouteCollection();

        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com'));
        $this->assertEquals('/', $routeCollection->getUri());
        $this->assertEquals('/', $routeCollection->getUri(null, null, null, RouteCollection::IMAGE_URL));
        $this->assertEquals('/', $routeCollection->getUri(null, '.html'));
        $this->assertEquals('/fake', $routeCollection->getUri('/fake'));
        $this->assertEquals('/fake.htm', $routeCollection->getUri('fake.htm', '.html'));
        $this->assertEquals('/fake.html', $routeCollection->getUri('fake', '.html'));
        $this->assertEquals('/fake.html', $routeCollection->getUri('fake', null, new Site()));
    }

    /**
     * @covers ::getUri()
     * @covers ::hasRequestAvailable()
     * @covers ::getUriForSite()
     * @covers ::getCurrentSite()
     * @covers ::getDefaultExtFromSite()
     */
    public function testGetUriSapiWithoutSite()
    {
        $this->application->expects($this->any())->method('isClientSAPI')->willReturn(true);

        $this->assertEquals('http://www.backbee.com', $this->collection->getUri('http://www.backbee.com'));
        $this->assertEquals('/', $this->collection->getUri());
        $this->assertEquals('/images/', $this->collection->getUri(null, null, null, RouteCollection::IMAGE_URL));
        $this->assertEquals('/', $this->collection->getUri(null, '.html'));
        $this->assertEquals('/fake', $this->collection->getUri('/fake'));
        $this->assertEquals('/fake.htm', $this->collection->getUri('fake.htm', '.html'));
        $this->assertEquals('/fake.html', $this->collection->getUri('fake', '.html'));
    }

    /**
     * @covers ::getUri()
     * @covers ::hasRequestAvailable()
     * @covers ::getUriForSite()
     * @covers ::getDefaultExtFromSite()
     */
    public function testGetUriSapiWithSiteSetted()
    {
        $site = new Site();
        $site->setServerName('www.fakeserver.com');

        $this->application->expects($this->any())->method('isClientSAPI')->willReturn(true);

        $this->assertEquals('http://www.backbee.com', $this->collection->getUri('http://www.backbee.com', null, $site));
        $this->assertEquals('http://www.fakeserver.com/', $this->collection->getUri(null, null, $site));
        $this->assertEquals(
            'http://www.fakeserver.com/images/',
            $this->collection->getUri(null, null, $site, RouteCollection::IMAGE_URL)
        );
        $this->assertEquals('http://www.fakeserver.com/', $this->collection->getUri(null, '.html', $site));
        $this->assertEquals('http://www.fakeserver.com/fake.html', $this->collection->getUri('/fake', null, $site));
        $this->assertEquals(
            'http://www.fakeserver.com/fake.htm',
            $this->collection->getUri('fake.htm', '.html', $site)
        );
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $this->collection->getUri('fake', '.htm', $site));
    }

    /**
     * @covers ::getUri()
     * @covers ::hasRequestAvailable()
     * @covers ::getUriForSite()
     * @covers ::getCurrentSite()
     * @covers ::getDefaultExtFromSite()
     */
    public function testGetUriSapiWithApplicationSite()
    {
        $site = new Site();
        $site->setServerName('www.fakeserver.com');

        $this->application->expects($this->any())->method('isClientSAPI')->willReturn(true);
        $this->application->expects($this->any())->method('getSite')->willReturn($site);

        $this->assertEquals('http://www.backbee.com', $this->collection->getUri('http://www.backbee.com'));
        $this->assertEquals('http://www.fakeserver.com/', $this->collection->getUri());
        $this->assertEquals(
            'http://www.fakeserver.com/images/',
            $this->collection->getUri(null, null, null, RouteCollection::IMAGE_URL)
        );
        $this->assertEquals('http://www.fakeserver.com/', $this->collection->getUri(null, '.html'));
        $this->assertEquals('http://www.fakeserver.com/fake.html', $this->collection->getUri('/fake'));
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $this->collection->getUri('fake.htm', '.html'));
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $this->collection->getUri('fake', '.htm'));
    }

    /**
     * @covers ::getUri()
     * @covers ::hasRequestAvailable()
     * @covers ::getUriFromBaseUrl()
     * @covers ::getDefaultExtFromSite()
     */
    public function testGetUriRequested()
    {
        // Starting with a site without servername and simulate an HTTPS request
        $site = new Site();
        $this->application->expects($this->any())->method('getSite')->willReturn($site);

        $this->application->expects($this->any())->method('isClientSAPI')->willReturn(false);
        $this->application->expects($this->any())->method('isStarted')->willReturn(true);

        $request = new Request();
        $request->server->add([
            'SCRIPT_URL' => '/public/fake/fake.html',
            'SCRIPT_URI' => 'https://www.fakeserver.com/public/fake/fake.html',
            'HTTP_HOST' => 'www.fakeserver.com',
            'SERVER_NAME' => 'www.fakeserver.com',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'DOCUMENT_ROOT' => '/home/web/fakeroot',
            'SCRIPT_FILENAME' => '/home/web/fakeroot/public/index.php',
            'REQUEST_URI' => '/public/fake/fake.html',
            'SCRIPT_NAME' => '/public/index.php'
        ]);
        $this->application->expects($this->any())->method('getRequest')->willReturn($request);

        // No site provided, the request is used
        $this->assertEquals('http://www.backbee.com', $this->collection->getUri('http://www.backbee.com'));
        $this->assertEquals('https://www.fakeserver.com/public/', $this->collection->getUri());
        $this->assertEquals(
            'https://www.fakeserver.com/public/images/',
            $this->collection->getUri(null, null, null, RouteCollection::IMAGE_URL)
        );
        $this->assertEquals('https://www.fakeserver.com/public/', $this->collection->getUri(null, '.html'));
        $this->assertEquals('https://www.fakeserver.com/public/fake.html', $this->collection->getUri('/fake'));
        $this->assertEquals(
            'https://www.fakeserver.com/public/fake.htm',
            $this->collection->getUri('fake.htm', '.html')
        );
        $this->assertEquals('https://www.fakeserver.com/public/fake.html', $this->collection->getUri('fake', '.html'));

        // A site is provided, the base URL and the protocol can't be predicted
        $otherSite = new Site();
        $otherSite->setServerName('other.fakeserver.com');
        $this->assertEquals(
            'http://www.backbee.com',
            $this->collection->getUri('http://www.backbee.com', null, $otherSite)
        );
        $this->assertEquals('http://other.fakeserver.com/', $this->collection->getUri(null, null, $otherSite));
        $this->assertEquals(
            'http://other.fakeserver.com/images/',
            $this->collection->getUri(null, null, $otherSite, RouteCollection::IMAGE_URL)
        );
        $this->assertEquals('http://other.fakeserver.com/', $this->collection->getUri(null, '.html', $otherSite));
        $this->assertEquals(
            'http://other.fakeserver.com/fake.html',
            $this->collection->getUri('/fake', null, $otherSite)
        );
        $this->assertEquals(
            'http://other.fakeserver.com/fake.htm',
            $this->collection->getUri('fake.htm', '.html', $otherSite)
        );
        $this->assertEquals(
            'http://other.fakeserver.com/fake.html',
            $this->collection->getUri('fake', '.html', $otherSite)
        );
    }

    /**
     * @covers ::getUri()
     * @covers ::hasRequestAvailable()
     * @covers ::getUriFromBaseUrl()
     * @covers ::getDefaultExtFromSite()
     */
    public function testGetUriRootRequested()
    {
        // Starting with a site without servername and simulate an HTTPS request
        $site = new Site();
        $this->application->expects($this->any())->method('getSite')->willReturn($site);

        $this->application->expects($this->any())->method('isClientSAPI')->willReturn(false);
        $this->application->expects($this->any())->method('isStarted')->willReturn(true);

        $request = new Request();
        $request->server->add([
            'SCRIPT_URL' => '/index.php',
            'SCRIPT_URI' => 'https://www.fakeserver.com/index.php',
            'HTTP_HOST' => 'www.fakeserver.com',
            'SERVER_NAME' => 'www.fakeserver.com',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'DOCUMENT_ROOT' => '/home/web/fakeroot',
            'SCRIPT_FILENAME' => '/home/web/fakeroot/index.php',
            'REQUEST_URI' => '/index.php',
            'SCRIPT_NAME' => '/index.php'
        ]);
        $this->application->expects($this->any())->method('getRequest')->willReturn($request);

        $this->assertEquals('https://www.fakeserver.com/', $this->collection->getUri());
    }

    /**
     * @covers ::getClassProxy()
     */
    public function testGetClassProxy()
    {
        $this->assertNull($this->collection->getClassProxy());
    }

    /**
     * @covers ::dump()
     */
    public function testDump()
    {
        $routes = [
            'images' => [
                'pattern' => '/images',
                'defaults' => [],
            ]
        ];
        $this->collection->pushRouteCollection($routes);

        $this->assertEquals(['routes' => $routes], $this->collection->dump());
    }

    /**
     * @covers ::restore()
     * @covers ::isRestored()
     */
    public function testRestore()
    {
        $logger = $this->application->getContainer()->get('logging');
        $logger->expects($this->at(0))->method('log')->with('warning', 'No routes found when restoring collection.');

        $this->collection->restore($this->application->getContainer(), []);
        $this->assertFalse($this->collection->isRestored());

        $routes = [
            'images' => [
                'pattern' => '/images',
                'defaults' => [],
            ]
        ];

        $this->collection->restore($this->application->getContainer(), ['routes' => $routes]);
        $this->assertTrue($this->collection->isRestored());
        $this->assertEquals(['images', 'default'], array_keys($this->collection->all()));
    }
}
