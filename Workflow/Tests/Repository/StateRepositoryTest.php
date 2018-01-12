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

namespace BackBee\Workflow\Tests\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use BackBee\Site\Layout;
use BackBee\Workflow\Repository\StateRepository;
use BackBee\Workflow\State;

/**
 * Test suite for class StateRepository.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Workflow\Repository\StateRepository
 */
class StateRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityManager
     */
    private $entityMng;

    /**
     * @var StateRepository
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

        $this->repository = $this->getMockBuilder(StateRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'createQueryBuilder'])
            ->getMock();
    }

    /**
     * @covers ::getWorkflowStatesForLayout()
     */
    public function testGetWorkflowStatesForLayout()
    {
        $layout = new Layout();
        $state1 = new State('state1', ['code' => 1]);
        $state2 = new State('state2', ['code' => 2]);

        $this->repository
            ->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$state2]);
        $this->repository
            ->expects($this->at(1))
            ->method('findBy')
            ->willReturn([$state1]);

        $this->assertEquals(
            [1 => $state1, 2 => $state2],
            $this->repository->getWorkflowStatesForLayout($layout)
        );
    }

    /**
     * @covers ::getWorkflowStatesWithLayout()
     */
    public function testGetWorkflowStatesWithLayout()
    {
        $query = $this->getMock(\stdClass::class, ['getResult']);
        $builder = $this->getMock(QueryBuilder::class, ['andWhere', 'getQuery'], [$this->entityMng]);

        $builder->expects($this->once())->method('andWhere')->with('w._layout IS NOT NULL')->willReturn($builder);
        $builder->expects($this->once())->method('getQuery')->willReturn($query);
        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($builder);

        $this->repository->getWorkflowStatesWithLayout();
    }
}
