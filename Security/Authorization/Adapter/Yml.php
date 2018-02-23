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
use BackBee\Security\Role\Role;

/**
 * @author Nicolas Dufreche
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
class Yml implements RoleReaderAdapterInterface
{

    /**
     * @var Role[]
     */
    private $roles;

    /**
     * {@inheritdoc}
     */
    public function __construct(BBApplication $application, $section = 'roles')
    {
        @trigger_error('The '.__NAMESPACE__.'\Yml class is deprecated since version 1.4, '
            . 'to be removed in 1.5.', E_USER_DEPRECATED);

        $this->roles = $application->getConfig()->getSecurityConfig($section) ?: array();
    }

    /**
     * {@inheritdoc}
     */
    public function extractRoles(TokenInterface $token)
    {
        $user_roles = array();
        foreach ($this->roles as $role => $users) {
            if (is_array($users) && in_array($token->getUsername(), $users)) {
                $user_roles[] = new Role($role);
            }
        }

        return $user_roles;
    }
}
