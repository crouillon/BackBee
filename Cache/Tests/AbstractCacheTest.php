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

namespace BackBee\Cache\Tests;

use Psr\Log\LoggerInterface;

use BackBee\Cache\AbstractCache;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class AbstractCache
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractCacheTest extends BackBeeTestCase
{

    /**
     * @var AbstractCache
     */
    private $cache;

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

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();

        $this->cache = $this->getMockForAbstractClass(
            AbstractCache::class,
            [['min_lifetime' => 0]]
        );
    }

    /**
     * @covers BackBee\Cache\AbstractCache::__construct()
     * @covers BackBee\Cache\AbstractCache::getContext()
     * @covers BackBee\Cache\AbstractCache::setContext()
     */
    public function testContext()
    {
        $this->assertNull($this->cache->getContext());
        $this->assertEquals($this->cache, $this->cache->setContext('context2'));
        $this->assertEquals('context2', $this->cache->getContext());
    }

    /**
     * @covers BackBee\Cache\AbstractCache::__construct()
     * @covers BackBee\Cache\AbstractCache::getLogger()
     * @covers BackBee\Cache\AbstractCache::setLogger()
     */
    public function testLogger()
    {
        $this->assertNull($this->cache->getLogger());
        $this->assertEquals($this->cache, $this->cache->setLogger($this->logger));
        $this->assertEquals($this->logger, $this->cache->getLogger());
    }

    /**
     * @covers BackBee\Cache\AbstractCache::__construct()
     * @covers BackBee\Cache\AbstractCache::getOptions()
     * @covers BackBee\Cache\AbstractCache::setOptions()
     */
    public function testOptions()
    {
        $this->assertEquals(
            ['min_lifetime' => 0, 'max_lifetime' => null],
            $this->invokeMethod($this->cache, 'getOptions')
        );
        $this->assertEquals($this->cache, $this->invokeMethod($this->cache, 'setOptions', [['option' => 'value']]));
        $this->assertEquals(['option' => 'value'], $this->invokeMethod($this->cache, 'getOptions'));
    }

    /**
     * @covers BackBee\Cache\AbstractCache::__construct()
     * @covers BackBee\Cache\AbstractCache::getOption()
     * @covers BackBee\Cache\AbstractCache::setOption()
     */
    public function testOption()
    {
        $this->assertNull($this->invokeMethod($this->cache, 'getOption', ['max_lifetime']));
        $this->assertEquals($this->cache, $this->invokeMethod($this->cache, 'setOption', ['max_lifetime', 10]));
        $this->assertEquals(10, $this->invokeMethod($this->cache, 'getOption', ['max_lifetime']));
    }

    /**
     * @covers            BackBee\Cache\AbstractCache::getOption()
     * @expectedException \BackBee\Cache\Exception\CacheException
     */
    public function testGetUnknownOption()
    {
        $this->invokeMethod($this->cache, 'getOption', ['unknown']);
    }

    /**
     * @covers BackBee\Cache\AbstractCache::log()
     */
    public function testLog()
    {
        $this->logger->expects($this->once())
                ->method('log');

        $this->cache->setLogger($this->logger);
        $this->cache->log('level', 'message');
    }

    /**
     * @covers BackBee\Cache\AbstractCache::getExpireTime()
     */
    public function testGetExpireTime()
    {
        $this->assertEquals(0, $this->cache->getExpireTime());
        $this->assertEquals(0, $this->cache->getExpireTime(null, true));
        $this->assertGreaterThan(time(), $this->cache->getExpireTime(10));
        $this->assertLessThan(time(), $this->cache->getExpireTime(-10));
    }

    /**
     * @covers BackBee\Cache\AbstractCache::getControlledLifetime()
     */
    public function testGetControlledLifetime()
    {
        $this->assertEquals(10, $this->cache->getControlledLifetime(10));

        $this->invokeMethod($this->cache, 'setOption', ['min_lifetime', '20']);
        $this->assertEquals(20, $this->cache->getControlledLifetime(10));

        $this->invokeMethod($this->cache, 'setOption', ['max_lifetime', '50']);
        $this->assertEquals(30, $this->cache->getControlledLifetime(30));
        $this->assertEquals(50, $this->cache->getControlledLifetime(100));
    }

    /**
     * @covers BackBee\Cache\AbstractCache::getControlledExpireTime()
     */
    public function testGetControlledExpireTime()
    {
        $this->assertEquals(0, $this->invokeMethod($this->cache, 'getControlledExpireTime', [0]));
        $this->assertEquals(2 * time(), $this->invokeMethod($this->cache, 'getControlledExpireTime', [2 * time()]));
    }
}
