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

namespace BackBee\Routing\Tests;

use Symfony\Component\HttpFoundation\Request;

use BackBee\Routing\RequestContext;

/**
 * Test suite for class RequestContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Routing\RequestContext
 */
class RequestContextTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::fromRequest()
     * @covers ::getRequest()
     */
    public function test()
    {
        $context = new RequestContext();
        $request = new Request();

        $context->fromRequest($request);
        $this->assertEquals($request, $context->getRequest());
    }
}