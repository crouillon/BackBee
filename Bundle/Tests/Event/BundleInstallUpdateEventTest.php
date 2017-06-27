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

use BackBee\Bundle\Event\BundleInstallUpdateEvent;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class BundleInstallUpdateEvent.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BundleInstallUpdateEventTest extends BackBeeTestCase
{

    /**
     * @covers BackBee\Bundle\Event\BundleInstallUpdateEvent::__construct()
     * @covers BackBee\Bundle\Event\BundleInstallUpdateEvent::isForced()
     */
    public function testForced()
    {
        $eventNoArgs = new BundleInstallUpdateEvent(self::$app->getBundle('demo'));
        $this->assertFalse($eventNoArgs->isForced());

        $eventStringArgs = new BundleInstallUpdateEvent(self::$app->getBundle('demo'), 'force');
        $this->assertFalse($eventStringArgs->isForced());

        $eventNotForcedArgs = new BundleInstallUpdateEvent(self::$app->getBundle('demo'), ['force' => 0]);
        $this->assertFalse($eventNotForcedArgs->isForced());

        $eventForcedArgs = new BundleInstallUpdateEvent(self::$app->getBundle('demo'), ['force' => 1]);
        $this->assertTrue($eventForcedArgs->isForced());
    }

    /**
     * @covers            BackBee\Bundle\Event\BundleInstallUpdateEvent::addLog()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidLog()
    {
        $event = new BundleInstallUpdateEvent(self::$app->getBundle('demo'));
        $event->addLog('key', []);
    }

    /**
     * @covers BackBee\Bundle\Event\BundleInstallUpdateEvent::addLog()
     * @covers BackBee\Bundle\Event\BundleInstallUpdateEvent::getLogs()
     */
    public function testLogs()
    {
        $event = new BundleInstallUpdateEvent(self::$app->getBundle('demo'));
        $event->addLog('key', 'message');
        $this->assertEquals(['key' => ['message']], $event->getLogs());
    }
}
