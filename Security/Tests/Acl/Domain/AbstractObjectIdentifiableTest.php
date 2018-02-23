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

namespace BackBee\Security\Tests\Acl\Domain;

use Symfony\Component\Security\Acl\Util\ClassUtils;

use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;

/**
 * Test suite for class AbstractObjectIdentifiable
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Acl\Domain\AbstractObjectIdentifiable
 */
class AbstractObjectIdentifiableTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AbstractObjectIdentifiable
     */
    protected $domain;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        $this->domain = $this->getMockForAbstractClass(
            AbstractObjectIdentifiable::class,
            [],
            '',
            false,
            true,
            true,
            ['getUid']
        );

        parent::setUp();
    }

    /**
     * @covers ::getObjectIdentifier()
     */
    public function testGetObjectIdentifier()
    {
        $expected = sprintf('%s(uid)', ClassUtils::getRealClass($this->domain));
        $this->domain->expects($this->once())->method('getUid')->willReturn('uid');
        $this->assertEquals($expected, $this->domain->getObjectIdentifier());
    }

    /**
     * @covers ::getIdentifier()
     */
    public function testGetIdentifier()
    {
        $this->domain->expects($this->once())->method('getUid')->willReturn('uid');
        $this->assertEquals('uid', $this->domain->getIdentifier());
    }

    /**
     * @covers ::getType()
     */
    public function testGetType()
    {
        $expected = ClassUtils::getRealClass($this->domain);
        $this->assertEquals($expected, $this->domain->getType());
    }

    /**
     * @covers ::equals()
     */
    public function testEquals()
    {
        $clone = clone $this->domain;
        $clone->expects($this->once())->method('getUid')->willReturn('uid');
        $this->domain->expects($this->once())->method('getUid')->willReturn('uid');
        $this->assertTrue($this->domain->equals($clone));
    }

    /**
     * @covers ::equals()
     */
    public function testNotEquals()
    {
        $clone = clone $this->domain;
        $clone->expects($this->once())->method('getUid')->willReturn('uid1');
        $this->domain->expects($this->once())->method('getUid')->willReturn('uid2');
        $this->assertFalse($this->domain->equals($clone));
    }
}
