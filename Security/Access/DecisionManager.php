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

namespace BackBee\Security\Access;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use BackBee\BBApplication;

/**
 * Class for all access decision managers that use decision voters.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class DecisionManager extends AccessDecisionManager
{

    /**
     * The current BackBee application.
     *
     * @var BBApplication
     */
    private $application;

    /**
     * Allow to try BBToken in voters if access is not granted.
     *
     * @var Boolean
     */
    private $tryBBTokenOnDenied;

    /**
     * Constructor.
     *
     * @param VoterInterface[] $voters                             An array of VoterInterface instances
     * @param string           $strategy                           The vote strategy
     * @param bool             $allowIfAllAbstainDecisions         Whether to grant access if all voters
     *                                                             abstained or not
     * @param bool             $allowIfEqualGrantedDeniedDecisions Whether to grant access if result are equals
     * @param Boolean          $tryBBTokenOnDenied                 Allow to try BBToken in voters if access
     *                                                             is not granted
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        array $voters = [],
        $strategy = self::STRATEGY_AFFIRMATIVE,
        $allowIfAllAbstainDecisions = false,
        $allowIfEqualGrantedDeniedDecisions = true,
        $tryBBTokenOnDenied = true
    ) {
        parent::__construct(
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->tryBBTokenOnDenied = $tryBBTokenOnDenied;
    }

    /**
     * Sets the current application.
     *
     * @param  BBApplication $application
     *
     * @return DecisionManager
     */
    public function setApplication(BBApplication $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Decides whether the access is possible or not.
     *
     * @param  TokenInterface $token      A TokenInterface instance
     * @param  array          $attributes An array of attributes associated with the method being invoked
     * @param  object         $object     The object to secure
     *
     * @return bool true if the access is granted, false otherwise
     */
    public function decide(TokenInterface $token, array $attributes, $object = null)
    {
        $result = parent::decide($token, $attributes, $object);

        if (false === $result
            && true === $this->tryBBTokenOnDenied
            && null !== $this->application
            && (null !== $bbToken = $this->application->getBBUserToken())
        ) {
            $result = parent::decide($bbToken, $attributes, $object);
        }

        return $result;
    }
}
