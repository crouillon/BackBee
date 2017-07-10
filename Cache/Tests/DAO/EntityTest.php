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

namespace BackBee\Cache\Tests\DAO;

use BackBee\Cache\DAO\Entity;

/**
 * Tests suite for class DAO\Entity.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers BackBee\Cache\DAO\Entity::__construct()
     * @covers BackBee\Cache\DAO\Entity::getId()
     * @covers BackBee\Cache\DAO\Entity::setUid()
     */
    public function testId()
    {
        $entity = new Entity('id');

        $this->assertEquals('id', $entity->getId());
        $this->assertEquals($entity, $entity->setUid('uid'));
        $this->assertEquals('uid', $entity->getId());
    }

    /**
     * @covers BackBee\Cache\DAO\Entity::getData()
     * @covers BackBee\Cache\DAO\Entity::setData()
     */
    public function testData()
    {
        $entity = new Entity();

        $this->assertNull($entity->getData());
        $this->assertEquals($entity, $entity->setData('data'));
        $this->assertEquals('data', $entity->getData());
    }

    /**
     * @covers BackBee\Cache\DAO\Entity::getExpire()
     * @covers BackBee\Cache\DAO\Entity::setExpire()
     */
    public function testExpire()
    {
        $now = new \DateTime();
        $entity = new Entity();

        $this->assertNull($entity->getExpire());
        $this->assertEquals($entity, $entity->setExpire($now));
        $this->assertEquals($now, $entity->getExpire());
    }

    /**
     * @covers BackBee\Cache\DAO\Entity::getTag()
     * @covers BackBee\Cache\DAO\Entity::setTag()
     */
    public function testTag()
    {
        $entity = new Entity();

        $this->assertNull($entity->getTag());
        $this->assertEquals($entity, $entity->setTag('tag'));
        $this->assertEquals('tag', $entity->getTag());
    }
}
