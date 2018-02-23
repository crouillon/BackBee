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

namespace BackBee\Security\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;

/**
 * Handler for clearing nonce file of BB connection.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BBLogoutHandler implements LogoutHandlerInterface
{

    /**
     * The BB user authentication provider.
     *
     * @var BBAuthenticationProvider
     */
    private $authProvider;

    /**
     * Class constructor.
     *
     * @param BBAuthenticationProvider $authProvider
     */
    public function __construct(BBAuthenticationProvider $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    /**
     * Invalidate the current BB connection.
     *
     * @param Request        $request
     * @param Response       $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->authProvider->clearNonce($token);
    }
}
