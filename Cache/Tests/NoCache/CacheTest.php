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

namespace BackBee\Cache\Tests\NoCache;

use BackBee\Cache\NoCache\Cache;

/**
 * Tests suite for class NoCache\Cache
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers BackBee\Cache\NoCache\Cache::save()
     * @covers BackBee\Cache\NoCache\Cache::test()
     * @covers BackBee\Cache\NoCache\Cache::load()
     * @covers BackBee\Cache\NoCache\Cache::remove()
     * @covers BackBee\Cache\NoCache\Cache::clear()
     */
    public function testCache()
    {
        $cache = new Cache();

        $this->assertTrue($cache->save('id', 'data'));
        $this->assertFalse($cache->test('id'));
        $this->assertFalse($cache->load('id'));
        $this->assertTrue($cache->remove('id'));
        $this->assertTrue($cache->clear());
    }
}
