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

namespace BackBee\Security\Tests\Acl\Permission;

use BackBee\Security\Acl\Permission\MaskBuilder;

/**
 * Test suite for class MaskBuilder
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Acl\Permission\MaskBuilder
 */
class MaskBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function test()
    {
        $this->assertEquals(256, MaskBuilder::MASK_COMMIT);
        $this->assertEquals(512, MaskBuilder::MASK_PUBLISH);
        $this->assertEquals('S', MaskBuilder::CODE_COMMIT);
        $this->assertEquals('P', MaskBuilder::CODE_PUBLISH);
    }
}
