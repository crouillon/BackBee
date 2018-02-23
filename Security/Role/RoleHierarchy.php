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

namespace BackBee\Security\Role;

use Symfony\Component\Security\Core\Role\RoleHierarchy as sfRoleHierarchy;

/**
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
class RoleHierarchy extends sfRoleHierarchy
{

    /**
     * @param array $hierarchy An array defining the hierarchy
     */
    public function __construct(array $hierarchy)
    {
        @trigger_error(
            'The ' . __NAMESPACE__ . '\RoleHierarchy class is deprecated since version 1.4, '
            . 'to be removed in 1.5. Use Symfony\Component\Security\Core\Role\RoleHierarchy instead.',
            E_USER_DEPRECATED
        );

        parent::__construct($hierarchy);
    }
}
