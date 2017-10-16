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

namespace BackBee\Security\Context;

use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;

/**
 * Security context for anonymous connections.
 *
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class AnonymousContext extends AbstractContext
{

    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = [];
        if (isset($config['anonymous'])) {
            $key = array_key_exists('key', (array) $config['anonymous']) ? $config['anonymous']['key'] : 'anom';

            $this->getSecurityContext()
                ->addAuthProvider(new AnonymousAuthenticationProvider($key));

            $listeners[] = new AnonymousAuthenticationListener(
                $this->getSecurityContext(),
                $key,
                $this->getSecurityContext()->getLogger()
            );
        }

        return $listeners;
    }
}
