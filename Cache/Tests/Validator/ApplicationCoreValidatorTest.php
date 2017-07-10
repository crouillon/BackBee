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

use BackBee\BBApplication;
use BackBee\Cache\Validator\ApplicationCoreValidator;

/**
 * Tests suite for class ApplicationCoreValidator
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class ApplicationCoreValidatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var BBApplication
     */
    private $application;

    /**
     * @var ApplicationCoreValidator
     */
    private $validator;

    /**
     * Sets up the fixtures
     */
    protected function setUp()
    {
        parent::setUp();

        $this->application = $this->getMockBuilder(BBApplication::class)
                ->disableOriginalConstructor()
                ->setMethods(['isDebugMode', 'getBBUserToken', 'isStarted', 'isClientSAPI'])
                ->getMock();
        $this->validator = new ApplicationCoreValidator($this->application);
    }

    /**
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::__construct()
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::getGroups()
     */
    public function testContruct()
    {
        $validator = new ApplicationCoreValidator(
            $this->application,
            ['group1', 'group2']
        );

        $this->assertEquals(['default', 'group1', 'group2'], $validator->getGroups());
    }

    /**
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::isValid()
     */
    public function testInvalidDebugMode()
    {
        $this->application->expects($this->once())->method('isDebugMode')->willReturn(true);

        $this->assertFalse($this->validator->isValid());
    }

    /**
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::isValid()
     */
    public function testInvalidToken()
    {
        $this->application->expects($this->once())->method('isDebugMode')->willReturn(false);
        $this->application->expects($this->once())->method('getBBUserToken')->willReturn('token');

        $this->assertFalse($this->validator->isValid());
    }

    /**
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::isValid()
     */
    public function testInvalidStarted()
    {
        $this->application->expects($this->once())->method('isDebugMode')->willReturn(false);
        $this->application->expects($this->once())->method('getBBUserToken')->willReturn(null);
        $this->application->expects($this->once())->method('isStarted')->willReturn(false);

        $this->assertFalse($this->validator->isValid());
    }

    /**
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::isValid()
     */
    public function testInvalidSapi()
    {
        $this->application->expects($this->once())->method('isDebugMode')->willReturn(false);
        $this->application->expects($this->once())->method('getBBUserToken')->willReturn(null);
        $this->application->expects($this->once())->method('isStarted')->willReturn(true);
        $this->application->expects($this->once())->method('isClientSAPI')->willReturn(true);

        $this->assertFalse($this->validator->isValid());
    }

    /**
     * @covers BackBee\Cache\Validator\ApplicationCoreValidator::isValid()
     */
    public function testIsValid()
    {
        $this->application->expects($this->once())->method('isDebugMode')->willReturn(false);
        $this->application->expects($this->once())->method('getBBUserToken')->willReturn(null);
        $this->application->expects($this->once())->method('isStarted')->willReturn(true);
        $this->application->expects($this->once())->method('isClientSAPI')->willReturn(false);

        $this->assertTrue($this->validator->isValid());
    }
}
