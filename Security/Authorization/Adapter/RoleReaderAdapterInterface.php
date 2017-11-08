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

namespace BackBee\Security\Authorization\Adapter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use BackBee\BBApplication;
use BackBee\Security\Role\Role;

@trigger_error('The '.__NAMESPACE__.'\RoleReaderAdapterInterface interface is deprecated '
        . 'since version 1.4, to be removed in 1.5.', E_USER_DEPRECATED);

/**
 * @author Nicolas Dufreche
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
interface RoleReaderAdapterInterface
{

    /**
     * Object Constructor.
     *
     * @param BBApplication $application
     */
    public function __construct(BBApplication $application);

    /**
     * retrieve the users role thanks to the Token.
     *
     * @param  TokenInterface $token
     *
     * @return Role[]
     */
    public function extractRoles(TokenInterface $token);
}
