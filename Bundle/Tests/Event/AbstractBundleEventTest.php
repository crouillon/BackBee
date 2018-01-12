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
use BackBee\Bundle\Event\AbstractBundleEvent;

/**
 * Tests suite for class AbstractBundleEvent
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\Event\AbstractBundleEvent
 */
class AbstractBundleEventTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers            ::__construct()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstruct()
    {
        $this->getMockForAbstractClass(AbstractBundleEvent::class, [new \StdClass()]);
    }

    /**
     * @covers ::__construct()
     * @covers ::getBundle()
     */
    public function testGetBundle()
    {
        $bundle = $this->getMockForAbstractClass(
            AbstractBundle::class,
            [],
            '',
            false
        );

        //$demo = self::$app->getBundle('demo');
        $stub = $this->getMockForAbstractClass(AbstractBundleEvent::class, [$bundle]);
        $this->assertEquals($bundle, $stub->getBundle());
    }
}
