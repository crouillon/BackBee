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

namespace BackBee\Bundle\Tests;

use Doctrine\ORM\EntityManager;
use org\bovigo\vfs\vfsStream;

use BackBee\BBApplication;
use BackBee\Bundle\AbstractBundle;
use BackBee\Bundle\BundleLoader;
use BackBee\Config\Config;
use BackBee\DependencyInjection\Container;
use BackBee\Renderer\Renderer;
use BackBee\Tests\BackBeeTestCase;

/**
 * TestCase for Bundle package.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BundleTestCase extends \PHPUnit_Framework_TestCase
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

        $this->application = $this->getMockApplication();
        $this->bundleLoader = $this->getMockBundleLoader();

        $config = new Config(vfsStream::url('MockBundle/Config'));
        $this->container = new Container();
        $this->container->set('bundle.loader', $this->bundleLoader);
        $this->container->set('bundle.mockbundle.config', $config);

        $this->application->expects($this->any())
            ->method('getContainer')
            ->willReturn($this->container);

        $this->bundleLoader->expects($this->any())
            ->method('buildBundleBaseDirectoryFromClassname')
            ->will($this->returnValue(vfsStream::url('MockBundle')));
    }

    /**
     * @return BBApplication
     */
    protected function getMockApplication()
    {
        return $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getLogging',
                'getRouting',
                'getContainer',
                'getEntityManager',
                'unshiftClassContentDir',
                'pushResourceDir',
                'isDebugMode',
                'getRenderer',
                'getSession',
                'getRequest'
            ])
            ->getMock();
    }

    /**
     * @return BundleLoader
     */
    protected function getMockBundleLoader()
    {
        return $this->getMockBuilder(BundleLoader::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'buildBundleBaseDirectoryFromClassname',
                'loadConfigDefinition'
            ])
            ->getMock();
    }

    /**
     * @return EntityManager
     */
    protected function getMockEntityManager()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRepository',
                'getConnection',
                'getConfiguration',
                'getMetadataFactory'
            ])
            ->getMock();
    }

    /**
     * @return Renderer
     */
    protected function getMockRenderer()
    {
        return $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->setMethods(['partial'])
            ->getMock();
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
