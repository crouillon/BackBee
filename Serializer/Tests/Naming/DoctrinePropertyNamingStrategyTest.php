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

namespace BackBee\Serializer\Tests\Naming;

use Doctrine\Common\Persistence\ObjectManager;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;

use BackBee\Doctrine\Registry;
use BackBee\Serializer\Naming\DoctrinePropertyNamingStrategy;

/**
 * Test suite for class DoctrinePropertyNamingStrategy
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Serializer\Naming\DoctrinePropertyNamingStrategy
 */
class DoctrinePropertyNamingStrategyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $delegate;

    /**
     * @var DoctrinePropertyNamingStrategy
     */
    private $strategy;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass'])
            ->getMock();

        $this->delegate = $this->getMockForAbstractClass(
            PropertyNamingStrategyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['translateName']
        );

        $this->strategy = new DoctrinePropertyNamingStrategy($this->registry, $this->delegate);
    }

    /**
     * @covers ::__construct()
     * @covers ::translateName()
     * @covers ::getDoctrineMetadata()
     */
    public function test()
    {
        $metadata = new \stdClass();
        $metadata->columnNames = ['strategy' => 'return strategy'];

        $objectmanager = $this->getMockForAbstractClass(
            ObjectManager::class,
            [],
            '',
            false,
            false,
            true,
            ['getClassMetadata']
        );

        $objectmanager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($objectmanager);
        $this->delegate->expects($this->once())->method('translateName')->willReturn('return delegate');

        $this->assertEquals(
            'return strategy',
            $this->strategy->translateName(new PropertyMetadata(__CLASS__, 'strategy'))
        );
        $this->assertEquals(
            'return delegate',
            $this->strategy->translateName(new PropertyMetadata(__CLASS__, 'delegate'))
        );
    }
}
