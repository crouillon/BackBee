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

namespace BackBee\Cache\Tests\MemCache;

use BackBee\Cache\MemCache\AbstractMemcache;
use BackBee\Cache\Tests\Mock\MockMemCache;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class AbstractMemCache
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractMemCacheTest extends BackBeeTestCase
{

    /**
     * @var AbstractMemcache
     */
    private $memcache;

    /**
     * Sets up the fixtures
     */
    protected function setUp()
    {
        parent::setUp();

        $this->memcache = $this->getMockForAbstractClass(
            AbstractMemcache::class,
            [['min_lifetime' => null, 'max_lifetime' => null]],
            '',
            true,
            true,
            true,
            ['getResultCode', 'getResultMessage'],
            false
        );

        $reflection = new \ReflectionClass($this->memcache);
        $property = $reflection->getProperty('_cache');
        $property->setAccessible(true);
        $property->setValue($this->memcache, new MockMemCache());
    }

    /**
     * @covers            BackBee\Cache\MemCache\AbstractMemcache::addServers()
     * @expectedException \BackBee\Cache\Exception\CacheException
     */
    public function testAddInvalidServers()
    {
        $this->memcache->addServers(['invalid']);
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::addServers()
     * @covers BackBee\Cache\MemCache\AbstractMemcache::setServerList()
     * @covers BackBee\Cache\MemCache\AbstractMemcache::getServerList()
     */
    public function testAddServers()
    {
        $this->assertTrue($this->memcache->addServers());

        $expected = [[
            'host' => 'validHost',
            'port' => AbstractMemcache::DEFAULT_PORT,
            'weight' => AbstractMemcache::DEFAULT_WEIGHT,
        ]];

        $this->assertTrue($this->memcache->addServers([['host' => 'validHost']]));
        $this->assertEquals($expected, $this->memcache->getServerList());
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::addServer()
     * @covers BackBee\Cache\MemCache\AbstractMemcache::_hasServer()
     * @covers BackBee\Cache\MemCache\AbstractMemcache::_onError()
     */
    public function testAddServer()
    {
        $this->memcache
            ->expects($this->once())
            ->method('getResultCode');

        $this->memcache
            ->expects($this->once())
            ->method('getResultMessage');

        $this->assertTrue($this->memcache->addServer('validHost', AbstractMemcache::DEFAULT_PORT));
        $this->assertFalse($this->memcache->addServer('invalidHost', AbstractMemcache::DEFAULT_PORT));

        $this->memcache->setServerList(['host' => 'validHost', 'port' => AbstractMemcache::DEFAULT_PORT]);
        $this->assertTrue($this->memcache->addServer('validHost', AbstractMemcache::DEFAULT_PORT));
        $this->assertTrue($this->memcache->addServer('validHost', 11212));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::test()
     */
    public function testTest()
    {
        $this->assertFalse($this->memcache->test('invalidId'));
        $this->assertGreaterThan(0, $this->memcache->test('validId'));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::load()
     */
    public function testLoad()
    {
        $this->assertFalse($this->memcache->load('invalidId'));
        $this->assertFalse($this->memcache->load('validId', false, new \DateTime('tomorrow')));
        $this->assertGreaterThan(0, $this->memcache->load('validId'));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::save()
     */
    public function testSave()
    {
        $this->assertFalse($this->memcache->save('invalidId', ''));
        $this->assertTrue($this->memcache->save('validId', '', 0, 'tag'));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::saveTag()
     */
    public function testSaveTag()
    {
        $this->assertNull($this->memcache->saveTag('validId', 'tag'));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::getLifeTime()
     */
    public function testGetLifeTime()
    {
        $this->assertEquals(0, $this->invokeMethod($this->memcache, 'getLifeTime', [null]));

        $this->invokeMethod($this->memcache, 'setOption', ['min_lifetime', 10]);
        $this->invokeMethod($this->memcache, 'setOption', ['max_lifetime', 50]);

        $this->assertEquals(20, $this->invokeMethod($this->memcache, 'getLifeTime', [20]));
        $this->assertEquals(50, $this->invokeMethod($this->memcache, 'getLifeTime', [0]));
        $this->assertEquals(10, $this->invokeMethod($this->memcache, 'getLifeTime', [5]));
        $this->assertEquals(50, $this->invokeMethod($this->memcache, 'getLifeTime', [60]));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::clear()
     */
    public function testClear()
    {
        $this->assertTrue($this->memcache->clear());
        $this->assertFalse($this->memcache->clear());
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::removeByTag()
     */
    public function testRemoveByTag()
    {
        $this->assertFalse($this->memcache->removeByTag([]));
        $this->assertTrue($this->memcache->removeByTag(['validId']));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::getMinExpireByTag()
     */
    public function testGetMinExpireByTag()
    {
        $this->assertEquals(0, $this->memcache->getMinExpireByTag(null));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::updateExpireByTag()
     */
    public function testUpdateExpireByTag()
    {
        $this->assertTrue($this->memcache->updateExpireByTag('tag', 10));
    }

    /**
     * @covers BackBee\Cache\MemCache\AbstractMemcache::remove()
     */
    public function testRemove()
    {
        $this->assertTrue($this->memcache->remove('validId'));
        $this->assertFalse($this->memcache->remove('invalidId'));
    }
}
