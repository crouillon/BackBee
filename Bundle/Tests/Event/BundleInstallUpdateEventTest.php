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

namespace BackBee\Bundle\Tests\Event;

use BackBee\Bundle\AbstractBundle;
use BackBee\Bundle\Event\BundleInstallUpdateEvent;

/**
 * Tests suite for class BundleInstallUpdateEvent.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\Event\BundleInstallUpdateEvent
 */
class BundleInstallUpdateEventTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AbstractBundle
     */
    private $bundle;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        parent::setUp();

        $this->bundle = $this->getMockForAbstractClass(
            AbstractBundle::class,
            [],
            '',
            false
        );
    }

    /**
     * @covers ::__construct()
     * @covers ::isForced()
     */
    public function testForced()
    {
        $eventNoArgs = new BundleInstallUpdateEvent($this->bundle);
        $this->assertFalse($eventNoArgs->isForced());

        $eventStringArgs = new BundleInstallUpdateEvent($this->bundle, 'force');
        $this->assertFalse($eventStringArgs->isForced());

        $eventNotForcedArgs = new BundleInstallUpdateEvent($this->bundle, ['force' => 0]);
        $this->assertFalse($eventNotForcedArgs->isForced());

        $eventForcedArgs = new BundleInstallUpdateEvent($this->bundle, ['force' => 1]);
        $this->assertTrue($eventForcedArgs->isForced());
    }

    /**
     * @covers           ::addLog()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidLog()
    {
        $event = new BundleInstallUpdateEvent($this->bundle);
        $event->addLog('key', []);
    }

    /**
     * @covers ::addLog()
     * @covers ::getLogs()
     */
    public function testLogs()
    {
        $event = new BundleInstallUpdateEvent($this->bundle);
        $event->addLog('key', 'message');
        $this->assertEquals(['key' => ['message']], $event->getLogs());
    }
}
