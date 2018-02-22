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

namespace BackBee\Util\Tests\Transport;

use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;
use BackBee\Util\Transport\AbstractTransport;

/**
 * Tests suite for class AbstractTransport
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Transport\AbstractTransport
 */
class AbstractTransportTest extends \PHPUnit_Framework_TestCase
{
    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var AbstractTransport
     */
    private $transport;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        parent::setUp();

        $this->transport = $this->getMockForAbstractClass(
            AbstractTransport::class,
            [[
                'protocol' => 'protocol',
                'remotepath' => '/remote/path'
            ]],
            '',
            true,
            false,
            true,
            [
                'connect',
                'login',
                'disconnect',
                'cd',
                'ls',
                'pwd',
                'send',
                'sendRecursive',
                'get',
                'getRecursive',
                'mkdir',
                'delete',
                'rename'
            ]
        );
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $this->assertEquals(
            'protocol',
            $this->invokeProperty($this->transport, 'protocol')
        );
    }

    /**
     * @covers ::__get()
     */
    public function testGetter()
    {
        $this->assertEquals(
            '/remote/path',
            $this->transport->_remotepath
        );
    }

    /**
     * @covers ::__get()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidProperty()
    {
        $this->assertEquals(
            '/remote/path',
            $this->transport->_unknown
        );
    }

    /**
     * @covers ::getAbsoluteRemotePath()
     */
    public function testGetAbsoluteRemotePath()
    {
        $this->assertEquals(
            '/remote/path',
            $this->invokeMethod(
                $this->transport,
                'getAbsoluteRemotePath'
            )
        );

        $this->assertEquals(
            '//:error',
            $this->invokeMethod(
                $this->transport,
                'getAbsoluteRemotePath',
                ['//:error']
            )
        );

        $this->assertEquals(
            '/fake/folder',
            $this->invokeMethod(
                $this->transport,
                'getAbsoluteRemotePath',
                [implode(DIRECTORY_SEPARATOR, ['', 'fake', 'folder'])]
            )
        );

        $this->assertEquals(
            '/remote/path/fake',
            $this->invokeMethod(
                $this->transport,
                'getAbsoluteRemotePath',
                ['fake']
            )
        );
    }

    /**
     * @covers ::triggerError()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testTriggerError()
    {
        $this->invokeMethod(
            $this->transport,
            'triggerError',
            ['message']
        );
    }
}
