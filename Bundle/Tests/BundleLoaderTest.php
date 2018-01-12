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

use org\bovigo\vfs\vfsStream;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;

use BackBee\Bundle\BundleLoader;
use BackBee\Bundle\DemoBundle\Demo;
use BackBee\Bundle\Event\BundleInstallUpdateEvent;
use BackBee\Cache\NoCache\Cache;
use BackBee\Config\Config;
use BackBee\Config\Configurator;
use BackBee\Controller\FrontController;
use BackBee\Renderer\Renderer;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Util\Doctrine\EntityManagerCreator;

/**
 * Tests suite for class BundleLoader
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\BundleLoader
 */
class BundleLoaderTest extends BundleTestCase
{
    use InvokeMethodTrait;

    /**
     * @var BundleLoader
     */
    private $loader;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->loader = new BundleLoader($this->application);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $controller = $this->getMockBuilder(FrontController::class)
                ->disableOriginalConstructor()
                ->setMethods(['registerRoutes'])
                ->getMock();

        $renderer = $this->getMockBuilder(Renderer::class)
                ->disableOriginalConstructor()
                ->setMethods(['addScriptDir', 'addHelperDir'])
                ->getMock();

        $this->application
                ->expects($this->any())
                ->method('getRenderer')
                ->willReturn($renderer);

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
                ->setMethods(['dispatch', 'addListeners'])
                ->getMock();

        $this->container->set('config', $config);
        $this->container->set('renderer', $renderer);
        $this->container->set('controller', $controller);
        $this->container->set('event.dispatcher', $dispatcher);
        $this->container->set('cache.bootstrap', new Cache());
        $this->container->set('config.configurator', new Configurator($this->application, $this->loader));

