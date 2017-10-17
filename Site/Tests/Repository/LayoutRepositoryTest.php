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

namespace BackBee\Site\Tests\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use BackBee\Site\Repository\LayoutRepository;

/**
 * Test suite for class LayoutRepository
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Site\Repository\LayoutRepository
 */
class LayoutRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var LayoutRepository
     */
    private $repository;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var AbstractQuery
     */
    private $query;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->repository = $this->getMockBuilder(LayoutRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'findOneBy'])
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$entityMng])
            ->setMethods(['getQuery'])
            ->getMock();

        $this->query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            false,
            true,
            ['getResult']
        );

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
    }

    /**
     * @covers ::getModels()
     */
    public function testGetModels()
    {
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->assertEquals([], $this->repository->getModels());
    }

    /**
     * @covers ::getModels()
     */
    public function testGetUnfoundModels()
    {
        $this->query->expects($this->once())
            ->method('getResult')
            ->willThrowException(new \Exception());

        $this->assertEquals([], $this->repository->getModels());
    }
}
