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

/**
 * PatternValidator will invalid string that match with pattern to exclude.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class PatternValidator implements ValidatorInterface
{

    /**
     * List of url pattern to exclude from cache candidates.
     *
     * @var array
     */
    private $excludedPatterns;

    /**
     * list of group name this validator belong to.
     *
     * @var array
     */
    private $groups;

    /**
     * Validator constructor.
     *
     * @param array $excludedPatterns array of excluded pattern.
     * @param array $groups           list of groups this validator belongs to
     */
    public function __construct(array $excludedPatterns, $groups = ['page'])
    {
        $this->excludedPatterns = $excludedPatterns;
        $this->groups = (array) $groups;
    }

    /**
     * @see ValidatorInterface::isValid
     */
    public function isValid($string = null)
    {
        $isValid = true;
        if (is_string($string)) {
            foreach ($this->excludedPatterns as $pattern) {
                if (1 === preg_match("#$pattern#i", $string)) {
                    $isValid = false;
                    break;
                }
            }
        }

        return $isValid;
    }

    /**
     * @see ValidatorInterface::getGroups
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
