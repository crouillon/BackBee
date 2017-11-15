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

namespace BackBee\Routing\Tests\Matcher;

use Symfony\Component\HttpFoundation\Request;

use BackBee\Routing\Matcher\RequestMatcher;

/**
 * Test suite for class RequestMatcher
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Routing\Matcher\RequestMatcher
 */
class RequestMatcherTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $matcher = new RequestMatcher();
        $matcher->matchHeaders(['header1' => '[0-9]+', 'header2' => '[a-z]+']);

        $request = new Request();
        $this->assertFalse($matcher->matches($request));

        $request->headers->set('header1', 1);
        $this->assertFalse($matcher->matches($request));

        $request->headers->set('header2', 'a');
        $this->assertTrue($matcher->matches($request));
    }
}
