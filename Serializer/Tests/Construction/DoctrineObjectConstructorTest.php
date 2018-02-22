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

namespace BackBee\Serializer\Tests\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\VisitorInterface;
use PhpOption\Option;

use BackBee\Serializer\Construction\DoctrineObjectConstructor;

/**
 * Test suite for class DoctrineObjectConstructor
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Serializer\Construction\DoctrineObjectConstructor
 */
class DoctrineObjectConstructorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ObjectConstructorInterface
     */
    private $fallback;

    /**
     * @var DoctrineObjectConstructor
     */
    private $constructor;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockForAbstractClass(
            ManagerRegistry::class,
            [],
            '',
            false,
            false,
            true,
            ['getManagerForClass']
        );
        $this->fallback = $this->getMockForAbstractClass(
            ObjectConstructorInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['construct']
        );
        $this->constructor = new DoctrineObjectConstructor($this->registry, $this->fallback);
    }

    /**
     * @covers ::__construct()
     * @covers ::construct()
     */
    public function testConstructWithoutObjectManager()
    {
        $visitor = $this->getMockForAbstractClass(VisitorInterface::class);
        $metadata = new ClassMetadata(__CLASS__);
        $context = new DeserializationContext();

        $this->fallback->expects($this->once())->method('construct')->willReturn('ok');
        $this->assertEquals('ok', $this->constructor->construct($visitor, $metadata, '', [], $context));
    }

    /**
     * @covers ::construct()
     */
    public function testConstructTransient()
    {
        $visitor = $this->getMockForAbstractClass(VisitorInterface::class);
        $metadata = new ClassMetadata(__CLASS__);
        $context = new DeserializationContext();

        $factory = $this->getMockForAbstractClass(
            ClassMetadataFactory::class,
            [],
            '',
            false,
            false,
            true,
            ['isTransient']
        );
        $objectmanager = $this->getMockForAbstractClass(
            ObjectManager::class,
            [],
            '',
            false,
            false,
            true,
            ['getMetadataFactory']
        );

        $factory->expects($this->once())->method('isTransient')->willReturn(true);
        $objectmanager->expects($this->once())->method('getMetadataFactory')->willReturn($factory);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($objectmanager);
        $this->fallback->expects($this->once())->method('construct')->willReturn('ok');
        $this->assertEquals('ok', $this->constructor->construct($visitor, $metadata, '', [], $context));
    }

    /**
     * @covers ::construct()
     */
    public function testConstructWithManagedEntity()
    {
        $visitor = $this->getMockForAbstractClass(VisitorInterface::class);
        $metadata = new ClassMetadata(__CLASS__);
        $context = new DeserializationContext();

        $factory = $this->getMockForAbstractClass(
            ClassMetadataFactory::class,
            [],
            '',
            false,
            false,
            true,
            ['isTransient']
        );
        $objectmanager = $this->getMockForAbstractClass(
            ObjectManager::class,
            [],
            '',
            false,
            false,
            true,
            ['getMetadataFactory', 'getReference']
        );

        $factory->expects($this->once())->method('isTransient')->willReturn(false);
        $objectmanager->expects($this->once())->method('getMetadataFactory')->willReturn($factory);
        $objectmanager->expects($this->once())->method('getReference')->willReturn('ok');
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($objectmanager);

        $this->assertEquals('ok', $this->constructor->construct($visitor, $metadata, '', [], $context));
    }

    /**
     * @covers ::construct()
     */
    public function testConstructUpdatedEntity()
    {
        $visitor = $this->getMockForAbstractClass(VisitorInterface::class);
        $metadata = new ClassMetadata(__CLASS__);
        $context = new DeserializationContext();

        $factory = $this->getMockForAbstractClass(
            ClassMetadataFactory::class,
            [],
            '',
            false,
            false,
            true,
            ['isTransient']
        );
        $objectmanager = $this->getMockForAbstractClass(
            ObjectManager::class,
            [],
            '',
            false,
            false,
            true,
            ['getMetadataFactory', 'getClassMetadata', 'initializeObject', 'find']
        );
        $classmetadata = $this->getMockForAbstractClass(
            DoctrineMetadata::class,
            [],
            '',
            false,
            false,
            true,
            ['getIdentifierFieldNames', 'getIdentifierValues']
        );
        $option = $this->getMockForAbstractClass(Option::class, [], '', false, false, true, ['get']);
        $context->attributes->set('target', $option);

        $factory->expects($this->once())->method('isTransient')->willReturn(false);
        $objectmanager->expects($this->once())->method('getMetadataFactory')->willReturn($factory);
        $objectmanager->expects($this->once())->method('getClassMetadata')->willReturn($classmetadata);
        $objectmanager->expects($this->once())->method('find')->willReturn('ok');
        $classmetadata->expects($this->once())->method('getIdentifierFieldNames')->willReturn(['name']);
        $classmetadata->expects($this->once())->method('getIdentifierValues')->willReturn(['name' => 'name']);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($objectmanager);

        $this->assertEquals('ok', $this->constructor->construct($visitor, $metadata, [], [], $context));
    }

    /**
     * @covers ::construct()
     */
    public function testConstruct()
    {
        $visitor = $this->getMockForAbstractClass(VisitorInterface::class);
        $metadata = new ClassMetadata(__CLASS__);
        $context = new DeserializationContext();

        $factory = $this->getMockForAbstractClass(
            ClassMetadataFactory::class,
            [],
            '',
            false,
            false,
            true,
            ['isTransient']
        );
        $objectmanager = $this->getMockForAbstractClass(
            ObjectManager::class,
            [],
            '',
            false,
            false,
            true,
            ['getMetadataFactory', 'getClassMetadata', 'initializeObject']
        );
        $classmetadata = $this->getMockForAbstractClass(
            DoctrineMetadata::class,
            [],
            '',
            false,
            false,
            true,
            ['getIdentifierFieldNames', 'getIdentifierValues']
        );
        $option = $this->getMockForAbstractClass(Option::class, [], '', false, false, true, ['get']);
        $context->attributes->set('target', $option);

        $factory->expects($this->once())->method('isTransient')->willReturn(false);
        $objectmanager->expects($this->once())->method('getMetadataFactory')->willReturn($factory);
        $objectmanager->expects($this->once())->method('getClassMetadata')->willReturn($classmetadata);
        $classmetadata->expects($this->once())->method('getIdentifierFieldNames')->willReturn([['name']]);
        $classmetadata->expects($this->once())->method('getIdentifierValues')->willReturn([]);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($objectmanager);

        $this->assertEquals($option, $this->constructor->construct($visitor, $metadata, [], [], $context));
    }
}
