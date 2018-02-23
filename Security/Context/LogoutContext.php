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

namespace BackBee\Security\Context;

use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

use BackBee\Security\Listeners\LogoutListener;

/**
 * Logout context.
 *
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class LogoutContext extends AbstractContext
{

    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        if (isset($config['logout'])
            && array_key_exists('handlers', (array) $config['logout'])
            && is_array($handlers = $config['logout']['handlers'])
        ) {
            $this->initLogoutListener();
            $this->setHandlers($handlers);
        }

        return [];
    }

    /**
     * Initializes a new logout listener if need.
     */
    public function initLogoutListener()
    {
        if (null === $this->getSecurityContext()->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $listener = new LogoutListener(
                $this->getSecurityContext(),
                $httpUtils,
                new DefaultLogoutSuccessHandler($httpUtils)
            );

            $this->getSecurityContext()
                ->setLogoutListener($listener);
        }
    }

    /**
     * Adds handlers to logout listener.
     *
     * @param array $handlers
     */
    public function setHandlers($handlers)
    {
        foreach ($handlers as $handler) {
            $this->getSecurityContext()
                ->getLogoutListener()
                ->addHandler(new $handler());
        }
    }
}
