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

use phpmock\phpunit\PHPMock;

use BackBee\Tests\Traits\InvokePropertyTrait;
use BackBee\Util\Transport\FTP;

/**
 * Tests suite for class FTP.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Transport\FTP
 */
class FTPTest extends \PHPUnit_Framework_TestCase
{
    use InvokePropertyTrait;
    use PHPMock;

    /**
     * @var FTP
     */
    private $transport;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->transport = new FTP();
    }

    /**
     * @covers ::__construct()
     */
    public function testConstruct()
    {
        $transport = new FTP([
            'passive' => false,
            'mode' => 'BINARY'
        ]);

        $this->assertEquals(
            false,
            $this->invokeProperty($transport, 'passive')
        );

        $this->assertEquals(
            FTP_BINARY,
            $this->invokeProperty($transport, 'mode')
        );
    }

    /**
     * @covers ::connect()
     */
    public function testConnect()
    {
        $mock = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_connect');
        $mock->expects($this->any())->willReturn('stream');

        $this->assertEquals(
            $this->transport,
            $this->transport->connect('host', 21)
        );
    }

    /**
     * @covers            ::connect()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoHostConnect()
    {
        $this->transport->connect();
    }

    /**
     * @covers            ::connect()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidPortConnect()
    {
        $this->transport->connect('host', 'port');
    }

    /**
     * @covers            ::connect()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testCannotConnect()
    {
        $mock = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_connect');
        $mock->expects($this->any())->willReturn(false);

        $this->transport->connect('host', 21);
    }

    /**
     * @covers ::login()
     */
    public function testLogin()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $login = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_login');
        $login->expects($this->any())
            ->with('stream', 'username', 'password')
            ->willReturn(true);

        $pasv = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_pasv');
        $pasv->expects($this->any())
            ->with('stream', true)
            ->willReturn(true);

        $this->assertEquals(
            $this->transport,
            $this->transport->login('username', 'password')
        );
    }

    /**
     * @covers            ::login()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamLogin()
    {
        $this->transport->login('username', 'password');
    }

    /**
     * @covers            ::login()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidLogin()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $login = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_login');
        $login->expects($this->any())->willReturn(false);

        $this->transport->login('username', 'password');
    }

    /**
     * @covers            ::login()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoPasvLogin()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $login = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_login');
        $login->expects($this->any())->willReturn(true);

        $pasv = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_pasv');
        $pasv->expects($this->any())->willReturn(false);

        $this->transport->login('username', 'password');
    }

    /**
     * @covers ::cd()
     */
    public function testCd()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $chdir = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_chdir');
        $chdir->expects($this->any())
            ->with('stream', '/dir')
            ->willReturn(true);

        $this->assertTrue(
            $this->transport->cd('/dir')
        );
    }

    /**
     * @covers            ::cd()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamCd()
    {
        $this->transport->cd('/dir');
    }

    /**
     * @covers            ::cd()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidCd()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $chdir = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_chdir');
        $chdir->expects($this->any())->willReturn(false);

        $this->transport->cd();
    }

    /**
     * @covers ::ls()
     */
    public function testLs()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $pwd = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_pwd');
        $pwd->expects($this->any())->willReturn(true);

        $nlist = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_nlist');
        $nlist->expects($this->any())
            ->with('stream', $this->transport->pwd())
            ->willReturn(['list']);

        $this->assertEquals(
            ['list'],
            $this->transport->ls()
        );
    }

    /**
     * @covers ::ls()
     */
    public function testInvalidLs()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $nlist = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_nlist');
        $nlist->expects($this->any())->willReturn(false);

        $this->assertEquals(
            [],
            $this->transport->ls('/dir')
        );
    }

    /**
     * @covers            ::ls()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamLs()
    {
        $this->transport->ls();
    }

    /**
     * @covers ::pwd()
     */
    public function testPwd()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $pwd = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_pwd');
        $pwd->expects($this->any())->willReturn('/pwd');

        $this->assertEquals(
            '/pwd',
            $this->transport->pwd()
        );
    }

    /**
     * @covers            ::pwd()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamPwd()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $pwd = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_pwd');
        $pwd->expects($this->any())->willReturn(false);

        $this->transport->pwd();
    }

    /**
     * @covers            ::pwd()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidPwd()
    {
        $this->transport->pwd();
    }

    /**
     * @covers ::send()
     */
    public function testSend()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $put = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_put');
        $put->expects($this->any())
            ->with('stream', basename(__FILE__), __FILE__, FTP_ASCII)
            ->willReturn(true);

        $this->assertTrue(
            $this->transport->send(__FILE__, basename(__FILE__))
        );
    }

    /**
     * @covers            ::send()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamSend()
    {
        $this->transport->send(__FILE__, basename(__FILE__));
    }

    /**
     * @covers            ::send()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidSend()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $put = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_put');
        $put->expects($this->any())->willReturn(false);

        $this->transport->send(__FILE__, basename(__FILE__));
    }

    /**
     * @covers ::sendRecursive()
     */
    public function testSendRecursive()
    {
        $this->assertFalse(
            $this->transport->sendRecursive(__FILE__, basename(__FILE__))
        );
    }

    /**
     * @covers ::get()
     */
    public function testGet()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $get = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_get');
        $get->expects($this->any())
            ->with('stream', __FILE__, basename(__FILE__), FTP_ASCII)
            ->willReturn(true);

        $this->assertTrue(
            $this->transport->get(__FILE__, basename(__FILE__))
        );
    }

    /**
     * @covers            ::get()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamGet()
    {
        $this->transport->get(__FILE__, basename(__FILE__));
    }

    /**
     * @covers            ::get()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidGet()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $get = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_get');
        $get->expects($this->any())->willReturn(false);

        $this->transport->get(__FILE__, basename(__FILE__));
    }

    /**
     * @covers ::getRecursive()
     */
    public function testGetRecursive()
    {
        $this->assertFalse(
            $this->transport->getRecursive(__FILE__, basename(__FILE__))
        );
    }

    /**
     * @covers ::mkdir()
     */
    public function testMkdir()
    {
        $this->assertFalse(
            $this->transport->mkdir('/dir')
        );
    }

    /**
     * @covers ::delete()
     */
    public function testDelete()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $delete = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_delete');
        $delete->expects($this->any())
            ->with('stream', basename(__FILE__))
            ->willReturn(true);

        $this->assertTrue(
            $this->transport->delete(basename(__FILE__))
        );
    }

    /**
     * @covers            ::delete()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamDelete()
    {
        $this->transport->delete(basename(__FILE__));
    }

    /**
     * @covers            ::delete()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidDelete()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $delete = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_delete');
        $delete->expects($this->any())->willReturn(false);

        $this->transport->delete(basename(__FILE__));
    }

    /**
     * @covers ::rename()
     */
    public function testRename()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $rename = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_rename');
        $rename->expects($this->any())
            ->with('stream', 'old', 'new')
            ->willReturn(true);

        $this->assertTrue(
            $this->transport->rename('old', 'new')
        );
    }

    /**
     * @covers            ::rename()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testNoStreamRename()
    {
        $this->transport->rename('old', 'new');
    }

    /**
     * @covers            ::rename()
     * @expectedException BackBee\Util\Transport\Exception\TransportException
     */
    public function testInvalidRename()
    {
        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $rename = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_rename');
        $rename->expects($this->any())->willReturn(false);

        $this->transport->rename('old', 'new');
    }

    /**
     * @covers ::disconnect()
     */
    public function testDisconnect()
    {
        $this->assertEquals(
            $this->transport,
            $this->transport->disconnect()
        );

        $this->invokeProperty($this->transport, 'ftp_stream', 'stream');

        $close = $this->getFunctionMock('\BackBee\Util\Transport', 'ftp_close');
        $close->expects($this->any())->with('stream');

        $this->assertEquals(
            $this->transport,
            $this->transport->disconnect()
        );
    }
}
