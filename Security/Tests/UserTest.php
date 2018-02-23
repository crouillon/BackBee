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

use BackBee\Security\User;
use BackBee\Tests\Traits\CreateEntityManagerTrait;

/**
 * Test suite for class User entity.
 *
 * @category    BackBee
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\User
 */
class UserTest extends \PHPUnit_Framework_TestCase
{
    use CreateEntityManagerTrait;

    /**
     * @var User
     */
    private $user;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->user = new User('login', 'password', 'firstname', 'lastname');
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $user = new User();
        $this->assertEquals('', $user->getLogin());
        $this->assertEquals('', $user->getPassword());
        $this->assertInstanceOf('\DateTime', $user->getCreated());
        $this->assertInstanceOf('\DateTime', $user->getModified());
        $this->assertInstanceOf(ArrayCollection::class, $user->getGroups());
        $this->assertInstanceOf(ArrayCollection::class, $user->getRevisions());

        $this->assertEquals('login', $this->user->getLogin());
        $this->assertEquals('password', $this->user->getPassword());
        $this->assertEquals('firstname', $this->user->getFirstname());
        $this->assertEquals('firstname', $this->user->getFirstname());
        $this->assertEquals('lastname', $this->user->getLastname());
    }

    /**
     * @covers ::getUid()
     */
    public function testGetUid()
    {
        $this->assertEquals($this->user->getId(), $this->user->getUid());
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $this->assertEquals('firstname lastname (login)', $this->user->__toString());
    }

    /**
     * @covers ::serialize
     */
    public function testSerialize()
    {
        $this->assertEquals('{"username":"login","commonname":"firstname lastname"}', $this->user->serialize());
    }

    /**
     * @covers ::generateRandomApiKey
     * @covers ::generateApiPublicKey
     */
    public function testGenerateRandomApiKey()
    {
        $this->user->generateRandomApiKey();
        $this->assertNotNull($this->user->getApiKeyPrivate());
        $this->assertEquals(
            sha1($this->user->getCreated()->format(\DateTime::ATOM) . $this->user->getApiKeyPrivate()),
            $this->user->getApiKeyPublic()
        );
    }

    /**
     * @covers ::checkPublicApiKey
     * @covers ::generateApiPublicKey
     */
    public function testCheckPublicApiKey()
    {
        $this->assertFalse($this->user->checkPublicApiKey(''));

        $expected = sha1($this->user->getCreated()->format(\DateTime::ATOM) . $this->user->getApiKeyPrivate());
        $this->assertTrue($this->user->checkPublicApiKey($expected));
    }

    /**
     * @covers ::setApiKeyEnabled
     * @covers ::generateKeysOnNeed
     */
    public function testSetApiKeyEnabled()
    {
        $this->assertNull($this->user->getApiKeyPrivate());
        $this->assertNull($this->user->getApiKeyPublic());

        $this->user->setApiKeyEnabled(true);
        $privateKey = $this->user->getApiKeyPrivate();
        $publicKey = $this->user->getApiKeyPublic();

        $this->assertNotNull($privateKey);
        $this->assertNotNull($publicKey);

        $this->user->setApiKeyEnabled(false);
        $this->assertEquals($privateKey, $this->user->getApiKeyPrivate());
        $this->assertEquals($publicKey, $this->user->getApiKeyPublic());
    }

    /**
     * @covers ::updateModified()
     */
    public function testUpdateModified()
    {
        $modified = $this->user->getModified()->getTimestamp();
        $this->user->updateModified();

        $this->assertTrue($this->user->getModified()->getTimestamp() >= $modified);
    }

    /**
     */
    public function testEntity()
    {
        $entityMng = $this->createEntityManager();
        $schemaTool = new SchemaTool($entityMng);
        $sql = $schemaTool->getCreateSchemaSql([
            $entityMng->getClassMetadata(User::class)
        ]);

        $expected = [
            'CREATE TABLE user (' .
                'id INTEGER NOT NULL, ' .
                'login VARCHAR(255) NOT NULL, ' .
                'email VARCHAR(255) NOT NULL, ' .
                'password VARCHAR(255) NOT NULL, ' .
                'state INTEGER DEFAULT 0 NOT NULL, ' .
                'activated BOOLEAN NOT NULL, ' .
                'firstname VARCHAR(255) DEFAULT NULL, ' .
                'lastname VARCHAR(255) DEFAULT NULL, ' .
                'api_key_public VARCHAR(255) DEFAULT NULL, ' .
                'api_key_private VARCHAR(255) DEFAULT NULL, ' .
                'api_key_enabled BOOLEAN DEFAULT \'0\' NOT NULL, ' .
                'created DATETIME NOT NULL, ' .
                'modified DATETIME NOT NULL, ' .
                'PRIMARY KEY(id)' .
            ')',
            'CREATE UNIQUE INDEX UNI_LOGIN ON user (login)'
        ];

        $this->assertEquals($expected, $sql);
    }
}
