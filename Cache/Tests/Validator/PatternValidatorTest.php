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

use BackBee\Cache\Validator\PatternValidator;

/**
 * Tests suite for class PatternValidator
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class PatternValidatorTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @covers BackBee\Cache\Validator\PatternValidator::__construct()
     * @covers BackBee\Cache\Validator\PatternValidator::getGroups()
     */
    public function testConstruct()
    {
        $validator = new PatternValidator([], ['group1', 'group2']);
        $this->assertEquals(['group1', 'group2'], $validator->getGroups());
    }

    /**
     * @covers BackBee\Cache\Validator\PatternValidator::isValid()
     */
    public function testIsValid()
    {
        $validator = new PatternValidator(['Exc[l]+uDe']);

        $this->assertTrue($validator->isValid());
        $this->assertTrue($validator->isValid('include'));
        $this->assertFalse($validator->isValid('exclude'));
    }
}
