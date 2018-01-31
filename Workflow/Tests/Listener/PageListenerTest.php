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

namespace BackBee\Workflow\Tests\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use BackBee\Event\Event;
use BackBee\NestedNode\Page;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Workflow\Listener\PageListener;
use BackBee\Workflow\ListenerInterface;
use BackBee\Workflow\State;

/**
 * Test suite for clas PageListener.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Workflow\Listener\PageListener
 */
class PageListenerTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;

    /**
     * @var PageListener
     */
    private $listener;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dispatcher = $this->getMockForAbstractClass(
            EventDispatcherInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['triggerEvent']
        );

        $container = new Container();
        $container->set('event.dispatcher', $this->dispatcher);

        $this->listener = new PageListener();
        $this->listener->setContainer($container);
    }

    /**
     * @covers ::getEventDispatcher()
     */
    public function testGetEventDispatcher()
    {
        $this->listener->setContainer(null);
        $this->assertFalse($this->invokeMethod($this->listener, 'getEventDispatcher'));

        $container = new Container();
        $this->listener->setContainer($container);
        $this->assertFalse($this->invokeMethod($this->listener, 'getEventDispatcher'));

        $container->set('event.dispatcher', 'dispatcher');
        $this->assertFalse($this->invokeMethod($this->listener, 'getEventDispatcher'));

        $container->set('event.dispatcher', $this->dispatcher);
        $this->assertEquals(
            $this->dispatcher,
            $this->invokeMethod($this->listener, 'getEventDispatcher')
        );
    }

    /**
     * @covers ::onPreUpdate()
     */
    public function testPreUpdateInvalidEvent()
    {
        $notaPage = $this->getMockBuilder(Event::class)
            ->setMethods(['getEventArgs'])
            ->setConstructorArgs(['target'])
            ->getMock();

        $notaPage->expects($this->never())->method('getEventArgs');
        $this->assertNull($this->listener->onPreUpdate($notaPage));

        $this->assertNull($this->listener->onPreUpdate(new Event(new Page())));
    }

    /**
     * @covers ::onPreUpdate()
     * @covers ::onStateChange()
     */
    public function testPreUpdateStateOnLine()
    {
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['hasChangedField', 'getOldValue', 'getNewValue'])
                    ->getMock();
        $args->expects(($this->any()))
            ->method('hasChangedField')
            ->willReturnMap([
                ['_state', true],
                ['_workflow_state', false],
            ]);
        $args->expects($this->any())->method('getOldValue')->willReturn(Page::STATE_OFFLINE);
        $args->expects($this->any())->method('getNewValue')->willReturn(Page::STATE_ONLINE);

        $this->dispatcher
            ->expects($this->once())
            ->method('triggerEvent')
            ->will($this->returnCallback(function ($name, $object) {
                \PHPUnit_Framework_Assert::assertEquals('putonline', $name);
                \PHPUnit_Framework_Assert::assertInstanceOf(Page::class, $object);
            }));

        $this->listener->onPreUpdate(new Event(new Page(), $args));
    }

    /**
     * @covers ::onPreUpdate()
     * @covers ::onStateChange()
     */
    public function testPreUpdateStateOffLine()
    {
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['hasChangedField', 'getOldValue', 'getNewValue'])
                    ->getMock();
        $args->expects(($this->any()))
            ->method('hasChangedField')
            ->willReturnMap([
                ['_state', true],
                ['_workflow_state', false],
            ]);
        $args->expects($this->any())->method('getOldValue')->willReturn(Page::STATE_ONLINE);
        $args->expects($this->any())->method('getNewValue')->willReturn(Page::STATE_OFFLINE);

        $this->dispatcher
            ->expects($this->once())
            ->method('triggerEvent')
            ->will($this->returnCallback(function ($name, $object) {
                \PHPUnit_Framework_Assert::assertEquals('putoffline', $name);
                \PHPUnit_Framework_Assert::assertInstanceOf(Page::class, $object);
            }));

        $this->listener->onPreUpdate(new Event(new Page(), $args));
    }

    /**
     * @covers ::onPreUpdate()
     * @covers ::onStateChange()
     */
    public function testPreUpdateStateNoChange()
    {
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['hasChangedField', 'getOldValue', 'getNewValue'])
                    ->getMock();
        $args->expects(($this->any()))
            ->method('hasChangedField')
            ->willReturnMap([
                ['_state', true],
                ['_workflow_state', false],
            ]);
        $args->expects($this->any())->method('getOldValue')->willReturn(Page::STATE_ONLINE);
        $args->expects($this->any())->method('getNewValue')->willReturn(Page::STATE_ONLINE + Page::STATE_HIDDEN);

        $this->dispatcher->expects($this->never())->method('triggerEvent');

        $this->listener->onPreUpdate(new Event(new Page(), $args));
    }

    /**
     * @covers ::onPreUpdate()
     * @covers ::onWorkflowStateChange()
     */
    public function testPreUpdateWorkflowStateChange()
    {
        $listener = $this->getMockForAbstractClass(
            ListenerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['switchOnState', 'switchOffState']
        );
        $state = $this->getMockBuilder(State::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['getListenerInstance'])
                    ->getMock();
        $state->expects($this->any())->method('getListenerInstance')->willReturn($listener);

        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['hasChangedField', 'getOldValue', 'getNewValue'])
                    ->getMock();
        $args->expects(($this->any()))
            ->method('hasChangedField')
            ->willReturnMap([
                ['_state', false],
                ['_workflow_state', true],
            ]);
        $args->expects($this->any())->method('getOldValue')->willReturn($state);
        $args->expects($this->any())->method('getNewValue')->willReturn($state);

        $listener->expects($this->once())->method('switchOnState');
        $listener->expects($this->once())->method('switchOffState');

        $this->listener->onPreUpdate(new Event(new Page(), $args));
    }
}
