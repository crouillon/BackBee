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

namespace BackBee\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use BackBee\BBApplication;
use BackBee\Security\Authorization\Adaptator\RoleReaderAdapterInterface;

/**
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
class AccessVoter implements VoterInterface
{
    private $application;
    private $adapter;
    private $prefix;
    private $class;

    /**
     * Constructor.
     *
     * @param RoleReaderAdapterInterface $adapter
     * @param string               $prefix    The role prefix
     */
    public function __construct(
        BBApplication $application,
        RoleReaderAdapterInterface $adapter,
        $class,
        $prefix = 'BB_'
    ) {
        @trigger_error('The '.__NAMESPACE__.'\Yml class is deprecated since version 1.4, '
            . 'to be removed in 1.5.', E_USER_DEPRECATED);

        $this->adapter = $adapter;
        $this->prefix = $prefix;
        $this->class = $class;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return 0 === strpos($attribute, $this->prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === 'BackBee\Security\Token\BBUserToken';
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (getclass($object) === $this->class) {
            $result = $this->voteForSomething($token, $object, $attributes);
        } else {
            $result = $this->voteForAccess($token, $attributes);
        }

        return $result;
    }

    private function voteForAccess(TokenInterface $token, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
    }

    private function voteForSomething(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param TokenInterface $token
     *
     * @return array
     */
    private function extractRoles(TokenInterface $token)
    {
        return $this->adapter->extractRoles($token);
    }

    private function getAccessRole()
    {
        $classPath = explode('\\', $this->class);
        $config = $this->application->getConfig()->getSecurityConfig();

        foreach ($array as $value) {
        }
    }
}
