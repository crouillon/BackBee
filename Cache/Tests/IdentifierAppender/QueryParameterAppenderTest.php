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

namespace BackBee\Cache\Tests\IdentifierAppender;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

use BackBee\Cache\IdentifierAppender\QueryParameterAppender;
use BackBee\Cache\Tests\Mock\MockObjectSelf;
use BackBee\Cache\Tests\Mock\MockRepository;
use BackBee\Renderer\Renderer;

/**
 * Tests suite for class QueryParameterAppender
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class QueryParameterAppenderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityManager
     */
    private $entityMngr;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityMngr = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();
        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository()));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\QueryParameterAppender::__construct()
     * @covers BackBee\Cache\IdentifierAppender\QueryParameterAppender::getGroups()
     */
    public function testContruct()
    {
        $appender = new QueryParameterAppender(
            new Request(),
            $this->entityMngr,
            QueryParameterAppender::NO_PARAMS_STRATEGY,
            ['group1', 'group2']
        );

        $this->assertEquals(['group1', 'group2'], $appender->getGroups());
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\QueryParameterAppender::computeIdentifier()
     */
    public function testComputeIdentifier()
    {
        $noParamStratgy = new QueryParameterAppender(
            new Request(),
            $this->entityMngr
        );
        $this->assertEquals('identifier', $noParamStratgy->computeIdentifier('identifier'));

        $unknownStrategy = new QueryParameterAppender(
            new Request(),
            $this->entityMngr,
            -1
        );
        $this->assertEquals('identifier', $unknownStrategy->computeIdentifier('identifier'));

        $allParamsStrategy = new QueryParameterAppender(
            new Request(['param1' => 'value1', 'param2' => 'value2']),
            $this->entityMngr,
            QueryParameterAppender::ALL_PARAMS_STRATEGY
        );
        $this->assertEquals(
            'identifier-param1=value1-param2=value2',
            $allParamsStrategy->computeIdentifier('identifier')
        );

        $contentParamsWithoutRenderer = new QueryParameterAppender(
            new Request(),
            $this->entityMngr,
            QueryParameterAppender::CLASSCONTENT_PARAMS_STRATEGY
        );
        $this->assertEquals(
            'identifier',
            $contentParamsWithoutRenderer->computeIdentifier('identifier')
        );
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\QueryParameterAppender::computeIdentifier()
     * @covers BackBee\Cache\IdentifierAppender\QueryParameterAppender::getClassContentCacheQueryParameters()
     */
    public function testComputeClassContentIdentifier()
    {
        $appender = new QueryParameterAppender(
            new Request(['param1' => 'value1', 'param2' => 'value2']),
            $this->entityMngr,
            QueryParameterAppender::CLASSCONTENT_PARAMS_STRATEGY
        );

        $renderer = $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();
        $renderer->expects($this->exactly(2))
            ->method('getObject')
            ->willReturn(new MockObjectSelf());

        $this->assertEquals(
            'identifier-param1=value1',
            $appender->computeIdentifier('identifier', $renderer)
        );
    }
}
