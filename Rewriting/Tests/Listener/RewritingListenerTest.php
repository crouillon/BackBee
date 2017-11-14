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

namespace BackBee\Rewriting\Tests\Listener;

use BackBee\ClassContent\ContentSet;
use BackBee\Event\Event;
use BackBee\NestedNode\Page;
use BackBee\Rewriting\Listener\RewritingListener;
use BackBee\Rewriting\UrlGeneratorInterface;

/**
 * Test suite for class RewritingListener
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Rewriting\Listener\RewritingListener
 */
class RewritingListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * @var RewritingListener
     */
    private $listener;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->generator = $this->getMockForAbstractClass(
            UrlGeneratorInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $this->listener = new RewritingListener($this->generator);
    }

    /**
     * @covers ::__construct()
     * @covers ::onFlushContent()
     */
    public function testOnFlushContent()
    {
        $page = new Page();
        $content = new ContentSet();

        $this->generator
            ->expects($this->once())
            ->method('onPageFlush')
            ->with($page);

        $this->listener->onFlushContent(new Event(new \stdClass()));
        $this->listener->onFlushContent(new Event($content));

        $content->setMainNode($page);
        $this->listener->onFlushContent(new Event($content));
    }

    /**
     * @covers ::onFlushPage()
     */
    public function testOnFlushPage()
    {
        $page = new Page();

        $this->generator
            ->expects($this->once())
            ->method('onPageFlush')
            ->with($page);

        $this->listener->onFlushPage(new Event(new \stdClass()));
        $this->listener->onFlushPage(new Event($page));
    }
}
