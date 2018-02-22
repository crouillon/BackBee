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

namespace BackBee\Stream\Tests\Adapter;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use BackBee\Cache\CacheInterface;
use BackBee\Stream\Adapter\Yaml;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for adapter class Yaml.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Stream\Adapter\Yaml
 */
class YamlTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var Yaml
     */
    private $adapter;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $root = [
            'dir1' => [
                'Namespace' => [
                    'Fake1.yml' => '{"invalid":"invalid"}',
                    'Valid.yml' => '{"valid":{"properties":{"name":"valid"}}}'
                ]
            ],
            'dir2' => [
                'Namespace' => [
                    'Fake2.yaml' => '{"invalid":{"invalid":"invalid"}}'
                ]
            ],
        ];
        $this->root = vfsStream::setup('root', 0700, $root);

        $options = [
            'pathinclude' => [vfsStream::url('root/dir1'), vfsStream::url('root/dir2')],
            'extensions' => ['.yml', '.yaml'],
            'cache' => null
        ];

        $this->adapter = new Yaml();
        $this->adapter->context = stream_context_create([Yaml::PROTOCOL => $options]);
    }

    /**
     * @covers                   ::stream_open()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Invalid mode for opening path, only `r` and `rb` are allowed.
     */
    public function testInvalidOpenMode()
    {
        $openedPath = '';
        $this->assertFalse($this->adapter->stream_open('path', 'w', STREAM_REPORT_ERRORS, $openedPath));
    }

    /**
     * @covers                   ::stream_open()
     * @expectedException        \BackBee\AutoLoader\Exception\ClassNotFoundException
     * @expectedExceptionMessage Cannot open unknown, file not found.
     */
    public function testUnknownOpen()
    {
        $openedPath = '';
        $this->assertFalse($this->adapter->stream_open('unknown', 'r', STREAM_REPORT_ERRORS, $openedPath));
    }

    /**
     * @covers                   ::stream_open()
     * @expectedException        \Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage No valid class content description found
     */
    public function testMalformedOpen()
    {
        $openedPath = '';
        $this->assertFalse(
            $this->adapter->stream_open(
                'bb.class://Namespace/Fake1',
                'r',
                STREAM_REPORT_ERRORS,
                $openedPath
            )
        );
    }

    /**
     * @covers ::stream_open()
     * @covers ::parseFile()
     * @covers ::parseDefinition()
     */
    public function testStreamOpen()
    {
        $openedPath = '';
        $this->assertTrue(
            $this->adapter->stream_open(
                'bb.class://Namespace/Valid',
                'r',
                STREAM_REPORT_ERRORS+STREAM_USE_PATH,
                $openedPath
            )
        );
        $this->assertEquals(vfsStream::url('root/dir1/Namespace/Valid.yml'), $openedPath);
        $this->assertEquals(0, $this->adapter->stream_tell());
        $this->assertContains('class Valid extends', $this->invokeProperty($this->adapter, 'data'));
    }

    /**
     * @covers ::stream_stat()
     */
    public function testStreamStat()
    {
        $this->assertFalse($this->adapter->stream_stat());

        $this->invokeProperty($this->adapter, 'filename', vfsStream::url('root/dir1/Namespace/Valid.yml'));
        $this->assertTrue(is_array($this->adapter->stream_stat()));
    }

    /**
     * @covers ::url_stat()
     */
    public function testUrlStat()
    {
        $this->assertFalse($this->adapter->url_stat('bb.class://Unknown/Class', STREAM_URL_STAT_QUIET));

        $this->assertEquals(
            stat(vfsStream::url('root/dir1/Namespace/Fake1.yml')),
            $this->adapter->url_stat('bb.class://Namespace/Fake1')
        );

        $this->assertEquals(
            lstat(vfsStream::url('root/dir2/Namespace/Fake2.yaml')),
            $this->adapter->url_stat('bb.class://Namespace/Fake2', STREAM_URL_STAT_LINK)
        );
    }

    /**
     * @covers            ::url_stat()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testWarningUrlStat()
    {
        $this->assertFalse($this->adapter->url_stat('bb.class://Unknown/Class'));
    }

    /**
     * @covers ::stream_close()
     */
    public function testStreamClose()
    {
        $this->invokeProperty($this->adapter, 'filename', 'filename');
        $this->adapter->stream_close();
        $this->assertNull($this->invokeProperty($this->adapter, 'filename'));
    }

    /**
     * @covers ::glob()
     */
    public function testGlob()
    {
        $expected = [
            'BackBee\ClassContent\Element\Attachment',
            'BackBee\ClassContent\Element\Date',
            'BackBee\ClassContent\Element\File',
            'BackBee\ClassContent\Element\Image',
            'BackBee\ClassContent\Element\Keyword',
            'BackBee\ClassContent\Element\Link',
            'BackBee\ClassContent\Element\Select',
            'BackBee\ClassContent\Element\Text'
        ];

        $options = [
            'pathinclude' => dirname(__DIR__) . '/../../ClassContent',
            'extensions' => ['.yml']
        ];
        $this->adapter->context = stream_context_create(['bb.class' => $options]);

        $this->assertEquals($expected, $this->adapter->glob('Element\*'));
    }

    /**
     * @covers ::getClassname()
     */
    public function testGetClassname()
    {
        $this->invokeProperty($this->adapter, 'filename', 'test\with/filename.yml');
        $this->assertEquals('filename', $this->invokeMethod($this->adapter, 'getClassname'));
    }

    /**
     * @covers ::setNamespace()
     */
    public function testSetNamespace()
    {
        $this->invokeMethod($this->adapter, 'setNamespace', ['bb.class://Test\With/This/filename.yml']);
        $this->assertEquals('BackBee\ClassContent\Test\With\This', $this->invokeProperty($this->adapter, 'namespace'));
    }

    /**
     * @covers                   ::parseFile()
     * @expectedException        \Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage No valid class content description found
     */
    public function testParseUnknownFile()
    {
        $this->invokeProperty($this->adapter, 'filename', vfsStream::url('root/dir1/Namespace/Unknown.yml'));
        $this->invokeMethod($this->adapter, 'parseFile');
    }

    /**
     * @covers                   ::parseFile()
     * @expectedException        \Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage No valid class content description found
     */
    public function testParseInvalidFile()
    {
        $this->invokeProperty($this->adapter, 'filename', vfsStream::url('root/dir1/Namespace/Fake1.yml'));
        $this->invokeMethod($this->adapter, 'parseFile');
    }

    /**
     * @covers                   ::parseDefinition()
     * @expectedException        \Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage Unknown property type invalid.
     */
    public function testParseMalformedFile()
    {
        $this->invokeProperty($this->adapter, 'filename', vfsStream::url('root/dir2/Namespace/Fake2.yaml'));
        $this->invokeMethod($this->adapter, 'parseFile');
    }

    /**
     * @covers ::resolveFilePath()
     */
    public function testResolveFilePath()
    {
        $this->assertFalse($this->invokeMethod($this->adapter, 'resolveFilePath', ['bb.class://Unknown/Class']));

        $this->assertEquals(
            vfsStream::url('root/dir1/Namespace/Fake1.yml'),
            $this->invokeMethod($this->adapter, 'resolveFilePath', ['bb.class://Namespace/Fake1'])
        );

        $this->assertEquals(
            vfsStream::url('root/dir2/Namespace/Fake2.yaml'),
            $this->invokeMethod($this->adapter, 'resolveFilePath', ['bb.class://Namespace\Fake2'])
        );
    }

    /**
     * @covers ::stream_open()
     * @covers ::getCacheAdapter()
     * @covers ::readFromCache()
     */
    public function testValidCache()
    {
        $cache = $this->getMockForAbstractClass(
            CacheInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['load', 'save']
        );
        $cache->expects($this->once())->method('load')->willReturn(true);
        $cache->expects($this->never())->method('save');

        $options = [
            'pathinclude' => [vfsStream::url('root/dir1'), vfsStream::url('root/dir2')],
            'extensions' => ['.yml', '.yaml'],
            'cache' => $cache
        ];

        $this->adapter->context = stream_context_create([Yaml::PROTOCOL => $options]);
        $this->assertTrue(
            $this->adapter->stream_open(
                'bb.class://Namespace/Valid',
                'r',
                STREAM_REPORT_ERRORS+STREAM_USE_PATH,
                $openedPath
            )
        );
    }

    /**
     * @covers ::stream_open()
     * @covers ::getCacheAdapter()
     * @covers ::readFromCache()
     * @covers ::saveToCache()
     */
    public function testNoValidCache()
    {
        $cache = $this->getMockForAbstractClass(
            CacheInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['load', 'save']
        );
        $cache->expects($this->once())->method('load')->willReturn(false);
        $cache->expects($this->once())->method('save');

        $options = [
            'pathinclude' => [vfsStream::url('root/dir1'), vfsStream::url('root/dir2')],
            'extensions' => ['.yml', '.yaml'],
            'cache' => $cache
        ];

        $this->adapter->context = stream_context_create([Yaml::PROTOCOL => $options]);
        $this->assertTrue(
            $this->adapter->stream_open(
                'bb.class://Namespace/Valid',
                'r',
                STREAM_REPORT_ERRORS+STREAM_USE_PATH,
                $openedPath
            )
        );
    }

    /**
     * @covers ::dispatchEvents()
     */
    public function testEventDisptacher()
    {
        $dispatcher = $this->getMockForAbstractClass(
            EventDispatcherInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['dispatch']
        );
        $dispatcher->expects($this->at(0))->method('dispatch')->with('namespace.valid.streamparsing');
        $dispatcher->expects($this->at(1))->method('dispatch')->with('classcontent.streamparsing');

        $options = [
            'pathinclude' => [vfsStream::url('root/dir1'), vfsStream::url('root/dir2')],
            'extensions' => ['.yml', '.yaml'],
            'dispatcher' => $dispatcher
        ];

        $this->adapter->context = stream_context_create([Yaml::PROTOCOL => $options]);
        $this->assertTrue(
            $this->adapter->stream_open(
                'bb.class://Namespace/Valid',
                'r',
                STREAM_REPORT_ERRORS+STREAM_USE_PATH,
                $openedPath
            )
        );
    }
}
