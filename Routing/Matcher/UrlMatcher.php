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
use Symfony\Component\Routing\Matcher\UrlMatcher as sfUrlMatcher;
use Symfony\Component\Routing\Route;

use BackBee\Routing\RequestContext;
use BackBee\Utils\File\File;

/**
 * UrlMatcher matches URL based on a set of routes.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class UrlMatcher extends sfUrlMatcher
{

    /**
     * Handles specific route requirements.
     *
     * @param string $pathinfo The path
     * @param string $name     The route name
     * @param string $route    The route
     *
     * @return array The first element represents the status, the second contains additional information
     */
    protected function handleRouteRequirements($pathinfo, $name, Route $route)
    {
        $path = File::normalizePath($pathinfo, '/', false);
        $status = parent::handleRouteRequirements($path, $name, $route);

        $headerRequirements = $route->getRequirements('HTTP-');
        if (self::REQUIREMENT_MATCH == $status[0] && 0 < count($headerRequirements)) {
            if (null === $request = $this->getContext()->getRequest()) {
                return [self::REQUIREMENT_MISMATCH, null];
            }

            $requestMatcher = new RequestMatcher();
            $requestMatcher->matchHeaders($headerRequirements);

            return [
                $requestMatcher->matches($request) ? self::REQUIREMENT_MATCH : self::REQUIREMENT_MISMATCH,
                null
            ];
        }

        return $status;
    }

    /**
     * Tries to match a URL with a set of routes.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     */
    public function match($pathinfo)
    {
        $path = File::normalizePath($pathinfo, '/', false);

        return parent::match($path);
    }

    /**
     * Tries to match a request with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param Request $request The request to match
     *
     * @return array An array of parameters
     */
    public function matchRequest(Request $request)
    {
        $context = new RequestContext();
        $context->fromRequest($request);
        $this->setContext($context);

        return parent::matchRequest($request);
    }
}
