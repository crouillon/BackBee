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

namespace BackBee\Security\Tests\Authorization\Voter;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use BackBee\BBApplication;
use BackBee\Config\Config;
use BackBee\Security\Authorization\Voter\SudoVoter;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class SudoVoter
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authorization\Voter\SudoVoter
 */
class SudoVoterTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @covers ::__construct()
     * @covers ::vote()
     * @covers ::supportsToken()
     */
    public function testVote()
    {
        $application = $this->getMockBuilder(BBApplication::class)
                ->disableOriginalConstructor()
                ->setMethods(['getConfig'])
                ->getMock();

        $config = $this->getMockBuilder(Config::class)
                ->disableOriginalConstructor()
                ->setMethods(['getSecurityConfig'])
                ->getMock();

        $application->expects($this->once())
                ->method('getConfig')
                ->willReturn($config);

        $config->expects($this->once())
                ->method('getSecurityConfig')
                ->willReturn(['sudoer' => 1]);

        $voter = new SudoVoter($application);

        $anoToken = new AnonymousToken('secret', 'user');
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($anoToken, new \stdClass(), ['VIEW']));

        $user = new User('sudoer');
        $this->invokeProperty($user, '_id', 1);

        $bbToken = new BBUserToken();
        $bbToken->setUser($user);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($bbToken, new \stdClass(), ['VIEW']));

        $this->invokeProperty($user, '_id', 2);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($bbToken, new \stdClass(), ['VIEW']));
    }
}
