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

namespace BackBee\Security\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\SchemaTool;

use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Site;
use BackBee\Tests\Traits\CreateEntityManagerTrait;

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
    use CreateEntityManagerTrait;

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

    /**
     */
    public function testEntity()
    {
        $entityMng = $this->createEntityManager();
        $schemaTool = new SchemaTool($entityMng);
        $sql = $schemaTool->getCreateSchemaSql([
            $entityMng->getClassMetadata(Group::class)
        ]);

        $expected = [
            'CREATE TABLE "group" (' .
                'id INTEGER NOT NULL, ' .
                'site_uid VARCHAR(32) DEFAULT NULL, ' .
                'name VARCHAR(255) NOT NULL, ' .
                'description VARCHAR(255) DEFAULT NULL, ' .
                'PRIMARY KEY(id), ' .
                'CONSTRAINT FK_6DC044C5A7063726 ' .
                    'FOREIGN KEY (site_uid) ' .
                    'REFERENCES site (uid) ' .
                    'NOT DEFERRABLE INITIALLY IMMEDIATE' .
            ')',
            'CREATE INDEX IDX_6DC044C5A7063726 ON "group" (site_uid)',
            'CREATE UNIQUE INDEX UNI_IDENTIFIER ON "group" (id)',
            'CREATE TABLE user_group (' .
                'group_id INTEGER NOT NULL, ' .
                'user_id INTEGER NOT NULL, ' .
                'PRIMARY KEY(group_id, user_id), ' .
                'CONSTRAINT FK_8F02BF9DFE54D947 ' .
                    'FOREIGN KEY (group_id) ' .
                    'REFERENCES "group" (id) ' .
                    'NOT DEFERRABLE INITIALLY IMMEDIATE, ' .
                'CONSTRAINT FK_8F02BF9DA76ED395 ' .
                    'FOREIGN KEY (user_id) ' .
                    'REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE' .
            ')',
            'CREATE INDEX IDX_8F02BF9DFE54D947 ON user_group (group_id)',
            'CREATE INDEX IDX_8F02BF9DA76ED395 ON user_group (user_id)',
        ];

        $this->assertEquals($expected, $sql);
    }
}
