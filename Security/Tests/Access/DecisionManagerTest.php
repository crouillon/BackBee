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

namespace BackBee\Security\Tests\Access;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use BackBee\BBApplication;
use BackBee\Security\Access\DecisionManager;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class DecisionManager
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Access\DecisionManager
 */
class DecisionManagerTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @var BBApplication
     */
    private $application;

    /**
     * @var DecisionManager
     */
    private $manager;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->manager = new DecisionManager();

        $this->application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBBUserToken'])
            ->getMock();
    }

    /**
     * @covers ::setApplication()
     */
    public function testSetApplication()
    {
        $this->assertEquals($this->manager, $this->manager->setApplication($this->application));
        $this->assertEquals($this->application, $this->invokeProperty($this->manager, 'application'));
    }

    /**
     * @covers ::__construct()
     * @covers ::decide()
     */
    public function testDecideWithoutToken()
    {
        $manager = new DecisionManager([], 'affirmative', false, true, false);
        $manager->setApplication($this->application);
        $this->assertFalse($manager->decide(new AnonymousToken('secret', 'user'), []));

        $this->assertFalse($this->manager->decide(new AnonymousToken('secret', 'user'), []));

        $this->application->expects($this->once())->method('getBBUserToken');
        $this->manager->setApplication($this->application);

        $this->assertFalse($this->manager->decide(new AnonymousToken('secret', 'user'), []));
    }

    /**
     * @covers ::decide()
     */
    public function testDecide()
    {
        $this->application
            ->expects($this->any())
            ->method('getBBUserToken')
            ->willReturn(new AnonymousToken('secret', 'user'));

        $this->manager->setApplication($this->application);

        $this->assertFalse($this->manager->decide(new AnonymousToken('secret', 'user'), []));
    }
}
