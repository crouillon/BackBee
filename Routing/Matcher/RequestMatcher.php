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

namespace BackBee\Routing\Matcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher as sfRequestMatcher;

/**
 * RequestMatcher compares a pre-defined set of checks against a Request instance.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class RequestMatcher extends sfRequestMatcher
{

    /**
     * Headers attributes.
     *
     * @var array
     */
    private $headers;

    /**
     * Constructor.
     *
     * @param string|null          $path
     * @param string|null          $host
     * @param string|string[]|null $methods
     * @param string|string[]|null $ips
     * @param array                $attributes
     * @param array                $headers
     * @param string|string[]|null $schemes
     */
    public function __construct(
        $path = null,
        $host = null,
        $methods = null,
        $ips = null,
        array $attributes = [],
        array $headers = [],
        $schemes = null
    ) {
        $this->headers = $headers;
        parent::__construct($path, $host, $methods, $ips, $attributes, $schemes);
    }

    /**
     * Adds a check for header attribute.
     *
     * @param string $key    The header attribute name
     * @param string $regexp A Regexp
     */
    public function matchHeader($key, $regexp)
    {
        $this->headers[$key] = $regexp;
    }

    /**
     * Adds checks for header attributes.
     *
     * @param array The header attributes to check [attribute1 => regexp1, ettribute2 => regexp2, ...]
     */
    public function matchHeaders($attributes)
    {
        foreach ((array) $attributes as $key => $regexp) {
            $this->matchHeader($key, $regexp);
        }
    }

    /**
     * Decides whether the rule(s) implemented by the strategy matches the supplied request.
     *
     * @param Request $request The request to check for a match
     *
     * @return bool true if the request matches, false otherwise
     */
    public function matches(Request $request)
    {
        foreach ($this->headers as $key => $pattern) {
            if (!preg_match('#'.str_replace('#', '\\#', $pattern).'#', $request->headers->get($key))) {
                return false;
            }
        }

        return parent::matches($request);
    }
}
