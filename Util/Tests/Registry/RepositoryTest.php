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

namespace BackBee\Util\Tests\Registry;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

use BackBee\Util\Registry\Registry;
use BackBee\Util\Registry\Repository;

/**
 * Tests suite for class Repository.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Registry\Repository
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityManager
     */
    private $entityMng;

    /**
     * @var Repository
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
            ->setMethods(['contains', 'persist', 'remove', 'flush', 'getUnitOfWork'])
            ->getMock();

        $this->repository = new Repository(
            $this->entityMng,
            new ClassMetadata(Registry::class)
        );
    }

    /**
     * @cover ::save()
     */
    public function testSaveNotManaged()
    {
        $this->entityMng
            ->expects($this->once())
            ->method('contains')
            ->willReturn(false);
        $this->entityMng
            ->expects($this->once())
            ->method('persist');
        $this->entityMng
            ->expects($this->once())
            ->method('flush');

        $registry = new Registry();
        $this->assertEquals($registry, $this->repository->save($registry));
    }

    /**
     * @cover ::save()
     */
    public function testSaveManaged()
    {
        $this->entityMng
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->entityMng
            ->expects($this->never())
            ->method('persist');
        $this->entityMng
            ->expects($this->once())
            ->method('flush');

        $registry = new Registry();
        $this->assertEquals($registry, $this->repository->save($registry));
    }

    /**
     * @covers ::remove()
     */
    public function testRemoveNotNew()
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityState'])
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_MANAGED);

        $this->entityMng
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $this->entityMng
            ->expects($this->once())
            ->method('remove');
        $this->entityMng
            ->expects($this->once())
            ->method('flush');

        $registry = new Registry();
        $this->assertEquals($registry, $this->repository->remove($registry));
    }

    /**
     * @covers ::remove()
     */
    public function testRemoveNew()
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityState'])
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_NEW);

        $this->entityMng
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $this->entityMng
            ->expects($this->never())
            ->method('remove');
        $this->entityMng
            ->expects($this->never())
            ->method('flush');

        $registry = new Registry();
        $this->assertEquals($registry, $this->repository->remove($registry));
    }
}
