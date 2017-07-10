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

namespace BackBee\Cache\Tests\Validator;

use Symfony\Component\HttpFoundation\Request;

use BackBee\Cache\Validator\RequestMethodValidator;

/**
 * Tests suite for class RequestMethodValidator
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class RequestMethodValidatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers BackBee\Cache\Validator\RequestMethodValidator::__construct()
     * @covers BackBee\Cache\Validator\RequestMethodValidator::getGroups()
     */
    public function testConstruct()
    {
        $validator = new RequestMethodValidator(new Request(), [], ['group1', 'group2']);
        $this->assertEquals(['default', 'group1', 'group2'], $validator->getGroups());
    }

    /**
     * @covers BackBee\Cache\Validator\RequestMethodValidator::isValid()
     */
    public function testIsValid()
    {
        $request = new Request();
        $request->setMethod('post');

        $invalidMethod = new RequestMethodValidator($request, ['GET']);
        $this->assertFalse($invalidMethod->isValid());

        $request->setMethod('get');
        $validMethod = new RequestMethodValidator($request, ['gEt']);
        $this->assertTrue($validMethod->isValid());
    }
}
