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
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\Token\PublicKeyToken;

/**
 * A voter for sudoers.
 *
 * @author Nicolas Dufreche, Eric Chau <eric.chau@lp-digital.fr>
 */
class SudoVoter implements VoterInterface
{

    /**
     * @var array
     */
    private $sudoers;

    /**
     * Voter constructor.
     *
     * @param BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->sudoers = $application->getConfig()->getSecurityConfig('sudoers') ?: [];
    }

    /**
     * Checks if the voter supports the given attribute.
     *
     * @param  mixed $attribute An attribute (usually the attribute name string)
     *
     * @return bool true if this Voter supports the attribute, false otherwise
     *
     * @deprecated since version 1.4, to be removed in 1.5
     */
    public function supportsAttribute($attribute)
    {
        return 0 === preg_match('#^ROLE#', $attribute);
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param  string $class A class name
     *
     * @return bool true if this Voter can process the class
     *
     * @deprecated since version 1.4, to be removed in 1.5.
     */
    public function supportsClass($class)
    {
        return $this->supportsToken($class);
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param  string $class A class name
     *
     * @return bool true if this Voter can process the class
     */
    protected function supportsToken($class)
    {
        return $class === BBUserToken::class
            || $class === PublicKeyToken::class
        ;
    }

    /**
     * Returns the vote for the given parameters.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param object|null    $object     The object to secure
     * @param array          $attributes An array of attributes associated with the method being invoked
     *
     * @return int either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($this->supportsToken(get_class($token))) {
            $userId = $token->getUser()->getId();
            $login = $token->getUser()->getUsername();

            if (isset($this->sudoers[$login])
                && $this->sudoers[$login] === $userId
            ) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
