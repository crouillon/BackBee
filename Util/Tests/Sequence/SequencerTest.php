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

namespace BackBee\Util\Tests\Sequence;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Util\Sequence\Entity\Sequence;
use BackBee\Util\Sequence\Sequencer;

/**
 * Tests suite for class Sequence.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Sequence\Sequencer
 */
class SequenceTest extends \PHPUnit_Framework_TestCase
{
    use InvokeMethodTrait;

    /**
     * @var Sequencer
     */
    private $sequencer;

    /**
     * @var Connection
     */
    private $connection;

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

        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeUpdate'])
            ->getMock();

        $this->entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['find', 'refresh', 'getConnection'])
            ->getMock();

        $metadata = new ClassMetadata(Sequence::class);
        $metadata->table = ['name' => 'sequence'];
        $metadata->fieldMappings = [
            'name' => ['columnName' => 'name'],
            'value' => ['columnName' => 'value']
        ];

        $this->sequencer = new Sequencer($this->entityMng, $metadata);
    }

    /**
     * @covers ::__construct()
     * @covers ::getValue()
     */
    public function testGetValue()
    {
        $sequence = $this->getMockBuilder(Sequence::class)->setMethods(['getValue'])->getMock();
        $sequence->expects($this->once())->method('getValue')->willReturn(10);
        $this->entityMng->expects($this->any())->method('find')->willReturn($sequence);
        $this->entityMng->expects($this->once())->method('refresh')->with($sequence);
        $this->entityMng->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->assertEquals(11, $this->sequencer->getValue('name', 11));
    }

    /**
     * @covers ::init()
     */
    public function testInit()
    {
        $this->connection
            ->expects($this->once())
            ->method('executeUpdate')
            ->with(
                'INSERT INTO sequence (name, value) VALUE(:name, :value)',
                [
                    'name'   => 'name',
                    'value'  => 0,
                ]
            );

        $this->entityMng->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->assertEquals(0, $this->invokeMethod($this->sequencer, 'init', ['name']));
    }

    /**
     * @covers                   ::init()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Sequence with name name already exists
     */
    public function testInitAlreadyExist()
    {
        $this->entityMng->expects($this->once())->method('find')->willReturn(1);
        $this->invokeMethod($this->sequencer, 'init', ['name']);
    }

    /**
     * @covers                   ::init()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Initial value of a sequence must be a positive integer
     */
    public function testInitNotInteger()
    {
        $this->invokeMethod($this->sequencer, 'init', ['name', -1]);
    }

    /**
     * @covers ::update()
     */
    public function testUpdate()
    {
        $this->connection
            ->expects($this->once())
            ->method('executeUpdate')
            ->with(
                'UPDATE sequence SET value = :value WHERE name = :name',
                [
                    'name'   => 'name',
                    'value'  => 0,
                ]
            );

        $this->entityMng->expects($this->once())->method('find')->willReturn(1);
        $this->entityMng->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->assertEquals(0, $this->invokeMethod($this->sequencer, 'update', ['name']));
    }

    /**
     * @covers                   ::update()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unknown sequence with name name
     */
    public function testUpdateDontExist()
    {
        $this->invokeMethod($this->sequencer, 'update', ['name']);
    }

    /**
     * @covers                   ::update()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Initial value of a sequence must be a positive integer
     */
    public function testUpdateNotInteger()
    {
        $this->entityMng->expects($this->once())->method('find')->willReturn(1);
        $this->invokeMethod($this->sequencer, 'update', ['name', -1]);
    }

    /**
     * @covers ::read()
     */
    public function testReadDontExist()
    {
        $this->entityMng->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->assertEquals(10, $this->invokeMethod($this->sequencer, 'read', ['name', 10]));
    }

    /**
     * @covers ::read()
     */
    public function testReadExist()
    {
        $sequence = $this->getMockBuilder(Sequence::class)->setMethods(['getValue'])->getMock();
        $sequence->expects($this->once())->method('getValue')->willReturn(10);
        $this->entityMng->expects($this->once())->method('find')->willReturn($sequence);
        $this->entityMng->expects($this->once())->method('refresh')->with($sequence);
        $this->assertEquals(10, $this->invokeMethod($this->sequencer, 'read', ['name', 10]));
    }

    /**
     * @covers ::increaseTo()
     */
    public function testIncreaseToMinus()
    {
        $sequence = $this->getMockBuilder(Sequence::class)->setMethods(['getValue'])->getMock();
        $sequence->expects($this->once())->method('getValue')->willReturn(10);
        $this->entityMng->expects($this->once())->method('find')->willReturn($sequence);
        $this->entityMng->expects($this->once())->method('refresh')->with($sequence);
        $this->assertEquals(10, $this->sequencer->increaseTo('name', 9));
    }

    /**
     * @covers ::increaseTo()
     */
    public function testIncreaseTo()
    {
        $sequence = $this->getMockBuilder(Sequence::class)->setMethods(['getValue'])->getMock();
        $sequence->expects($this->once())->method('getValue')->willReturn(10);
        $this->entityMng->expects($this->any())->method('find')->willReturn($sequence);
        $this->entityMng->expects($this->once())->method('refresh')->with($sequence);
        $this->entityMng->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->assertEquals(11, $this->sequencer->increaseTo('name', 11));
    }

    /**
     * @covers                   ::increaseTo()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Value of a sequence must be a positive integer
     */
    public function testIncreaseToNotInteger()
    {
        $this->sequencer->increaseTo('name', -1);
    }
}
