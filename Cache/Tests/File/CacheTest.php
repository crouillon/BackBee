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

namespace BackBee\Cache\Tests\File;

use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;

use BackBee\Cache\File\Cache;

/**
 * Tests suite for class File\Cache
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();

        vfsStream::setup('root', 0777);

        $this->cache = new Cache(['cachedir' => vfsStream::url('root')], 'test', $this->logger);
    }

    /**
     * @covers            BackBee\Cache\File\Cache::__construct()
     * @expectedException \BackBee\Cache\Exception\CacheException
     */
    public function testInvalidCacheDir()
    {
        vfsStream::setup('invalid', 0000);
        new Cache(['cachedir' => vfsStream::url('invalid')]);
    }

    /**
     * @covers            BackBee\Cache\File\Cache::__construct()
     * @expectedException \BackBee\Cache\Exception\CacheException
     */
    public function testInvalidCacheDirWithContext()
    {
        vfsStream::setup('invalid', 0000);
        new Cache(['cachedir' => vfsStream::url('invalid')], 'test');
    }

    /**
     * @covers BackBee\Cache\File\Cache::save()
     */
    public function testSave()
    {
        $this->logger
            ->expects($this->exactly(2))
            ->method('log');

        $this->assertTrue($this->cache->save('id', 'data', 10, 'tag'));
        $this->assertTrue(file_exists(vfsStream::url('root/test/id')));
    }

    /**
     * @covers BackBee\Cache\File\Cache::test()
     */
    public function testTest()
    {
        $this->cache->save('id', 'data');
        $this->assertFalse($this->cache->test('unknown'));
        $this->assertGreaterThan(0, $this->cache->test('id'));
    }

    /**
     * @covers BackBee\Cache\File\Cache::load()
     * @covers BackBee\Cache\File\Cache::getCacheFile()
     */
    public function testLoad()
    {
        $this->cache->save('id', 'data');
        $this->assertEquals('data', $this->cache->load('id'));
        $this->assertEquals('data', $this->cache->load('id', true, (new \DateTime())->add(new \DateInterval('PT1H'))));
        $this->assertFalse($this->cache->load('id', false, (new \DateTime())->add(new \DateInterval('PT1H'))));

        $this->logger
            ->expects($this->once())
            ->method('log');
        $this->assertFalse($this->cache->load('unknown', true));
    }

    /**
     * @covers BackBee\Cache\File\Cache::remove()
     */
    public function testRemove()
    {
        $this->cache->save('id', 'data');
        $this->logger
            ->expects($this->once())
            ->method('log');

        $this->assertTrue($this->cache->remove('id'));
    }

    /**
     * @covers BackBee\Cache\File\Cache::clear()
     */
    public function testClear()
    {
        $this->cache->save('id', 'data');
        $this->cache->save('id2', 'data');

        $this->assertEquals(4, count(scandir(vfsStream::url('root/test'))));
        $this->assertTrue($this->cache->clear());
        $this->assertEquals(2, count(scandir(vfsStream::url('root/test'))));
    }

    /**
     * @covers BackBee\Cache\File\Cache::save()
     * @covers BackBee\Cache\File\Cache::clear()
     */
    public function testNoWrite()
    {
        vfsStream::setup('invalid', 0777);
        file_put_contents(vfsStream::url('invalid/id'), 'data');

        $cache = new Cache(['cachedir' => vfsStream::url('invalid')]);
        chmod(vfsStream::url('invalid'), 0444);

        $this->assertFalse($cache->save('id2', 'data'));
        $this->assertFalse($cache->clear());
    }
}
