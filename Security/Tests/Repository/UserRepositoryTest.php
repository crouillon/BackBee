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

namespace BackBee\Security\Tests\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

use BackBee\Security\Repository\UserRepository;
use BackBee\Security\User;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Test suite for class UserRepostory.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Repository\UserRepository
 */
class UserRepositoryTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;

    /**
     * @var EntityManager
     */
    private $entityMng;

    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'createQueryBuilder'])
            ->getMock();
    }

    /**
     * @covers            ::loadUserByPublicKey()
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUnknownUserByPublicKey()
    {
        $this->repository->loadUserByPublicKey('key');
    }

    /**
     * @covers ::loadUserByPublicKey()
     */
    public function testLoadUserByPublicKey()
    {
        $user = new User();
        $user->setActivated(true);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->assertEquals($user, $this->repository->loadUserByPublicKey('key'));
    }

    /**
     * @covers            ::loadUserByUsername()
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUnknownUserByUsername()
    {
        $this->repository->loadUserByUsername('username');
    }

    /**
     * @covers ::loadUserByUsername()
     */
    public function testLoadUserByUsername()
    {
        $user = new User();
        $user->setActivated(true);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->assertEquals($user, $this->repository->loadUserByUsername('username'));
    }

    /**
     * @covers            ::checkActivatedStatus()
     * @expectedException \Symfony\Component\Security\Core\Exception\DisabledException
     */
    public function testCheckInvalidStatus()
    {
        $this->invokeMethod($this->repository, 'checkActivatedStatus', [new User()]);
    }

    /**
     * @covers ::checkActivatedStatus()
     */
    public function testCheckActivatedStatus()
    {
        $user = new User();
        $user->setActivated(true);

        $this->assertEquals(
            $user,
            $this->invokeMethod($this->repository, 'checkActivatedStatus', [$user])
        );
    }

    /**
     * @covers            ::refreshUser()
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshInvalidUser()
    {
        $user = $this->getMockForAbstractClass(UserInterface::class);
        $this->repository->refreshUser($user);
    }

    /**
     * @covers ::refreshUser()
     */
    public function testRefreshUser()
    {
        $user = new User();
        $user->setActivated(true);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->assertEquals(
            $user,
            $this->repository->refreshUser($user)
        );
    }

    /**
     * @covers ::supportsClass()
     */
    public function testSupportsClass()
    {
        $this->assertTrue($this->repository->supportsClass(User::class));
        $this->assertFalse($this->repository->supportsClass(\stdClass::class));
    }

    /**
     * @covers ::getCollection()
     * @covers ::addNameCriteria()
     * @covers ::addCriteria()
     */
    public function testGetCollection()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$this->entityMng])
            ->setMethods(['expr', 'getQuery'])
            ->getMock();

        $queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn(new Expr());

        $query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            false,
            true,
            ['getResult']
        );

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->getCollection(['name' => 'name1 name2']);
        $this->assertEquals(
            'SELECT WHERE (u._firstname LIKE :p0 OR u._lastname LIKE :p0) '
            . 'AND (u._firstname LIKE :p1 OR u._lastname LIKE :p1)',
            $queryBuilder->getDQL()
        );

        $queryBuilder->resetDQLParts();
        $queryBuilder->setParameters(new ArrayCollection());

        $this->repository->getCollection(['firstname' => 'firstname', 'created' => 'created', 'unknown' => 'unknown']);
        $this->assertEquals(
            'SELECT WHERE u._firstname LIKE :firstname AND u._created = :created',
            $queryBuilder->getDQL()
        );
    }
}
