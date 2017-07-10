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

namespace BackBee\Cache\Tests;

use BackBee\Cache\CacheIdentifierGenerator;
use BackBee\DependencyInjection\Container;
use BackBee\Tests\BackBeeTestCase;

/**
 * Tests suite for class CacheIdentifierGenerator.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheIdentifierGeneratorTest extends BackBeeTestCase
{

    /**
     * @var CacheIdentifierGenerator
     */
    private $generator;

    /**
     * Sets up the fixtures.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->generator = new CacheIdentifierGenerator(new Container());
    }

    /**
     * @covers BackBee\Cache\CacheIdentifierGenerator::__construct()
     */
    public function testConstruct()
    {
        $container = $this->getMock(Container::class);

        $container->expects($this->once())
                ->method('findTaggedServiceIds')
                ->will($this->returnValue(['id']));

        $container->expects($this->once())
                ->method('get')
                ->will($this->returnValue(new Mock\MockAppenderValidator()));

        new CacheIdentifierGenerator($container);
    }

    /**
     * @covers BackBee\Cache\CacheIdentifierGenerator::addAppender()
     * @covers BackBee\Cache\CacheIdentifierGenerator::isValidGroup()
     */
    public function testAddAppender()
    {
        $appender = new Mock\MockAppenderValidator();
        $this->generator->addAppender($appender);

        $this->assertTrue($this->generator->isValidGroup('group1'));
        $this->assertTrue($this->generator->isValidGroup('group2'));
        $this->assertFalse($this->generator->isValidGroup('group'));
    }

    /**
     * @covers            BackBee\Cache\CacheIdentifierGenerator::compute()
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testComputeInvalidGroup()
    {
        $this->generator->compute('group', 'identifier');
    }

    /**
     * @covers BackBee\Cache\CacheIdentifierGenerator::compute()
     */
    public function testCompute()
    {
        $appender = new Mock\MockAppenderValidator();
        $this->generator->addAppender($appender);

        $this->assertEquals(
            'computed-identifier',
            $this->generator->compute('group1', 'identifier')
        );
    }
}
