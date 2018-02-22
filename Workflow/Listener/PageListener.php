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

namespace BackBee\Workflow\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use BackBee\Event\Event;
use BackBee\NestedNode\Page;

/**
 * Listener to page events.
 *
 * @author d.Bensid <djoudi.bensid@lp-digital.fr>
 */
class PageListener
{

    use ContainerAwareTrait;

    /**
     * Returns the event dispatcher if available, false elsewhere.
     *
     * @return EventDispatcherInterface|false
     */
    protected function getEventDispatcher()
    {
        if (null === $this->container
            || !$this->container->has('event.dispatcher')
        ) {
            return false;
        }

        $dispatcher = $this->container->get('event.dispatcher');
        if (!($dispatcher instanceof EventDispatcherInterface)) {
            return false;
        }

        return $dispatcher;
    }

    /**
     * Occur on nestednode.page.preupdate events.
     *
     * @access public
     *
     * @param Event $event
     */
    public function onPreUpdate(Event $event)
    {
        $page = $event->getTarget();
        if (!($page instanceof Page)) {
            return;
        }

        $eventArgs = $event->getEventArgs();
        if (!($eventArgs instanceof PreUpdateEventArgs)) {
            return;
        }

        $this->onStateChange($eventArgs, $page)
            ->onWorkflowStateChange($event);
    }

    /**
     * Triggers a page event on state changes.
     *
     * @param  PreUpdateEventArgs $eventArgs
     * @param  Page               $page
     *
     * @return PageListener
     */
    private function onStateChange(PreUpdateEventArgs $eventArgs, Page $page)
    {
        if ($eventArgs->hasChangedField('_state')
            && (false !== $dispatcher = $this->getEventDispatcher())
        ) {
            if (!($eventArgs->getOldValue('_state') & Page::STATE_ONLINE)
                && $eventArgs->getNewValue('_state') & Page::STATE_ONLINE
            ) {
                $dispatcher->triggerEvent('putonline', $page);
            } elseif ($eventArgs->getOldValue('_state') & Page::STATE_ONLINE
                && !($eventArgs->getNewValue('_state') & Page::STATE_ONLINE)
            ) {
                $dispatcher->triggerEvent('putoffline', $page);
            }
        }

        return $this;
    }

    /**
     * Triggers an event on workflow state changes.
     *
     * @param  Event $event
     *
     * @return PageListener
     */
    private function onWorkflowStateChange(Event $event)
    {
        $eventArgs = $event->getEventArgs();
        if ($eventArgs->hasChangedField('_workflow_state')) {
            $old = $eventArgs->getOldValue('_workflow_state');
            $new = $eventArgs->getNewValue('_workflow_state');

            if (null !== $new && null !== $listener = $new->getListenerInstance()) {
                $listener->switchOnState($event);
            }

            if (null !== $old && null !== $listener = $old->getListenerInstance()) {
                $listener->switchOffState($event);
            }
        }

        return $this;
    }
}
