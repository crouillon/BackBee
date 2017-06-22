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
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for BackBee AutoLoader.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AutoLoaderTest extends BackBeeTestCase
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

        $this->autoloader = self::$app->getAutoloader();
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::__construct()
     * @covers BackBee\AutoLoader\AutoLoader::setApplication()
     * @covers BackBee\AutoLoader\AutoLoader::getApplication()
     * @covers BackBee\AutoLoader\AutoLoader::setEventDispatcher()
     * @covers BackBee\AutoLoader\AutoLoader::getEventDispatcher()
     */
    public function testConstruct()
    {
        $autoloader = new AutoLoader(self::$app, self::$app->getEventDispatcher());
        $this->assertEquals(self::$app, $autoloader->getApplication());
        $this->assertEquals(
            self::$app->getEventDispatcher(),
            $autoloader->getEventDispatcher()
        );
        $this->assertFalse($autoloader->isRestored());
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::autoload()
     * @covers BackBee\AutoLoader\AutoLoader::normalizeClassname()
     * @expectedException BackBee\AutoLoader\Exception\InvalidNamespaceException
     */
    public function testAutoloadWrongNamespaceSyntax()
    {
        $this->autoloader->autoload('\Wrong-Namespace\ClassName');
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::autoload()
     * @covers BackBee\AutoLoader\AutoLoader::normalizeClassname()
     * @expectedException BackBee\AutoLoader\Exception\InvalidClassnameException
     */
    public function testAutoloadWrongClassnameSyntax()
    {
        $this->autoloader->autoload('3rrorClassName');
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::autoload()
     * @expectedException BackBee\AutoLoader\Exception\ClassNotFoundException
     */
    public function testAutoloadUnknownClassContentClass()
    {
        $this->autoloader->autoload('BackBee\ClassContent\Unknown\Classname');
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::autoload()
     * @expectedException BackBee\AutoLoader\Exception\ClassNotFoundException
     */
    public function testAutoloadUnknownHandleClass()
    {
        $this->autoloader->autoload('BackBee\Event\Listener\UnknownListener');
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::autoload()
     */
    public function testAutoloadUnknownClass()
    {
        $this->assertNull(
            $this->autoloader->autoload('\Unknown\Classname')
        );
    }

    public function testAutoloadThrowWrapper()
    {
        $this->assertNull(
            $this->autoloader->autoload('BackBee\ClassContent\Element\Text')
        );
    }

    public function testAutoloadThrowFileSystem()
    {
        $this->autoloader->registerListenerNamespace(__DIR__);

        $this->assertNull(
            $this->autoloader->autoload('BackBee\Event\Listener\FakeListener')
        );
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::getStreamWrapperClassname()
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
            ['\BackBee\Stream\ClassWrapper\Adapter\Yaml'],
            $this->autoloader->getStreamWrapperClassname('BackBee\ClassContent', 'bb.class')
        );
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::glob()
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
     * @covers BackBee\AutoLoader\AutoLoader::register()
     */
    public function testRegister()
    {
        $autoloader = new AutoLoader();
        $autoloader->register();

        $functions = spl_autoload_functions();
        $this->assertEquals($autoloader, end($functions)[0]);
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::registerNamespace()
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
     * @covers BackBee\AutoLoader\AutoLoader::registerListenerNamespace()
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
     * @covers BackBee\AutoLoader\AutoLoader::registerStreamWrapper()
     * @covers BackBee\AutoLoader\AutoLoader::registerStreams()
     */
    public function testRegisterStreamWrapper()
    {
        $autoloader = new AutoLoader();
        $this->assertEquals($autoloader, $autoloader->registerStreamWrapper(
            'BackBee\ClassContent',
            'test.protocol',
            'BackBee\Stream\ClassWrapper\Adapter\Yaml'
        ));
        $this->assertTrue(in_array('test.protocol', stream_get_wrappers()));
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::getClassProxy()
     */
    public function testGetClassProxy()
    {
        $this->assertNull($this->autoloader->getClassProxy());
    }

    /**
     * @covers BackBee\AutoLoader\AutoLoader::dump()
     */
    public function testDump()
    {
        $autoloader = new AutoLoader();
        $autoloader->registerNamespace('BackBee\Fake', __DIR__)
            ->registerStreamWrapper(
                'BackBee\ClassContent',
                'test.protocol',
                'BackBee\Stream\ClassWrapper\Adapter\Yaml'
            );

        $expected = [
            'namespaces_locations' => [
                'BackBee\Fake' => [__DIR__], 'BackBee\ClassContent' => []
            ],
            'wrappers_namespaces' => [
                'BackBee\ClassContent' => [[
                    'protocol' => 'test.protocol',
                    'classname' => 'BackBee\Stream\ClassWrapper\Adapter\Yaml'
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
                    'classname' => 'BackBee\Stream\ClassWrapper\Adapter\Yaml'
                ]]
            ],
            'has_event_dispatcher' => true
        ];

        $autoloader = new AutoLoader();
        $this->assertFalse($autoloader->isRestored());

        $autoloader->restore(self::$app->getContainer(), $dump);
        $this->assertTrue($autoloader->isRestored());
        $this->assertEquals($dump, $autoloader->dump());
        $this->assertEquals(self::$app->getEventDispatcher(), $autoloader->getEventDispatcher());
    }
}
