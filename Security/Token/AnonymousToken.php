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

namespace BackBee\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken as sfAnonymousToken;

/**
 * @deprecated since version 1.4, to be removed in 1.5.
 * @codeCoverageIgnore
 */
class AnonymousToken extends sfAnonymousToken
{

    /**
     * @param string          $secret A secret used to make sure the token is created by the app and not
     *                                by a malicious client
     * @param string|object   $user   The user can be a UserInterface instance, or an object implementing
     *                                a __toString method or the username as a regular string
     * @param RoleInterface[] $roles  An array of roles
     */
    public function __construct($secret, $user, array $roles = array())
    {
        @trigger_error('The ' . __NAMESPACE__ . '\AnonymousToken class is deprecated since version 1.4, '
            . 'to be removed in 1.5. '
            . 'Use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken instead.', E_USER_DEPRECATED);

        parent::__construct($secret, $user, $roles);
    }
}
