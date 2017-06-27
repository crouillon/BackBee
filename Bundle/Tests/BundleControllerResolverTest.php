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

namespace BackBee\Bundle\Tests;

use BackBee\Bundle\BundleControllerResolver;

/**
 * Tests suite for class BundleControllerResolver.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BundleControllerResolverTest extends BundleTestCase
{

    /**
     * @var BundleControllerResolver
     */
    private $resolver;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->resolver = new BundleControllerResolver($this->application);
    }

    /**
     * @covers            BackBee\Bundle\BundleControllerResolver::resolve()
     * @expectedException BackBee\Bundle\Exception\BundleConfigurationException
     * @expectedExceptionMessage unknown doesn't exist.
     */
    public function testResolveUnknownBundle()
    {
        $this->resolver->resolve('unknown', 'controller');
    }

    /**
     * @covers            BackBee\Bundle\BundleControllerResolver::resolve()
     * @expectedException BackBee\Bundle\Exception\BundleConfigurationException
     * @expectedExceptionMessage No controller definition in
     */
    public function testResolveNoAdminController()
    {
        $bundle = $this->getBundle('mockbundle');
        $this->container->set('bundle.mockbundle', $bundle);

        $this->resolver->resolve('mockbundle', 'controller');
    }

    /**
     * @covers            BackBee\Bundle\BundleControllerResolver::resolve()
     * @expectedException BackBee\Bundle\Exception\BundleConfigurationException
     * @expectedExceptionMessage controller controller is undefined
     */
    public function testResolveNoController()
    {
        $bundle = $this->getBundle('mockbundle');
        $bundle->getConfig()->setSection('bundle', ['admin_controller' => 'action']);
        $this->container->set('bundle.mockbundle', $bundle);

        $this->resolver->resolve('mockbundle', 'controller');
    }

    /**
     * @covers BackBee\Bundle\BundleControllerResolver::__construct()
     * @covers BackBee\Bundle\BundleControllerResolver::resolve()
     * @covers BackBee\Bundle\BundleControllerResolver::computeBundleName()
     */
    public function testResolve()
    {
        $bundle = $this->getBundle('mockbundle');
        $bundle->getConfig()->setSection('bundle', [
            'admin_controller' => [
                'controller' => 'BackBee\Bundle\Tests\Mock\BundleControllerMock'
            ]
        ]);

        $this->container->set('bundle.mockbundle', $bundle);

        $controller = $this->resolver->resolve('mockbundle', 'controller');

        $this->assertInstanceOf(Mock\BundleControllerMock::class, $controller);
    }

    /**
     * @covers BackBee\Bundle\BundleControllerResolver::resolveBaseAdminUrl()
     */
    public function testResolveBaseAdminUrl()
    {
        $this->assertEquals(
            '/bundle/bundle/controller/action',
            $this->resolver->resolveBaseAdminUrl('bundle', 'controller', 'action')
        );
    }
}
