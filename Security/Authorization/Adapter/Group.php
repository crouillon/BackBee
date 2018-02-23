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

namespace BackBee\Security\Authorization\Adapter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use BackBee\BBApplication;

/**
 * @author Nicolas Dufreche
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
class Group implements RoleReaderAdapterInterface
{

    /**
     * {@inheritdoc}
     */
    public function __construct(BBApplication $application)
    {
        @trigger_error('The '.__NAMESPACE__.'\Group class is deprecated since version 1.4, '
            . 'to be removed in 1.5.', E_USER_DEPRECATED);
    }

    /**
     * {@inheritdoc}
     */
    public function extractRoles(TokenInterface $token)
    {
        $user_roles = array();
        foreach ($token->getUser()->getGroups() as $group) {
            $tmp = array();
            foreach ($group->getRoles() as $role) {
                $tmp[$role->getRole()] = $role;
            }
            $user_roles = array_merge($tmp, $user_roles);
        }

        return $user_roles;
    }
}
