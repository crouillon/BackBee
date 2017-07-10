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

namespace BackBee\Cache\Validator;

use BackBee\BBApplication;

/**
 * This cache validator checks requirements on application instance:
 *   - application debug value must be false
 *   - AND application isClientSAPI() must return false
 *   - AND current user must be not BBUser
 *   - AND application must be started.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class ApplicationCoreValidator implements ValidatorInterface
{

    /**
     * @var BBApplication
     */
    private $application;

    /**
     * list of group name this validator belong to.
     *
     * @var array
     */
    private $groups;

    /**
     * constructor.
     *
     * @param BBApplication $application the application from which we will checks core requirements
     * @param mixed         $groups      group(s) name(s) which this validator is associated with
     */
    public function __construct(BBApplication $application, $groups = null)
    {
        $this->application = $application;
        $this->groups = array_merge(['default'], (array) $groups);
    }

    /**
     * @see ValidatorInterface::isValid
     */
    public function isValid($object = null)
    {
        return false === $this->application->isDebugMode()
                && null === $this->application->getBBUserToken()
                && true === $this->application->isStarted()
                && false === $this->application->isClientSAPI()
        ;
    }

    /**
     * @see ValidatorInterface::getGroups
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
