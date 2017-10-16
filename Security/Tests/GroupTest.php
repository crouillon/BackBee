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

namespace BackBee\Security\Tests;

use Doctrine\Common\Collections\ArrayCollection;

use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Site;

/**
 * Test suite for class Group entity.
 *
 * @category    BackBee
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Group
 */
class GroupTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Group
     */
    private $group;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->group = new Group();
        $this->group->setId(1);
    }

    /**
     * @covers ::__construct()
     * @covers ::getUsers()
     * @covers ::setUsers()
     * @covers ::addUser()
     * @covers ::removeUser()
     */
    public function testUsers()
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->group->getUsers());

        $users = new ArrayCollection([new User()]);
        $this->assertEquals($this->group, $this->group->setUsers($users));

        $user = new User();
        $this->assertEquals($this->group, $this->group->addUser($user));
        $this->assertEquals($this->group, $this->group->removeUser($user));
    }

    /**
     * @covers ::getSiteUid()
     */
    public function testGetSiteUid()
    {
        $this->assertNull($this->group->getSiteUid());
        $this->assertEquals($this->group, $this->group->setSite(new Site('site-uid')));
        $this->assertEquals('site-uid', $this->group->getSiteUid());
    }

    /**
     * @covers ::getObjectIdentifier()
     */
    public function testGetObjectIdentifier()
    {
        $this->assertEquals(1, $this->group->getObjectIdentifier());
    }
}
