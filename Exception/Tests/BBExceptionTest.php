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

namespace BackBee\Exception\Tests;

use BackBee\Exception\BBException;

/**
 * Tests suite for class BBException.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass BackBee\Exception\BBException
 */
class BBExceptionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $emptyException = new BBException();
        $this->assertEquals(BBException::UNKNOWN_ERROR, $emptyException->getCode());

        $sourceExeption = new BBException('message', BBException::INVALID_ARGUMENT, null, 'source', 10);
        $this->assertEquals(BBException::INVALID_ARGUMENT, $sourceExeption->getCode());
        $this->assertEquals('message in source at 10.', $sourceExeption->getMessage());
    }

    /**
     * @covers ::setSource()
     * @covers ::getSource()
     * @covers ::setSeek()
     * @covers ::getSeek()
     * @covers ::updateMessage()
     */
    public function testSourceAndSeek()
    {
        $exception = new BBException();

        $this->assertEquals($exception, $exception->setSource('source'));
        $this->assertEquals(' in source at 0.', $exception->getMessage());

        $this->assertEquals($exception, $exception->setSeek(10));
        $this->assertEquals(' in source at 10.', $exception->getMessage());

        $this->assertEmpty($exception->setSource(null)->getMessage());
    }
}
