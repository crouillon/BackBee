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

namespace BackBee\Security;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\FirewallMap as sfFirewallMap;

/**
 * FirewallMap allows configuration of different firewalls for specific parts
 * of the website.
 * Add the unshift feature to symfony FirewallMap.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class FirewallMap extends sfFirewallMap
{

    /**
     * @var array
     */
    protected $map = [];

    /**
     * Prepends a request matcher to the firewall map.
     *
     * @param RequestMatcherInterface $requestMatcher
     * @param array                   $listeners
     * @param ExceptionListener       $exceptionListener
     */
    public function unshift(
        RequestMatcherInterface $requestMatcher = null,
        array $listeners = [],
        ExceptionListener $exceptionListener = null
    ) {
        array_unshift($this->map, [$requestMatcher, $listeners, $exceptionListener]);
    }
}
