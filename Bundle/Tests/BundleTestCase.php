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

use org\bovigo\vfs\vfsStream;

use BackBee\BBApplication;
use BackBee\Bundle\AbstractBundle;
use BackBee\Bundle\BundleLoader;
use BackBee\Config\Config;
use BackBee\DependencyInjection\Container;
use BackBee\Tests\BackBeeTestCase;

/**
 * TestCase for Bundle package.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BundleTestCase extends BackBeeTestCase
{
    /**
     * @var BBApplication
     */
    protected $application;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var BundleLoader
     */
    protected $bundleLoader;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $mockDir = ['Config' => ['config.yml' => '{"bundle":{"name":"mock"}}']];
        vfsStream::umask(0000);
        vfsStream::setup('MockBundle', 0777, $mockDir);

        $this->application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer', 'getEntityManager', 'unshiftClassContentDir', 'pushResourceDir'])
            ->getMock();

        $this->bundleLoader = $this->getMockBuilder(BundleLoader::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildBundleBaseDirectoryFromClassname', 'loadConfigDefinition'])
            ->getMock();

        $config = new Config(vfsStream::url('MockBundle/Config'));
        $this->container = new Container();
        $this->container->set('bundle.loader', $this->bundleLoader);
        $this->container->set('bundle.mockbundle.config', $config);

        $this->application->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->container));

        $this->bundleLoader->expects($this->any())
            ->method('loadConfigDefinition')
            ->will($this->returnValue(null));

        $this->bundleLoader->expects($this->any())
            ->method('buildBundleBaseDirectoryFromClassname')
            ->will($this->returnValue(vfsStream::url('MockBundle')));
    }

    /**
     * Returns a mock instance of AbstractBundle.
     *
     * @param  string|null $id
     * @param  string|null $dir
     *
     * @return AbstractBundle
     */
    protected function getBundle($id = null, $dir = null)
    {
        return $this->getMockForAbstractClass(
            AbstractBundle::class,
            [$this->application, $id, $dir]
        );
    }
}
