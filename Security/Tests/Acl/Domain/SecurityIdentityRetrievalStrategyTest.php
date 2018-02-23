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

namespace BackBee\Security\Tests\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

use BackBee\Security\Acl\Domain\SecurityIdentityRetrievalStrategy;
use BackBee\Security\Group;
use BackBee\Security\User;

/**
 * Test suite for class SecurityIdentityRetrievalStrategy
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Acl\Domain\SecurityIdentityRetrievalStrategy
 */
class SecurityIdentityRetrievalStrategyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SecurityIdentityRetrievalStrategy
     */
    protected $strategy;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        $this->strategy = new SecurityIdentityRetrievalStrategy(
            new RoleHierarchy([]),
            new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class)
        );

        parent::setUp();
    }

    /**
     * @covers ::getSecurityIdentities()
     */
    public function testGetSecurityIdentities()
    {
        $group = new Group();
        $group->setId(1);

        $user = new User('username');
        $user->addGroup($group);

        $token = new UsernamePasswordToken($user, 'password', 'key');
        $sids = $this->strategy->getSecurityIdentities($token);

        $this->assertTrue(is_array($sids) && isset($sids[0]));
        $this->assertInstanceOf(UserSecurityIdentity::class, $sids[0]);
        $this->assertTrue($sids[0]->equals(new UserSecurityIdentity('1', Group::class)));
    }
}
