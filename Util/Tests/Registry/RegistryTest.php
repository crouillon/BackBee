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

namespace BackBee\Util\Tests\Registry;

use Doctrine\ORM\Tools\SchemaTool;

use BackBee\Tests\Traits\CreateEntityManagerTrait;
use BackBee\Util\Registry\Registry;

/**
 * Test suite for class Registry.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Registry\Registry
 */
class RegistryTest extends \PHPUnit_FrameWork_TestCase
{
    use CreateEntityManagerTrait;

    /**
     * @var Registry
     */
    private $entity;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new Registry();
    }

    /**
     * @covers ::getId()
     */
    public function testEntity()
    {
        $entityMng = $this->createEntityManager();
        $schemaTool = new SchemaTool($entityMng);
        $sql = $schemaTool->getCreateSchemaSql([
            $entityMng->getClassMetadata(Registry::class)
        ]);

        $expected = [
            'CREATE TABLE registry (id INTEGER NOT NULL, ' .
                '"type" VARCHAR(255) DEFAULT NULL, ' .
                '"key" VARCHAR(255) DEFAULT NULL, ' .
                '"value" CLOB DEFAULT NULL, ' .
                '"scope" VARCHAR(255) DEFAULT NULL, ' .
            'PRIMARY KEY(id))',
            'CREATE INDEX IDX_TYPE ON registry (type)',
            'CREATE INDEX IDX_SCOPE ON registry (scope)',
            'CREATE INDEX IDX_KEY ON registry ("key")'
        ];

        $this->assertEquals($expected, $sql);

        $schemaTool->createSchema([
            $entityMng->getClassMetadata(Registry::class)
        ]);

        $registry = new Registry();
        $entityMng->persist($registry);
        $entityMng->flush();

        $this->assertEquals(1, $registry->getId());
    }

    /**
     * @covers ::setType()
     * @covers ::getType()
     */
    public function testType()
    {
        $this->assertEquals($this->entity, $this->entity->setType('type'));
        $this->assertEquals('type', $this->entity->getType());
    }

    /**
     * @covers ::setKey()
     * @covers ::getKey()
     */
    public function testKey()
    {
        $this->assertEquals($this->entity, $this->entity->setKey('key'));
        $this->assertEquals('key', $this->entity->getKey());
    }

    /**
     * @covers ::setValue()
     * @covers ::getValue()
     */
    public function testValue()
    {
        $this->assertEquals($this->entity, $this->entity->setValue('value'));
        $this->assertEquals('value', $this->entity->getValue());
    }

    /**
     * @covers ::setScope()
     * @covers ::getScope()
     */
    public function testScope()
    {
        $this->assertEquals($this->entity, $this->entity->setScope('scope'));
        $this->assertEquals('scope', $this->entity->getScope());
    }
}
