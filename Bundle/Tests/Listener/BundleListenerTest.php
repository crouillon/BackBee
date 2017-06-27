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

namespace BackBee\Bundle\Tests\Listener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;

use BackBee\Bundle\DemoBundle\Demo;
use BackBee\Bundle\Listener\BundleListener;
use BackBee\Event\Event;

/**
 * Tests suite for class BundleListener.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BundleListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Demo
     */
    private $bundle;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var BundleListener
     */
    private $listener;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->bundle = $this->getMockBuilder(Demo::class)
                ->disableOriginalConstructor()
                ->setMethods(['stop'])
                ->getMock();

        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)
                ->setMethods(['dispatch'])
                ->getMock();

        $definition = new Definition(get_class($this->bundle));
        $definition->setFactory([$this, 'getBundle']);
        $definition->addTag('bundle');

        $container = new ContainerBuilder();
        $container->setDefinition('bundle.mock', $definition);

        $this->listener = new BundleListener($container, $this->dispatcher);
    }

    /**
     * @return Demo
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @covers BackBee\Bundle\Listener\BundleListener::__construct()
     * @covers BackBee\Bundle\Listener\BundleListener::setContainer()
     * @covers BackBee\Bundle\Listener\BundleListener::setEventDispatcher()
     * @covers BackBee\Bundle\Listener\BundleListener::onApplicationStop()
     */
    public function testOnApplicationStop()
    {
        $this->bundle
            ->expects($this->once())
            ->method('stop');

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch');

        $this->listener->onApplicationStop(new Event(''));
    }

    /**
     * @covers BackBee\Bundle\Listener\BundleListener::onApplicationStop()
     */
    public function testNoContainer()
    {
        $this->listener->setContainer(null);
        $this->assertNull($this->listener->onApplicationStop(new Event('')));
    }
}
