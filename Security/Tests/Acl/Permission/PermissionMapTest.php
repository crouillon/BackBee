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
use BackBee\Security\Acl\Permission\PermissionMap;

/**
 * Test suite for class PermissionMap
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Acl\Permission\PermissionMap
 */
class PermissionMapTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct()
     */
    public function test()
    {
        $map = new PermissionMap();

        $this->assertNull($map->getMasks('unknown', null));

        $expectedCommit = [
            MaskBuilder::MASK_COMMIT,
            MaskBuilder::MASK_OPERATOR,
            MaskBuilder::MASK_MASTER,
            MaskBuilder::MASK_OWNER,
        ];
        $this->assertEquals($expectedCommit, $map->getMasks(PermissionMap::PERMISSION_COMMIT, null));

        $expectedPublish = [
            MaskBuilder::MASK_PUBLISH,
            MaskBuilder::MASK_MASTER,
        ];
        $this->assertEquals($expectedPublish, $map->getMasks(PermissionMap::PERMISSION_PUBLISH, null));
    }
}
