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

namespace BackBee\Routing;

use Symfony\Component\Routing\Route as sfRoute;

/**
 * A Route describes a route and its parameters.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Route extends sfRoute
{

    /**
     * Part of requirements related to headers.
     *
     * @var array
     */
    private $headerRequirements;

    /**
     * Constructor.
     *
     * Available requirements:
     *  - HTTP-<<headername>> : HTTP header value required
     *
     * @param string $pattern      The pattern to match
     * @param array  $defaults     An array of default parameter values
     * @param array  $requirements An array of requirements for parameters (regexes)
     * @param array  $options      An array of options
     */
    public function __construct($pattern, array $defaults = [], array $requirements = [], array $options = [])
    {
        parent::__construct($pattern, $defaults, $requirements, $options);

        $this->addHeaderRequirements();
    }

    /**
     * Extracts header requirements.
     *
     * @return Route The current Route instance
     */
    private function addHeaderRequirements()
    {
        $this->headerRequirements = [];
        foreach ($this->getRequirements() as $key => $value) {
            if (0 === strpos($key, 'HTTP-')) {
                $this->headerRequirements[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Adds requirements.
     * This method implements a fluent interface.
     *
     * @param array $requirements The requirements
     *
     * @return Route The current Route instance
     */
    public function addRequirements(array $requirements)
    {
        parent::addRequirements($requirements);
        $this->addHeaderRequirements();

        return $this;
    }

    /**
     * Returns the requirements.
     *
     * @return array The requirements
     */
    public function getRequirements($startingWith = null)
    {
        if (null === $startingWith) {
            return parent::getRequirements();
        }

        return ('HTTP-' === $startingWith) ? $this->headerRequirements : [];
    }
}
