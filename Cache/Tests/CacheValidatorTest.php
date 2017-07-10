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

namespace BackBee\Cache\Tests;

use BackBee\Cache\CacheValidator;
use BackBee\DependencyInjection\Container;

/**
 * Tests suite for class CacheValidator.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheValidatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var CacheValidator
     */
    private $validator;

    /**
     * Sets up the fixtures.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->validator = new CacheValidator(new Container());
    }

    /**
     * @covers BackBee\Cache\CacheValidator::__construct()
     */
    public function testConstruct()
    {
        $container = $this->getMock(Container::class);

        $container->expects($this->once())
                ->method('findTaggedServiceIds')
                ->will($this->returnValue(['id']));

        $container->expects($this->once())
                ->method('get')
                ->will($this->returnValue(new Mock\MockAppenderValidator()));

        new CacheValidator($container);
    }

    /**
     * @covers BackBee\Cache\CacheValidator::addValidator()
     * @covers BackBee\Cache\CacheValidator::isValidGroup()
     */
    public function testAddValidator()
    {
        $appender = new Mock\MockAppenderValidator();
        $this->validator->addValidator($appender);

        $this->assertTrue($this->validator->isValidGroup('group1'));
        $this->assertTrue($this->validator->isValidGroup('group2'));
        $this->assertFalse($this->validator->isValidGroup('group'));
    }

    /**
     * @covers            BackBee\Cache\CacheValidator::isValid()
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testIsValidInvalidGroup()
    {
        $this->validator->isValid('group');
    }

    /**
     * @covers BackBee\Cache\CacheValidator::isValid()
     */
    public function testIsValid()
    {
        $appender = new Mock\MockAppenderValidator();
        $this->validator->addValidator($appender);

        $this->assertTrue($this->validator->isValid('group1', true));
        $this->assertFalse($this->validator->isValid('group1'));
    }
}
