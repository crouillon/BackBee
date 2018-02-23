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

namespace BackBee\Security\Listeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener as sfAnonymousAuthenticationListener;

/**
 * @deprecated since version 1.4, to be removed in 1.5.
 * @codeCoverageIgnore
 */
class AnonymousAuthenticationListener extends sfAnonymousAuthenticationListener
{

    /**
     * Listener constructor.
     *
     * @param TokenStorageInterface|SecurityContextInterface $tokenStorage
     * @param string                                         $secret
     * @param LoggerInterface|null                           $logger
     * @param AuthenticationManagerInterface|null            $authenticationManager
     */
    public function __construct(
        $tokenStorage,
        $secret,
        LoggerInterface $logger = null,
        AuthenticationManagerInterface $authenticationManager = null
    ) {
        @trigger_error('The '.__NAMESPACE__.'\AnonymousAuthenticationListener class is deprecated '
            . 'since version 1.4 and will be removed in 1.5. '
            . 'Use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener '
            . 'instead.', E_USER_DEPRECATED);

        if ($tokenStorage instanceof SecurityContextInterface) {
            $tokenStorage = new TokenStorage();
        }

        parent::__construct($tokenStorage, $secret, $logger, $authenticationManager);
    }
}
