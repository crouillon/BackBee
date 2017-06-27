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

namespace BackBee\Bundle\Listener;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use BackBee\Bundle\Event\BundleStopEvent;
use BackBee\Event\Event;

/**
 * BackBee core bundle listener.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class BundleListener implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Listener constructor
     *
     * @param ContainerInterface|null       $container
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(ContainerInterface $container = null, EventDispatcherInterface $eventDispatcher = null)
    {
        $this
            ->setContainer($container)
            ->setEventDispatcher($eventDispatcher)
        ;
    }

    /**
     * Occurs on `bbapplication.stop` event to stop every started bundles.
     *
     * @param Event $event
     */
    public function onApplicationStop(Event $event)
    {
        if (null === $this->container) {
            return;
        }

        $bundlesId = array_keys($this->container->findTaggedServiceIds('bundle'));
        foreach ($bundlesId as $bundleId) {
            if (!$this->container->has($bundleId)) {
                continue;
            }

            $bundle = $this->container->get($bundleId);
            $bundle->stop();

            if (null !== $this->eventDispatcher) {
                $stopEvent = new BundleStopEvent($this->container->get($bundleId));
                $this->eventDispatcher->dispatch(
                    sprintf('bundle.%s.stopped', $bundleId),
                    $stopEvent
                );
            }
        }
    }

    /**
     * Sets the container.
     *
     * @param  ContainerInterface|null $container A ContainerInterface instance or null.
     *
     * @return BundleListenr                      Current listener instance.
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Sets the event dispatcher.
     *
     * @param EventDispatcherInterface|null $eventDispatcher A EventDispatcherInterface instance or null
     *
     * @return BundleListenr                                 Current listener instance.
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
