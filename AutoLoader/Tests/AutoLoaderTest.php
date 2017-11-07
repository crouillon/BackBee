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

namespace BackBee\AutoLoader\Tests;

use BackBee\AutoLoader\AutoLoader;
use BackBee\BBApplication;
use BackBee\ClassContent\AbstractContent;
use BackBee\DependencyInjection\Container;
use BackBee\Event\Dispatcher;
use BackBee\Stream\Adapter\Yaml;

/**
 * Tests suite for BackBee AutoLoader.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\AutoLoader\AutoLoader
 */
class AutoLoaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AutoLoader
     */
    private $autoloader;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->autoloader = new AutoLoader();
        $this->autoloader
            ->register()
            ->registerStreamWrapper(
                AbstractContent::CLASSCONTENT_BASE_NAMESPACE,
                Yaml::PROTOCOL,
                Yaml::class
            )
            ->registerNamespace(
                'BackBee\Event\Listener',
                dirname(__DIR__) . '/../Listener'
            );
    }

    /**
     * @covers            ::__construct()
     * @expectedException \BadMethodCallException
     */
    public function testInvalidConstruct()
    {
        new AutoLoader('string');
    }

    /**
     * @covers ::__construct()
     * @covers ::setEventDispatcher()
     * @covers ::getEventDispatcher()
     * @covers ::isRestored()
     */
    public function testConstruct()
    {
        $application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $oldSignature = new AutoLoader($application, $dispatcher);
        $this->assertEquals($dispatcher, $oldSignature->getEventDispatcher());

        $newSignature = new AutoLoader($dispatcher);
        $this->assertEquals($dispatcher, $newSignature->getEventDispatcher());

        $this->assertFalse($this->autoloader->isRestored());
    }

    /**
     * @covers            ::autoload()
     * @covers            ::normalizeClassname()
     * @expectedException \BackBee\AutoLoader\Exception\InvalidNamespaceException
     */
    public function testAutoloadWrongNamespaceSyntax()
    {
        $this->autoloader->autoload('\Wrong-Namespace\ClassName');
    }

    /**
     * @covers            ::autoload()
     * @covers            ::normalizeClassname()
     * @expectedException \BackBee\AutoLoader\Exception\InvalidClassnameException
     */
    public function testAutoloadWrongClassnameSyntax()
    {
        $this->autoloader->autoload('3rrorClassName');
    }

    /**
     * @covers            ::autoload()
     * @covers            ::autoloadThrowWrappers()
     * @covers            ::includeClass()
     * @expectedException \BackBee\AutoLoader\Exception\ClassNotFoundException
     */
    public function testAutoloadUnknownClassContentClass()
    {
        $this->autoloader->autoload('BackBee\ClassContent\Unknown\Classname');
    }

    /**
     * @covers            ::autoload()
     * @covers            ::autoloadThrowFilesystem()
     * @covers            ::scanPaths()
     * @expectedException \BackBee\AutoLoader\Exception\ClassNotFoundException
     */
    public function testAutoloadUnknownHandleClass()
    {
        $this->autoloader->autoload('BackBee\Event\Listener\UnknownListener');
    }

    /**
     * @covers ::autoload()
     * @covers ::autoloadThrowWrappers()
     * @covers ::autoloadThrowFilesystem()
     * @covers ::scanPaths()
     */
    public function testAutoloadUnknownClass()
    {
        $this->assertFalse(class_exists('\Unknown\Classname'));
    }

    /**
     * @covers ::autoload()
     * @covers ::autoloadThrowWrappers()
     * @covers ::includeClass()
     */
    public function testAutoloadThrowWrapper()
    {
        $this->assertTrue(class_exists('\BackBee\ClassContent\Element\Image'));
    }

    /**
     * @covers ::autoload()
     * @covers ::registerListenerNamespace()
     * @covers ::autoloadThrowFilesystem()
     * @covers ::scanPaths()
     * @covers ::normalizeClassname()
     */
    public function testAutoloadThrowFileSystem()
    {
        $this->assertEquals(
            $this->autoloader,
            $this->autoloader->registerListenerNamespace(__DIR__)
        );

        $this->assertNull($this->autoloader->autoload('\BackBee\Event\Listener\FakeListener'));
    }

    /**
     * @covers ::getStreamWrapperClassname()
     */
    public function testGetStreamWrapperClassname()
    {
        $this->assertEmpty($this->autoloader->getStreamWrapperClassname(
            'BackBee\ClassContent',
            'wrong.protocol'
        ));
        $this->assertEmpty($this->autoloader->getStreamWrapperClassname(
            'Wrong\ClassName',
            'bb.class'
        ));
        $this->assertEquals(
            [Yaml::class],
            $this->autoloader->getStreamWrapperClassname('BackBee\ClassContent', 'bb.class')
        );
    }

    /**
     * @covers ::glob()
     */
    public function testGlob()
    {
        $this->assertFalse($this->autoloader->glob('*\File', 'wrong.protocol'));
        $this->assertFalse($this->autoloader->glob('*\Unknown'));
        $this->assertEquals(
            ['BackBee\ClassContent\Element\File'],
            $this->autoloader->glob('*\File')
        );
        $this->assertEquals(
            ['BackBee\ClassContent\Element\File'],
            $this->autoloader->glob('*/File')
        );
    }

    /**
     * @covers ::register()
     */
    public function testRegister()
    {
        $autoloader = new AutoLoader();
        $autoloader->register();

        $functions = spl_autoload_functions();
        $this->assertEquals($autoloader, end($functions)[0]);
    }

    /**
     * @covers ::registerNamespace()
     */
    public function testRegisterNamespace()
    {
        $this->assertFalse(class_exists('BackBee\Fake\FakeClass'));
        $this->assertEquals(
            $this->autoloader,
            $this->autoloader->registerNamespace('BackBee\Fake', __DIR__)
        );
        $this->assertTrue(class_exists('BackBee\Fake\FakeClass'));
    }

    /**
     * @covers ::registerListenerNamespace()
     */
    public function testRegisterListenerNamespace()
    {
        $autoloader = new Autoloader();
        $this->assertEquals(
            $autoloader,
            $autoloader->registerListenerNamespace(__DIR__)
        );
        $autoloader->register();
        $this->assertTrue(class_exists('BackBee\Event\Listener\FakeListener'));
    }

    /**
     * @covers ::registerStreamWrapper()
     * @covers ::registerStreams()
     */
    public function testRegisterStreamWrapper()
    {
        $autoloader = new AutoLoader();
        $this->assertEquals($autoloader, $autoloader->registerStreamWrapper(
            'BackBee\ClassContent',
            'test.protocol',
            'BackBee\Stream\Adapter\Yaml'
        ));
        $this->assertTrue(in_array('test.protocol', stream_get_wrappers()));
    }

    /**
     * @covers ::getClassProxy()
     */
    public function testGetClassProxy()
    {
        $this->assertNull($this->autoloader->getClassProxy());
    }

    /**
     * @covers ::dump()
     */
    public function testDump()
    {
        $autoloader = new AutoLoader();
        $autoloader->registerNamespace('BackBee\Fake', __DIR__)
            ->registerStreamWrapper(
                'BackBee\ClassContent',
                'test.protocol',
                'BackBee\Stream\Adapter\Yaml'
            );

        $expected = [
            'namespaces_locations' => [
                'BackBee\Fake' => [__DIR__], 'BackBee\ClassContent' => []
            ],
            'wrappers_namespaces' => [
                'BackBee\ClassContent' => [[
                    'protocol' => 'test.protocol',
                    'classname' => 'BackBee\Stream\Adapter\Yaml'
                ]]
            ],
            'has_event_dispatcher' => false
        ];

        $this->assertEquals($expected, $autoloader->dump());
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::restore()
     * @covers BackBee\AutoLoader\AutoLoader::isRestored()
     */
    public function testRestore()
    {
        $dump = [
            'namespaces_locations' => [
                'BackBee\Fake' => [__DIR__], 'BackBee\ClassContent' => []
            ],
            'wrappers_namespaces' => [
                'BackBee\ClassContent' => [[
                    'protocol' => 'test.protocol',
                    'classname' => 'BackBee\Stream\Adapter\Yaml'
                ]]
            ],
            'has_event_dispatcher' => true
        ];

        $autoloader = new AutoLoader();
        $this->assertFalse($autoloader->isRestored());

        $dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = new Container();
        $container->set('event.dispatcher', $dispatcher);

        $autoloader->restore($container, $dump);
        $this->assertTrue($autoloader->isRestored());
        $this->assertEquals($dump, $autoloader->dump());
        $this->assertEquals($dispatcher, $autoloader->getEventDispatcher());
    }
}
