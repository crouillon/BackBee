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

namespace BackBee\Bundle\Tests\Event;

use BackBee\Bundle\Event\AbstractBundleEvent;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class AbstractBundleEvent
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractBundleEventTest extends BackBeeTestCase
{

    /**
     * @covers            BackBee\Bundle\Event\AbstractBundleEvent::__construct()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstruct()
    {
        $this->getMockForAbstractClass(AbstractBundleEvent::class, [self::$app]);
    }

    /**
     * @covers BackBee\Bundle\Event\AbstractBundleEvent::__construct()
     * @covers BackBee\Bundle\Event\AbstractBundleEvent::getBundle()
     */
    public function testGetBundle()
    {
        $demo = self::$app->getBundle('demo');
        $stub = $this->getMockForAbstractClass(AbstractBundleEvent::class, [self::$app->getBundle('demo')]);
        $this->assertEquals($demo, $stub->getBundle());
    }
}
