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

namespace BackBee\Util;

use Doctrine\ORM\EntityManager;

use BackBee\BBApplication;
use BackBee\Util\ObjectIdentityRetrieval;

/**
 * Test suite for class ObjectIdentityRetrieval
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\ObjectIdentityRetrieval
 */
class ObjectIdentityRetrievalTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityManager
     */
    private $entityMng;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        \PHPUnit_Framework_Error_Deprecated::$enabled = false;
        $this->entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();
    }

    /**
     * @covers ::__construct()
     * @covers ::build()
     * @covers ::getObject()
     */
    public function testOldDefinition()
    {
        $application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMock();
        $application->expects($this->any())->method('getEntityManager')->willReturn($this->entityMng);

        $retrieval = ObjectIdentityRetrieval::build($application, '');
        $this->assertNull($retrieval->getObject());
    }

    /**
     * @covers            ::build()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidCall()
    {
        ObjectIdentityRetrieval::build(new \stdClass(), '');
    }

    /**
     * @covers ::__construct()
     * @covers ::build()
     * @covers ::getObject()
     */
    public function testPattern1()
    {
        $this->entityMng
            ->expects($this->once())
            ->method('find')
            ->with('namespace\classname', 'uid')
            ->willReturn('result');

        $retrieval = ObjectIdentityRetrieval::build($this->entityMng, '(uid, namespace\classname)');
        $this->assertEquals('result', $retrieval->getObject());
    }

    /**
     * @covers ::__construct()
     * @covers ::build()
     * @covers ::getObject()
     */
    public function testPattern2()
    {
        $this->entityMng
            ->expects($this->once())
            ->method('find')
            ->with('namespace\classname', 'uid')
            ->willReturn('result');

        $retrieval = ObjectIdentityRetrieval::build($this->entityMng, 'namespace\classname(uid)');
        $this->assertEquals('result', $retrieval->getObject());
    }

    /**
     * @covers ::__construct()
     * @covers ::build()
     * @covers ::getObject()
     */
    public function testUnrecognizedObject()
    {
        $retrieval = ObjectIdentityRetrieval::build($this->entityMng, '');
        $this->assertNull($retrieval->getObject());
    }
}
