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

use BackBee\Security\Listeners\ContextListener;

/**
 * Stateless context.
 *
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class StatelessContext extends AbstractContext
{

    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = [];
        if ((!isset($config['stateless']) || false === $config['stateless'])
            && (isset($config['context']) ||isset($config['firewall_name']))
        ) {
            $contextKey = isset($config['context']) ? $config['context'] : $config['firewall_name'];

            $listeners[] = new ContextListener(
                $this->getSecurityContext(),
                $this->getSecurityContext()->getUserProviders(),
                $contextKey,
                $this->getSecurityContext()->getLogger(),
                $this->getSecurityContext()->getDispatcher()
            );
        }

        return $listeners;
    }
}
