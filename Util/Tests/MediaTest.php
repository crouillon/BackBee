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

namespace BackBee\Util\Tests;

use org\bovigo\vfs\vfsStream;
use phpmock\phpunit\PHPMock;

use BackBee\ClassContent\Element\File;
use BackBee\ClassContent\Revision;
use BackBee\Util\Media;

/**
 * Test suite for class Media
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Media
 */
class MediaTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    /**
     * @covers ::getPathFromContent()
     */
    public function testGetPathFromContent()
    {
        $file = new File();
        $file->originalname = 'original.name';

        $revision = new Revision('long_enough_uid');
        $revision->setData($file->getDataToObject());
        $file->setDraft($revision);

        $this->assertEquals(
            'long/_enough_uid.name',
            Media::getPathFromContent($file, 4)
        );
    }

    /**
     * @covers            ::getPathFromContent()
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testGetPathFromInvalidContent()
    {
        $file = new File();

        Media::getPathFromContent($file);
    }

    /**
     * @covers ::getPathFromUid()
     */
    public function testGetPathFromUid()
    {
        $uid = 'long_enough_uid';
        $origin = 'original.name';

        $this->assertEquals(
            'long' . DIRECTORY_SEPARATOR . '_enough_uid.name',
            Media::getPathFromUid($uid, $origin, 4)
        );

        $this->assertEquals(
            'long' . DIRECTORY_SEPARATOR . '_enough_uid' . DIRECTORY_SEPARATOR . $origin,
            Media::getPathFromUid($uid, $origin, 4, true)
        );
    }

    /**
     * @covers            ::getPathFromUid()
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testGetPathFromInvalidUid()
    {
        Media::getPathFromUid('', '');
    }

    /**
     * @covers ::resize()
     */
    public function testResize()
    {
        if (!(extension_loaded('gd'))) {
            $this->markTestSkipped('The gd extension is not available.');
        }

        vfsStream::setup('root', 0777);

        $this->assertTrue(Media::resize(__DIR__ . '/resources/test.png', vfsStream::url('root/resized10.png'), 10, 10));
        $this->assertTrue(Media::resize(
            __DIR__ . '/resources/test.png',
            vfsStream::url('root/resized1000.png'),
            1000,
            1000
        ));

        $size10 = getimagesize(vfsStream::url('root/resized10.png'));
        $this->assertEquals('width="9" height="10"', $size10[3]);

        $size1000 = getimagesize(vfsStream::url('root/resized1000.png'));
        $this->assertEquals('width="102" height="108"', $size1000[3]);

        $this->assertTrue(Media::resize(__DIR__ . '/resources/test.jpg', vfsStream::url('root/resized.jpg'), 10, 10));
        $this->assertTrue(Media::resize(__DIR__ . '/resources/test.gif', vfsStream::url('root/resized.gif'), 10, 10));
    }

    /**
     * @covers            ::resize()
     * @expectedException BackBee\Exception\BBException
     */
    public function testResizeWithoutGd()
    {
        $loaded = $this->getFunctionMock('BackBee\Util', 'extension_loaded');
        $loaded->expects($this->any())->willReturn(false);

        vfsStream::setup('root', 0777);
        Media::resize(vfsStream::url('root/unknown.gif'), vfsStream::url('root/resized.gif'), 10, 10);
    }

    /**
     * @covers            ::resize()
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testResizeUnknownFile()
    {
        if (!(extension_loaded('gd'))) {
            $this->markTestSkipped('The gd extension is not available.');
        }

        vfsStream::setup('root', 0777);
        Media::resize(vfsStream::url('root/unknown.gif'), vfsStream::url('root/resized.gif'), 10, 10);
    }

    /**
     * @covers            ::resize()
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testResizeInvalidFile()
    {
        if (!(extension_loaded('gd'))) {
            $this->markTestSkipped('The gd extension is not available.');
        }

        vfsStream::setup('root', 0777);
        Media::resize(__FILE__, vfsStream::url('root/resized.gif'), 10, 10);
    }

    /**
     * @covers            ::resize()
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testResizeInvalidType()
    {
        if (!(extension_loaded('gd'))) {
            $this->markTestSkipped('The gd extension is not available.');
        }

        vfsStream::setup('root', 0777);
        Media::resize(__DIR__ . '/resources/test.bmp', vfsStream::url('root/resized.bmp'), 10, 10);
    }
}
