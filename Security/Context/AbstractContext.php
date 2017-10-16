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

use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\SecurityContext;

/**
 * Abstract class for security context definition.
 *
 * @author nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AbstractContext implements ContextInterface
{

    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * Context constructor.
     *
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Returns the BackBee secuirty context.
     *
     * @return SecurityContext
     *
     * @codeCoverageIgnore
     */
    protected function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * Magic getter on old property _context.
     *
     * @param  string $name
     *
     * @return SecurityContext
     *
     * @throws \InvalidArgumentException if $name is not _context.
     * @codeCoverageIgnore
     */
    public function __get($name)
    {
        if ('_context' === $name) {
            @trigger_error('The property _context of class ' . __CLASS__ . ' is deprecated since version 1.4 and '
                . 'will be removed in 1.5. Use ' . __CLASS__ . '::getSecurityContext() instead.', E_USER_DEPRECATED);

            return $this->getSecurityContext();
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown property %s for class %s.',
            $name,
            __CLASS__
        ));
    }

    /**
     * Returns the default user provider for context.
     *
     * @param  array $config
     *
     * @return UserProviderInterface
     */
    public function getDefaultProvider($config)
    {
        $providers = $this->getSecurityContext()->getUserProviders();
        $defaultProvider = reset($providers);

        if (isset($config['provider']) && isset($providers[$config['provider']])) {
            $defaultProvider = $providers[$config['provider']];
        }

        return $defaultProvider;
    }
}