        $this->container->setParameter('debug', false);
        $this->container->setParameter('bbapp.environment', null);
        $this->container->setParameter('container.autogenerate', true);
        $this->container->setParameter('config.yml_files_to_ignore', []);
    }

    /**
     * @covers ::load()
     * @covers ::dump()
     * @covers ::getBundleIdByBaseDir()
     * @covers ::loadFullBundles()
     */
    public function testLoad()
    {
        $this->loader->load(['test' => Demo::class]);

        $baseDir = $this->invokeMethod(
            $this->loader,
            'buildBundleBaseDirectoryFromClassname',
            [Demo::class]
        );

        $expected = [
            'bundleInfos' => ['test' => [
                'main_class' => Demo::class,
                'base_dir' => $baseDir
            ]]
        ];

        $this->assertEquals($expected, $this->loader->dump());
        $this->assertEquals('test', $this->loader->getBundleIdByBaseDir($baseDir . 'suffix'));
        $this->assertNull($this->loader->getBundleIdByBaseDir('prefix' . $baseDir));
    }

    /**
     * @covers ::__construct()
     * @covers ::buildBundleBaseDirectoryFromClassname()
     */
    public function testBuildBundleBaseDirectoryFromClassname()
    {
        $basedir = $this->invokeMethod(
            $this->loader,
            'buildBundleBaseDirectoryFromClassname',
            [get_class($this)]
        );

        $this->assertEquals(__DIR__, $basedir);
    }

    /**
     * @covers ::loadConfigDefinition()
     */
    public function testLoadConfigDefinition()
    {
        $bundle = $this->getBundle();

        $this->assertFalse($this->container->hasDefinition('bundle.test.config'));
        $this->loader->loadConfigDefinition('bundle.test.config', $bundle->getBaseDirectory());
        $this->assertTrue($this->container->hasDefinition('bundle.test.config'));
    }

    /**
     * @covers ::installBundle()
     */
    public function testInstallBundle()
    {
        $dispatcher = $this->container->get('event.dispatcher');
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->stringEndsWith('install'), $this->isInstanceOf(BundleInstallUpdateEvent::class));

        $this->assertEquals(['sql' => []], $this->loader->installBundle($this->getBundle()));
    }

    /**
     * @covers ::updateBundle()
     */
    public function testUpdateBundle()
    {
        $dispatcher = $this->container->get('event.dispatcher');
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->stringEndsWith('update'), $this->isInstanceOf(BundleInstallUpdateEvent::class));

        $this->assertEquals(['sql' => []], $this->loader->updateBundle($this->getBundle()));
    }

    /**
     * @covers ::createEntitiesSchema()
     */
    public function testCreateEntitiesSchema()
    {
        $bundle = $this->getBundle();

        $entityMng = EntityManagerCreator::create([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'proxy_dir' => vfsStream::url('MockBundle/Proxies'),
            'proxy_ns' => '__TEST__'
        ]);

        $this->application
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityMng);

        $this->assertEquals([], $this->invokeMethod($this->loader, 'createEntitiesSchema', [$bundle]));

        mkdir($bundle->getBaseDirectory() . '/Entity', 0777);
        $this->assertEquals([], $this->invokeMethod($this->loader, 'createEntitiesSchema', [$bundle, true]));
    }

    /**
     * @covers ::updateEntitiesSchema()
     */
    public function testUpdateEntitiesSchema()
    {
        $bundle = $this->getBundle();

        $entityMng = EntityManagerCreator::create([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'proxy_dir' => vfsStream::url('MockBundle/Proxies'),
            'proxy_ns' => '__TEST__'
        ]);

        $this->application
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityMng);

        $this->assertEquals([], $this->invokeMethod($this->loader, 'updateEntitiesSchema', [$bundle]));

        mkdir($bundle->getBaseDirectory() . '/Entity', 0777);
        $this->assertEquals([], $this->invokeMethod($this->loader, 'updateEntitiesSchema', [$bundle, true]));
    }

    /**
     * @covers ::getBundleEntityDir()
     */
    public function testGetBundleEntityDir()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(
            $bundle->getBaseDirectory() . '/Entity',
            $this->invokeMethod($this->loader, 'getBundleEntityDir', [$bundle])
        );
    }

    /**
     * @covers ::generateBundleServiceId()
     */
    public function testGenerateBundleServiceId()
    {
        $this->assertEquals(
            'bundle.test',
            $this->invokeMethod($this->loader, 'generateBundleServiceId', ['TeSt'])
        );
    }

    /**
     * @covers ::buildBundleDefinition()
     */
    public function testBuildBundleDefinition()
    {
        $def = $this->invokeMethod(
            $this->loader,
            'buildBundleDefinition',
            [Demo::class, 'test', __DIR__]
        );

        $this->assertInstanceOf(Definition::class, $def);
        $this->assertTrue($def->hasTag('bundle'));
        $this->assertEquals([['start', []], ['started', []]], $def->getMethodCalls());
    }

    /**
     * @covers            ::buildBundleDefinition()
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testBuildInvalidBundleDefinition()
    {
        $this->invokeMethod(
            $this->loader,
            'buildBundleDefinition',
            [get_class($this), 'test', __DIR__]
        );
    }

    /**
     * @covers ::loadAndGetBundleConfigByBaseDir()
     */
    public function testLoadAndGetBundleConfigByBaseDir()
    {
        file_put_contents(
            vfsStream::url('MockBundle/Config/config.yml'),
            '{"bundle":{"name":"mock","config_per_site":true}}'
        );

        $service = $this->invokeMethod(
            $this->loader,
            'loadAndGetBundleConfigByBaseDir',
            ['test', vfsStream::url('MockBundle')]
        );

        $this->assertInstanceOf(Config::class, $service);
        $this->assertTrue($this->container->findDefinition('test.config')->hasTag('config_per_site'));
    }

    /**
     * @covers ::buildConfigDefinition()
     */
    public function testBuildConfigDefinition()
    {
        $bundle = $this->getBundle();

        $def = $this->invokeMethod(
            $this->loader,
            'buildConfigDefinition',
            [$bundle->getBaseDirectory()]
        );

        $this->assertInstanceOf(Definition::class, $def);
        $this->assertEquals(Config::class, $def->getClass());
        $this->assertEquals(['dumpable', 'bundle.config'], array_keys($def->getTags()));
    }

    /**
     * @covers ::getConfigDirByBundleBaseDir()
     */
    public function testGetConfigDirByBundleBaseDir()
    {
        $bundle = $this->getBundle();

        $expected = $bundle->getBaseDirectory() . DIRECTORY_SEPARATOR . 'Config';
        $this->assertEquals(
            $expected,
            $this->invokeMethod(
                $this->loader,
                'getConfigDirByBundleBaseDir',
                [$bundle->getBaseDirectory()]
            )
        );

        rename($expected, $bundle->getBaseDirectory() . DIRECTORY_SEPARATOR . 'Ressources');
        $this->assertEquals(
            $bundle->getBaseDirectory() . DIRECTORY_SEPARATOR . 'Ressources',
            $this->invokeMethod(
                $this->loader,
                'getConfigDirByBundleBaseDir',
                [$bundle->getBaseDirectory()]
            )
        );
    }

    /**
     * @covers ::getLoaderRecipesByConfig()
     */
    public function testGetLoaderRecipesByConfig()
    {
        $this->assertNull($this->invokeMethod(
            $this->loader,
            'getLoaderRecipesByConfig',
            [new Config(vfsStream::url('MockBundle/Config'))]
        ));

        file_put_contents(
            vfsStream::url('MockBundle/Config/config.yml'),
            '{"bundle":{"name":"mock","bundle_loader_recipes":"bundle_loader_recipes"}}'
        );

        $this->assertEquals(
            'bundle_loader_recipes',
            $this->invokeMethod(
                $this->loader,
                'getLoaderRecipesByConfig',
                [new Config(vfsStream::url('MockBundle/Config'))]
            )
        );
    }

    /**
     * @covers ::getCallbackFromRecipes()
     */
    public function testGetCallbackFromRecipes()
    {
        $this->assertNull($this->invokeMethod($this->loader, 'getCallbackFromRecipes', ['key', []]));

        $expected = function () {
            return true;
        };

        $this->assertEquals(
            $expected,
            $this->invokeMethod($this->loader, 'getCallbackFromRecipes', ['key', ['key' => $expected]])
        );
    }

    /**
     * @covers ::loadServices()
     */
    public function testLoadServices()
    {
        copy(__DIR__ . '/Config/services.yml', vfsStream::url('MockBundle/Config/services.yml'));
        copy(__DIR__ . '/Config/services.xml', vfsStream::url('MockBundle/Config/services.xml'));

        $this->invokeMethod($this->loader, 'loadServices', [new Config(vfsStream::url('MockBundle/Config')), 'test']);

        $this->assertTrue($this->container->has('listener.yml'));
        $this->assertTrue($this->container->has('listener.xml'));
    }

    /**
     * @covers ::loadEvents()
     */
    public function testLoadEvents()
    {
        file_put_contents(
            vfsStream::url('MockBundle/Config/config.yml'),
            '{"bundle":{"name":"mock"},"events":{"event.name":{"listeners":[]}}}'
        );

        $dispatcher = $this->container->get('event.dispatcher');
        $dispatcher->expects($this->once())
            ->method('addListeners');

        $this->invokeMethod($this->loader, 'loadEvents', [new Config(vfsStream::url('MockBundle/Config'))]);
    }

    /**
     * @covers ::addClassContentDir()
     */
    public function testAddClassContentDir()
    {
        mkdir(vfsStream::url('MockBundle/ClassContent'), 0777);

        $this->application
            ->expects($this->once())
            ->method('unshiftClassContentDir');

        $this->invokeMethod($this->loader, 'addClassContentDir', [new Config(vfsStream::url('MockBundle/Config'))]);
    }

    /**
     * @covers ::addTemplatesDir()
     */
    public function testAddTemplatesDir()
    {
        mkdir(vfsStream::url('MockBundle/Templates/scripts'), 0777, true);

        $this->application
            ->getRenderer()
            ->expects($this->once())
            ->method('addScriptDir')
            ->with(vfsStream::url('MockBundle/Templates/scripts'));

        $this->invokeMethod($this->loader, 'addTemplatesDir', [new Config(vfsStream::url('MockBundle/Config'))]);
    }

    /**
     * @covers ::addHelpersDir()
     */
    public function testAddHelpersDir()
    {
        mkdir(vfsStream::url('MockBundle/Templates/helpers'), 0777, true);

        $this->application
            ->getRenderer()
            ->expects($this->once())
            ->method('addHelperDir')
            ->with(vfsStream::url('MockBundle/Templates/helpers'));

        $this->invokeMethod($this->loader, 'addHelpersDir', [new Config(vfsStream::url('MockBundle/Config'))]);
    }

    /**
     * @covers ::loadRoutes()
     */
    public function testLoadRoutes()
    {
        file_put_contents(
            vfsStream::url('MockBundle/Config/config.yml'),
            '{"bundle":{"name":"mock"},"route":{"route.name":{}}}'
        );

        $this->application
            ->getController()
            ->expects($this->once())
            ->method('registerRoutes');

        $this->invokeMethod($this->loader, 'loadRoutes', [new Config(vfsStream::url('MockBundle/Config'))]);
    }

    /**
     * @covers ::addResourcesDir()
     */
    public function testAddResourcesDir()
    {
        $this->application
            ->expects($this->exactly(2))
            ->method('pushResourceDir');

        mkdir(vfsStream::url('MockBundle/Resources'), 0777);
        $this->invokeMethod($this->loader, 'addResourcesDir', [new Config(vfsStream::url('MockBundle/Config'))]);

        rename(vfsStream::url('MockBundle/Resources'), vfsStream::url('MockBundle/Ressources'));
        $this->invokeMethod($this->loader, 'addResourcesDir', [new Config(vfsStream::url('MockBundle/Config'))]);
    }

    /**
     * @covers ::runRecipe()
     */
    public function testRunRecipe()
    {
        $callable = function () {
            return true;
        };

        $this->assertFalse($this->invokeMethod(
            $this->loader,
            'runRecipe',
            [new Config(vfsStream::url('MockBundle/Config'))]
        ));

        $this->assertTrue($this->invokeMethod(
            $this->loader,
            'runRecipe',
            [new Config(vfsStream::url('MockBundle/Config')), $callable]
        ));
    }

    /**
     * @covers ::getClassProxy()
     */
    public function testGetClassProxy()
    {
        $this->assertNull($this->loader->getClassProxy());
    }

    /**
     * @covers ::restore()
     * @covers ::isRestored()
     */
    public function testRestore()
    {
        $baseDir = $this->invokeMethod(
            $this->loader,
            'buildBundleBaseDirectoryFromClassname',
            [Demo::class]
        );

        $expected = [
            'bundleInfos' => ['test' => [
                'main_class' => Demo::class,
                'base_dir' => $baseDir
            ]]
        ];

        $loader = new BundleLoader($this->application);
        $loader->restore($this->container, $expected);

        $this->assertEquals($expected, $loader->dump());
        $this->assertTrue($loader->isRestored());
    }
}
