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

namespace BackBee\Cache\Tests\APC;

use BackBee\Cache\APC\Cache;

/**
 * Tests site for class APC\Cache
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
     * Sets up the fixture.
     */
    protected function setUp()
    {
        if (!(extension_loaded('apc') && ini_get('apc.enabled'))) {
            $this->markTestSkipped('The APC extension is not available.');
        }

        parent::setUp();

        $this->cache = new Cache([], 'test');
    }

    /**
     * @covers BackBee\Cache\Tests\APC::__construct()
     * @covers BackBee\Cache\Tests\APC::getHashmap()
     * @covers BackBee\Cache\Tests\APC::getHashmapId()
     */
    public function testConstruct()
    {
        $this->assertNotNull($this->cache->getHashmap());
        $this->assertEquals(
            Cache::HASHMAP_PREFIX . '_' . md5('test'),
            $this->cache->getHashmapId()
        );
    }
}
