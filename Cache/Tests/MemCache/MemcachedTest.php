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

use BackBee\Cache\MemCache\Memcached;

/**
 * Tests suite for class Memcached
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MemcachedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Memcached
     */
    private $cache;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        if (!(extension_loaded('memcached'))) {
            $this->markTestSkipped('The memcached extension is not available.');
        }

        parent::setUp();

        $this->cache = new Memcached();
    }

    /**
     * @covers            BackBee\Cache\MemCache\Memcached::__construct()
     * @expectedException \BackBee\Cache\Exception\CacheException
     */
    public function testConstructInvalidServer()
    {
        new Memcached(['servers' => 'not an array']);
    }
}
