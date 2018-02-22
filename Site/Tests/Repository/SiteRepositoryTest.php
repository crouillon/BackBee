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

namespace BackBee\Site\Tests\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use BackBee\Site\Repository\SiteRepository;

/**
 * Test suite for class SiteRepository
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Site\Repository\SiteRepository
 */
class SiteRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityManager
     */
    private $entityMng;

    /**
     * @var SiteRepository
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

        $this->repository = $this->getMockBuilder(SiteRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'findOneBy'])
            ->getMock();
    }

    /**
     * @covers ::findByServerName()
     */
    public function testFindByServerName()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$this->entityMng])
            ->setMethods(['getQuery'])
            ->getMock();

        $query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            false,
            true,
            ['getOneOrNullResult']
        );
        $query->expects($this->once())->method('getOneOrNullResult');

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->findByServerName('server-name');
        $this->assertEquals(
            'SELECT WHERE s._server_name = :server_name',
            $queryBuilder->getDQL()
        );
        $this->assertEquals('server-name', $queryBuilder->getParameter('server_name')->getValue());
    }

    /**
     * @covers ::findByCustomServerName()
     */
    public function testfindByCustomServerName()
    {
        $serverName = 'server-name';
        $this->assertNull($this->repository->findByCustomServerName($serverName, []));

        $config = ['label' => ['domain' => $serverName]];
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnCallback(function ($param) {
                \PHPUnit_Framework_Assert::assertEquals(['_label' => 'label'], $param);
            }));
        $this->repository->findByCustomServerName($serverName, $config);
    }
}
