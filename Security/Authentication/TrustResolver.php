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

namespace BackBee\Security\Authentication;

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;

/**
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
class TrustResolver extends AuthenticationTrustResolver
{

    /**
     * @param string $anonymousClass
     * @param string $rememberMeClass
     */
    public function __construct($anonymousClass, $rememberMeClass)
    {
        @trigger_error('The ' . __NAMESPACE__ . '\TrustResolver class is deprecated since '
            . 'version 1.4, to be removed in 1.5. '
            . 'Use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver'
            . ' instead.', E_USER_DEPRECATED);

        parent::__construct($anonymousClass, $rememberMeClass);
    }
}
