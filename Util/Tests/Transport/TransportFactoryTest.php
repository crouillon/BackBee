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

namespace BackBee\Util\Tests\Transport;

use BackBee\Util\Transport\FileSystem;
use BackBee\Util\Transport\TransportFactory;

/**
 * Tests suite for class TransportFactory.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Transport\TransportFactory
 */
class TransportFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::create()
     */
    public function testCreate()
    {
        $this->assertInstanceOf(
            FileSystem::class,
            TransportFactory::create(['transport' => FileSystem::class])
        );
    }

    /**
     * @covers            ::create()
     * @expectedException BackBee\Util\Transport\Exception\MisconfigurationException
     *
     * @return void
     */
    public function testInvalidCreate()
    {
        TransportFactory::create([]);
    }

    /**
     * @covers            ::create()
     * @expectedException BackBee\Util\Transport\Exception\MisconfigurationException
     *
     * @return void
     */
    public function testUnknownCreate()
    {
        TransportFactory::create(['transport' => 'Unknown']);
    }
}