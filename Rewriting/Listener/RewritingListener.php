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

namespace BackBee\Rewriting\Listener;

use Doctrine\ORM\EntityManager;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\Event\Event;
use BackBee\NestedNode\Page;
use BackBee\Rewriting\UrlGeneratorInterface;

/**
 * Rewriting listener updating the page url on content changes, listen:
 *    - classcontent.onflush: occurs when a classcontent entity is mentioned for current flush.
 *    - nestednode.page.onflush: occurs when a page entity is mentioned for current flush.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class RewritingListener
{

    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * Listener constructor.
     *
     * @param UrlGeneratorInterface $generator
     * @param EntityManager         $entityMng
     */
    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Occur on classcontent.onflush events.
     *
     * @param Event $event
     */
    public function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AbstractClassContent)) {
            return;
        }

        $page = $content->getMainNode();
        if (null === $page) {
            return;
        }

        $this->generator->onPageFlush($page);
    }

    /**
     * Occur on nestednode.page.onflush events.
     *
     * @param Event $event
     */
    public function onFlushPage(Event $event)
    {
        $page = $event->getTarget();
        if (!($page instanceof Page)) {
            return;
        }

        $this->generator->onPageFlush($page);
    }
}
