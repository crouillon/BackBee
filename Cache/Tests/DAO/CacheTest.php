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

namespace BackBee\Cache\Tests\DAO;

use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\LoggerInterface;

use BackBee\Cache\DAO\Cache;
use BackBee\Cache\DAO\Entity;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class DAO\Cache.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheTest extends BackBeeTestCase
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
     * Sets up the fixtures.
     */
    protected function setUp()
    {
        parent::setUp();

        $metadata = self::$em->getMetadataFactory()->getMetadataFor(Cache::ENTITY_CLASSNAME);
        $schemaTool = new SchemaTool(self::$em);
        $schemaTool->updateSchema([$metadata], true);

        $options = [
            'em' => self::$em
        ];

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();

        $this->cache = new Cache($options, 'test', $this->logger);
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::save()
     * @covers BackBee\Cache\DAO\Cache::persistEntity()
     */
    public function testSave()
    {
        $this->assertTrue($this->cache->save('noExpire', 'noExpire'));
        $this->assertTrue($this->cache->save('withExpire', 'withExpire', 10));
        $this->assertTrue($this->cache->save('expire', 'expire', -10));
        $this->assertTrue($this->cache->save('noExpireWithTag', 'noExpireWithTag', null, 'tag'));
        $this->assertTrue($this->cache->save('withExpireWithTag', 'withExpireWithTag', 10, 'tag'));
        $this->assertTrue($this->cache->save('noExpire', 'noExpire2'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::test()
     */
    public function testTest()
    {
        $this->assertFalse($this->cache->test('unknown'));
        $this->assertEquals(0, $this->cache->test('noExpire'));
        $this->assertGreaterThan(0, $this->cache->test('withExpire'));
        $this->assertFalse($this->cache->test('expire'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::load()
     */
    public function testLoad()
    {
        $this->assertFalse($this->cache->load('unknown'));
        $this->assertEquals('noExpire2', $this->cache->load('noExpire'));
        $this->assertFalse($this->cache->load('expire'));
        $this->assertEquals('expire', $this->cache->load('expire', true));
        $this->assertEquals('withExpire', $this->cache->load('withExpire'));
        $this->assertFalse($this->cache->load('withExpire', false, (new \DateTime())->add(new \DateInterval('PT50S'))));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::getMinExpireByTag()
     */
    public function testGetMinExpireByTag()
    {
        $this->assertEquals(0, $this->cache->getMinExpireByTag(null));
        $this->assertEquals(0, $this->cache->getMinExpireByTag('unknown'));
        $this->assertGreaterThan(0, $this->cache->getMinExpireByTag('tag'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::updateExpireByTag()
     */
    public function testUpdateExpireByTag()
    {
        $this->assertFalse($this->cache->updateExpireByTag(null));
        $this->assertTrue($this->cache->updateExpireByTag('tag', 10));
        $this->assertGreaterThan(0, $this->cache->test('noExpireWithTag'));
        $this->assertGreaterThan(0, $this->cache->test('withExpireWithTag'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::remove()
     */
    public function testRemove()
    {
        $this->assertTrue($this->cache->remove('withExpire'));

        self::$em->clear();
        $this->assertFalse($this->cache->test('withExpire'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::removeByTag()
     */
    public function testRemoveByTag()
    {
        $this->assertFalse($this->cache->removeByTag(null));
        $this->assertTrue($this->cache->removeByTag('tag'));

        self::$em->clear();
        $this->assertFalse($this->cache->test('noExpireWithTag'));
        $this->assertFalse($this->cache->test('withExpireWithTag'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::getCacheEntity()
     * @covers BackBee\Cache\DAO\Cache::resetCacheEntity()
     */
    public function testCacheEntity()
    {
        $this->assertInstanceOf(
            Entity::class,
            $this->invokeMethod($this->cache, 'getCacheEntity', ['noExpire'])
        );

        $this->invokeMethod($this->cache, 'resetCacheEntity');
        $this->assertNull(
            $this->invokeMethod($this->cache, 'getCacheEntity', ['unknown'])
        );
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::clear()
     */
    public function testClear()
    {
        $this->assertTrue($this->cache->clear());
        $this->assertEquals(0, count(self::$em->getRepository(Entity::class)->findAll()));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::getExpireTime()
     */
    public function testGetExpireTime()
    {
        $this->assertNull($this->cache->getExpireTime());
        $this->assertNotNull($this->cache->getExpireTime(10));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::__construct()
     * @covers BackBee\Cache\DAO\Cache::setPrefixKey()
     * @covers BackBee\Cache\DAO\Cache::getContextualId()
     */
    public function testPrefixKey()
    {
        $expected = md5(md5('test') . 'id');
        $this->assertEquals($expected, $this->invokeMethod($this->cache, 'getContextualId', ['id']));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::getContextualTags()
     */
    public function testGetContextualTags()
    {
        $expected = [md5(md5('test') . 'tag')];
        $this->assertEquals($expected, $this->invokeMethod($this->cache, 'getContextualTags', [['tag']]));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::setEntityRepository()
     */
    public function testSetRepository()
    {
        $options = [
            'em' => null,
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
                'proxy_dir' => __DIR__,
                'proxy_ns' => 'Proxy'
            ]
        ];
        $cache = new Cache($options);

        $this->assertEquals($cache, $this->invokeMethod($cache, 'setEntityRepository'));
    }

    /**
     * @covers            BackBee\Cache\DAO\Cache::setEntityManager()
     * @expectedException \BackBee\Cache\Exception\CacheException
     */
    public function testInvalidEntityManager()
    {
        new Cache([]);
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::setEntityManager()
     */
    public function testDbalEntityManager()
    {
        $options = [
            'em' => null,
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
                'proxy_dir' => __DIR__,
                'proxy_ns' => 'Proxy'
            ]
        ];

        $this->invokeMethod($this->cache, 'setOptions', [$options]);
        $this->assertEquals($this->cache, $this->invokeMethod($this->cache, 'setEntityManager'));
    }

    /**
     * @covers BackBee\Cache\DAO\Cache::save()
     * @covers BackBee\Cache\DAO\Cache::remove()
     * @covers BackBee\Cache\DAO\Cache::removeByTag()
     * @covers BackBee\Cache\DAO\Cache::updateExpireByTag()
     * @covers BackBee\Cache\DAO\Cache::getMinExpireByTag()
     * @covers BackBee\Cache\DAO\Cache::clear()
     */
    public function testInvalidRequests()
    {
        self::$em->getConnection()->close();

        $this->logger
            ->expects($this->exactly(6))
            ->method('log');

        $this->assertFalse($this->cache->save('id', 'data'));
        $this->assertFalse($this->cache->remove('id'));
        $this->assertFalse($this->cache->removeByTag('tag'));
        $this->assertFalse($this->cache->updateExpireByTag('tag'));
        $this->assertEquals(0, $this->cache->getMinExpireByTag('tag'));
        $this->assertFalse($this->cache->clear());
    }
}
