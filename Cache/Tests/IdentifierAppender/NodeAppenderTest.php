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

use BackBee\Cache\IdentifierAppender\NodeAppender;
use BackBee\Cache\Tests\Mock\MockObjectNull;
use BackBee\Cache\Tests\Mock\MockObjectParent;
use BackBee\Cache\Tests\Mock\MockObjectRoot;
use BackBee\Cache\Tests\Mock\MockObjectSelf;
use BackBee\Cache\Tests\Mock\MockRepository;
use BackBee\ClassContent\Element\Text;
use BackBee\NestedNode\Page;
use BackBee\Renderer\Renderer;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class NodeAppender.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class NodeAppenderTest extends BackBeeTestCase
{

    /**
     * @var EntityManager
     */
    private $entityMngr;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * Sets up the fixtures.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityMngr = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $this->renderer = $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObject', 'getCurrentPage', 'getCurrentRoot'])
            ->getMock();
        $this->renderer->expects($this->any())
            ->method('getCurrentRoot')
            ->willReturn(new Page('root_uid'));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::__construct()
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::getGroups()
     */
    public function testContruct()
    {
        $appender = new NodeAppender(
            $this->entityMngr,
            ['group1', 'group2']
        );

        $this->assertEquals(['group1', 'group2'], $appender->getGroups());
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier()
     */
    public function testComputeInvalidRenderer()
    {
        $appender = new NodeAppender($this->entityMngr);

        $this->assertEquals('identifier', $appender->computeIdentifier('identifier'));

        $this->renderer
            ->expects($this->any())
            ->method('getObject')
            ->willReturn(new self());

        $this->assertEquals('identifier', $appender->computeIdentifier('identifier', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier()
     */
    public function testComputeInvalidObject()
    {
        $appender = new NodeAppender($this->entityMngr);

        $this->renderer
            ->method('getObject')
            ->willReturn(new MockObjectSelf());
        $this->renderer
            ->expects($this->once())
            ->method('getCurrentPage')
            ->willReturn(null);

        $this->assertEquals('identifier', $appender->computeIdentifier('identifier', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::getClassContentCacheNodeParameter()
     */
    public function testComputeIdentifierNode()
    {
        $appender = new NodeAppender($this->entityMngr);

        $this->renderer->expects($this->any())
            ->method('getObject')
            ->willReturn(new Text());
        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository('Unknown\ClassName')));

        $this->assertEquals('id', $appender->computeIdentifier('id', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::getClassContentCacheNodeParameter()
     */
    public function testComputeIdentifierNull()
    {
        $appender = new NodeAppender($this->entityMngr);
        $this->renderer->expects($this->any())
            ->method('getObject')
            ->willReturn(new MockObjectNull());
        $this->renderer->expects($this->any())
            ->method('getCurrentPage')
            ->willReturn(new Page('page_uid'));
        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository()));

        $this->assertEquals('id', $appender->computeIdentifier('id'));
        $this->assertEquals('id-page_uid', $appender->computeIdentifier('id', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier
     */
    public function testComputeIdentifierSelf()
    {
        $appender = new NodeAppender($this->entityMngr);
        $this->renderer->expects($this->any())
            ->method('getObject')
            ->willReturn(new MockObjectSelf());
        $this->renderer->expects($this->any())
            ->method('getCurrentPage')
            ->willReturn(new Page('page_uid'));
        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository()));

        $this->assertEquals('id', $appender->computeIdentifier('id'));
        $this->assertEquals('id-page_uid', $appender->computeIdentifier('id', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier
     */
    public function testComputeIdentifierParent()
    {
        $appender = new NodeAppender($this->entityMngr);
        $this->renderer->expects($this->any())
            ->method('getObject')
            ->willReturn(new MockObjectParent());
        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository(MockObjectParent::class)));

        $this->assertEquals('id', $appender->computeIdentifier('id', $this->renderer));

        $parent = new Page('parent_uid');
        $page = new Page('page_uid');
        $page->setParent($parent);
        $this->renderer->expects($this->any())
            ->method('getCurrentPage')
            ->willReturn($page);

        $this->assertEquals('id-parent_uid', $appender->computeIdentifier('id', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::computeIdentifier
     */
    public function testComputeIdentifierRoot()
    {
        $appender = new NodeAppender($this->entityMngr);
        $this->renderer->expects($this->any())
            ->method('getObject')
            ->willReturn(new MockObjectRoot());
        $this->renderer->expects($this->any())
            ->method('getCurrentPage')
            ->willReturn(new Page('page_uid'));
        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository(MockObjectRoot::class)));

        $this->assertEquals('id-root_uid', $appender->computeIdentifier('id', $this->renderer));
    }

    /**
     * @covers BackBee\Cache\IdentifierAppender\NodeAppender::getClassContentCacheNodeParameter()
     */
    public function testGetClassContentCacheNodeParameter()
    {
        $appender = new NodeAppender($this->entityMngr);

        $this->entityMngr
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new MockRepository()));

        $this->assertEquals('self', $this->invokeMethod(
            $appender,
            'getClassContentCacheNodeParameter',
            [new MockObjectSelf()]
        ));
    }
}
