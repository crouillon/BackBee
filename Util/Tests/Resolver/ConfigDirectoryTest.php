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

namespace BackBee\Util\Tests\Resolver;

use BackBee\Util\Resolver\ConfigDirectory;

/**
 * Tests suite for class ConfigDirectory
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Resolver\ConfigDirectory
 */
class ConfigDirectoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getDirectories()
     */
    public function testGetDirectories()
    {

        $this->assertEquals(
            [
                implode(DIRECTORY_SEPARATOR, ['bbdir', 'Config']),
                implode(DIRECTORY_SEPARATOR, ['basedir', 'Config']),
                implode(DIRECTORY_SEPARATOR, ['basedir', 'Config', 'env']),
                implode(DIRECTORY_SEPARATOR, ['basedir', 'context', 'Config']),
                implode(DIRECTORY_SEPARATOR, ['basedir', 'context', 'Config', 'env']),
            ],
            ConfigDirectory::getDirectories(
                'bbdir',
                'basedir',
                'context',
                'env'
            )
        );
    }
}
