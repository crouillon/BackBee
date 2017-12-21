<?php

/*
 * Copyright (c) 2017 Lp digital system
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
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\ClassContent\Tests\Traits;

use BackBee\ClassContent\Element\File;
use BackBee\ClassContent\Element\Image;
use BackBee\ClassContent\Element\Keyword;
use BackBee\Tests\BackBeeTestCase;

/**
 * Class FileMediaTraitTest
 *
 * @category    BackBee
 * @copyright   ©2017 - Lp digital system
 * @author      Djoudi Bensid <djoudi.bensid@lp-digital.fr>
 */
class FileMediaTraitTest extends BackBeeTestCase
{
    /**
     * Test mime type supported for file element.
     */
    public function testMimeTypeSupportedFile()
    {
        $file = new File();
        $this->assertNotNull($file->getProperty('mime-types-supported'));
        $this->assertTrue($file->mimeTypeSupported('image/pdf'));
    }

    /**
     * Test mime type supported for image element.
     */
    public function testMimeTypeSupportedImage()
    {
        $image = new Image();
        $this->assertInstanceOf('\BackBee\ClassContent\Element\File', $image);
        $this->assertNotNull($image->getProperty('mime-types-supported'));
        $this->assertTrue($image->mimeTypeSupported('image/gif'));
        $this->assertFalse($image->mimeTypeSupported('image/fake'));
    }

    /**
     * Test mime type supported failed.
     */
    public function testMimeTypeSupportedFailed()
    {
        $keyword = new Keyword();
        $this->assertNotInstanceOf('\BackBee\ClassContent\Element\File', $keyword);
        $this->assertNull($keyword->getProperty('mime-types-supported'));
    }
}
