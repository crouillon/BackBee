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

use org\bovigo\vfs\vfsStream;

use BackBee\ApplicationInterface;
use BackBee\Util\Resolver\BundleConfigDirectory;

/**
 * Tests suite for class BundleConfigDirectory
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Resolver\BundleConfigDirectory
 */
class BundleConfigDirectoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getDirectories()
     */
    public function testGetDirectories()
    {
        vfsStream::setup(
            'root',
            0777,
            ['Config' => [
                'bundle' => ['bundleid' => []],
                'env' => ['bundle' => ['bundleid' => []]]
            ]]
        );

        $this->assertEquals(
            [
                implode(DIRECTORY_SEPARATOR, [vfsStream::url('root'), 'Config', 'bundle', 'bundleid']),
                implode(DIRECTORY_SEPARATOR, [vfsStream::url('root'), 'Config', 'env', 'bundle', 'bundleid']),
            ],
            BundleConfigDirectory::getDirectories(
                vfsStream::url('root'),
                ApplicationInterface::DEFAULT_CONTEXT,
                'env',
                'bundleid'
            )
        );
    }
}
