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

namespace BackBee\Util\Tests\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Element\Text;
use BackBee\ClassContent\Revision;
use BackBee\NestedNode\Page;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Util\Doctrine\ScheduledEntities;

/**
 * Tests suite for class ScheduledEntities.
 *
 * @author Charles Rouillon <charles.rouilon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Doctrine\ScheduledEntities
 */
class ScheduledEntititesTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getScheduledEntityInsertionsByClassname()
     */
    public function testGetScheduledEntityInsertionsByClassname()
    {
        $site = new Site();
        $entities = [new Page(), $site, new Layout()];

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($entities);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$site],
            ScheduledEntities::getScheduledEntityInsertionsByClassname(
                $entityMng,
                Site::class
            )
        );
    }

    /**
     * @covers ::getScheduledEntityUpdatesByClassname()
     */
    public function testGetScheduledEntityUpdatesByClassname()
    {
        $site = new Site();
        $entities = [new Page(), $site, new Layout()];

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($entities);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$site],
            ScheduledEntities::getScheduledEntityUpdatesByClassname(
                $entityMng,
                Site::class
            )
        );
    }

    /**
     * @covers ::getScheduledEntityDeletionsByClassname()
     */
    public function testGetScheduledEntityeletionsByClassname()
    {
        $site = new Site();
        $entities = [new Page(), $site, new Layout()];

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($entities);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$site],
            ScheduledEntities::getScheduledEntityDeletionsByClassname(
                $entityMng,
                Site::class
            )
        );
    }

    /**
     * @covers ::getScheduledEntityByClassname()
     */
    public function testGetScheduledEntityByClassname()
    {
        $site = new Site();
        $layout = new Layout();
        $page = new Page();

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$site]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$layout]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$page]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$site, $layout, $page],
            ScheduledEntities::getScheduledEntityByClassname(
                $entityMng,
                [Site::class, Layout::class, Page::class]
            )
        );
    }

    /**
     * @covers ::getScheduledAClassContentInsertions()
     * @covers ::getScheduledEntity()
     */
    public function testGetScheduledAClassContentInsertions()
    {
        $content = new Text();
        $contentset = new ContentSet();
        $revision = new Revision();
        $revision->setContent($content);

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->at(0))
            ->method('getScheduledEntityInsertions')
            ->willReturn([$content]);
        $unitOfWork->expects($this->at(1))
            ->method('getScheduledEntityInsertions')
            ->willReturn([$content]);
        $unitOfWork->expects($this->at(2))
            ->method('getScheduledEntityInsertions')
            ->willReturn([$contentset]);
        $unitOfWork->expects($this->at(3))
            ->method('getScheduledEntityInsertions')
            ->willReturn([$revision]);
        $unitOfWork->expects($this->at(4))
            ->method('getScheduledEntityInsertions')
            ->willReturn([$revision]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$content],
            ScheduledEntities::getScheduledAClassContentInsertions($entityMng, false, false)
        );

        $this->assertEquals(
            [],
            ScheduledEntities::getScheduledAClassContentInsertions($entityMng, false, true)
        );

        $this->assertEquals(
            [$contentset],
            ScheduledEntities::getScheduledAClassContentInsertions($entityMng, false, true)
        );

        $this->assertEquals(
            [],
            ScheduledEntities::getScheduledAClassContentInsertions($entityMng, false, false)
        );

        $this->assertEquals(
            [$content],
            ScheduledEntities::getScheduledAClassContentInsertions($entityMng, true, false)
        );
    }

    /**
     * @covers ::getScheduledAClassContentUpdates()
     */
    public function testGetScheduledAClassContentUpdates()
    {
        $content = new Text();

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$content]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$content],
            ScheduledEntities::getScheduledAClassContentUpdates($entityMng, false, false)
        );
    }

    /**
     * @covers ::getSchedulesAClassContentDeletions()
     */
    public function testGetSchedulesAClassContentDeletions()
    {
        $content = new Text();

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->at(0))
            ->method('getScheduledEntityDeletions')
            ->willReturn([$content]);
        $unitOfWork->expects($this->at(1))
            ->method('getScheduledEntityDeletions')
            ->willReturn([$content]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$content],
            ScheduledEntities::getSchedulesAClassContentDeletions($entityMng, false)
        );
        $this->assertEquals(
            [],
            ScheduledEntities::getSchedulesAClassContentDeletions($entityMng, true)
        );
    }

    /**
     * @covers ::getScheduledAClassContentNotForDeletions()
     */
    public function testGetScheduledAClassContentNotForDeletions()
    {
        $insert = new Text();
        $update = new Text();

        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$insert]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$update]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            [$insert, $update],
            ScheduledEntities::getScheduledAClassContentNotForDeletions($entityMng)
        );
    }

    /**
     * @covers ::hasScheduledEntitiesNotForDeletions()
     */
    public function testHasScheduledEntitiesNotForDeletions()
    {
        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Site()]);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new Layout()]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertTrue(ScheduledEntities::hasScheduledEntitiesNotForDeletions($entityMng, Site::class));
        $this->assertTrue(ScheduledEntities::hasScheduledEntitiesNotForDeletions($entityMng, Layout::class));
        $this->assertFalse(ScheduledEntities::hasScheduledEntitiesNotForDeletions($entityMng, Page::class));
    }

    /**
     * @covers ::hasScheduledPageNotForDeletions()
     */
    public function testHasScheduledPageNotForDeletions()
    {
        $unitOfWork = $this->getMockedUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Page()]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $entityMng = $this->getMockedEntityManager();
        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertTrue(ScheduledEntities::hasScheduledPageNotForDeletions($entityMng));
    }

    /**
     * @return EntityManager
     */
    private function getMockedEntityManager()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnitOfWork'])
            ->getMock();
    }

    /**
     * @return UnitOfWork
     */
    private function getMockedUnitOfWork()
    {
        return $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getScheduledEntityInsertions',
                'getScheduledEntityUpdates',
                'getScheduledEntityDeletions',
            ])
            ->getMock();
    }
}
