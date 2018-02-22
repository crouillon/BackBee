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

use org\bovigo\vfs\vfsStream;

use BackBee\Tests\Traits\InvokePropertyTrait;
use BackBee\Util\Transport\Exception\MisconfigurationException;
use BackBee\Util\Transport\FileSystem;

/**
 * Tests suite for class FileSystem.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Transport\FileSystem
 */
class FileSystemTest extends \PHPUnit_Framework_TestCase
{

    use InvokePropertyTrait;

    /**
     * @var FileSystem
     */
    private $transport;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        vfsStream::setup('root', 0777);

        $this->transport = new FileSystem([
            'remotepath' => vfsStream::url('root/remote')
        ]);
    }

    /**
     * @covers            ::__construct()
     * @expectedException BackBee\Util\Transport\Exception\MisconfigurationException
     *
     * @return void
     */
    public function testInvalidConstruct()
    {
        vfsStream::setup('invalid', 0000);
        new FileSystem([
            'remotepath' => vfsStream::url('invalid/remote')
        ]);
    }

    /**
     * @covers ::connect()
     */
    public function testConnect()
    {
        $this->assertEquals(
            $this->transport,
            $this->transport->connect()
        );
    }

    /**
     * @covers ::login()
     */
    public function testLogin()
    {
        $this->invokeProperty(
            $this->transport,
            'startingpath',
            vfsStream::url('root/remote/path')
        );

        $this->assertEquals(
            $this->transport,
            $this->transport->login()
        );
    }

    /**
     * @covers            ::login()
     * @expectedException BackBee\Util\Transport\Exception\AuthenticationException
     */
    public function testForbiddenLogin()
    {
        $this->invokeProperty(
            $this->transport,
            'startingpath',
            vfsStream::url('root/remote/path')
        );
        chmod(vfsStream::url('root/remote'), 0000);

        $this->assertEquals(
            $this->transport,
            $this->transport->login()
        );
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
    }

    /**
     * @covers ::cd()
     */
    public function testCd()
    {
        $this->assertTrue(
            $this->transport->cd(vfsStream::url('root/remote'))
        );
    }

    /**
     * @covers            ::cd()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testInvalidCd()
    {
        $this->assertFalse(
            $this->transport->cd('invalid/path')
        );
    }

    /**
     * @covers ::ls()
     */
    public function testLs()
    {
        $this->assertEquals(
            ['.', '..'],
            $this->transport->ls()
        );
    }

    /**
     * @covers            ::ls()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testInvalidLs()
    {
        vfsStream::setup('invalid', 0000);
        $transport = new FileSystem(['remotepath' => vfsStream::url('invalid')]);

        $this->assertFalse($transport->ls(''));
    }

    /**
     * @covers ::pwd()
     */
    public function testPwd()
    {
        $this->assertEquals(
            vfsStream::url('root/remote'),
            $this->transport->pwd()
        );
    }

    /**
     * @covers ::send()
     */
    public function testSend()
    {
        $target = basename(__FILE__);

        $this->assertTrue(
            $this->transport->send(__FILE__, $target)
        );
        $this->assertTrue(
            file_exists(vfsStream::url('root/remote') . '/' . $target)
        );
    }

    /**
     * @covers            ::send()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSendInvalidSource()
    {
        $this->assertFalse(
            $this->transport->send(__FILE__ . 'unknown', basename(__FILE__))
        );
    }

    /**
     * @covers            ::send()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSendAlreadyExisting()
    {
        $target = basename(__FILE__);
        $this->assertTrue(
            $this->transport->send(__FILE__, $target)
        );
        $this->assertFalse(
            $this->transport->send(__FILE__, $target)
        );
    }

    /**
     * @covers            ::send()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testInvalidSend()
    {
        vfsStream::setup('invalid', 0000);
        $transport = new FileSystem(['remotepath' => vfsStream::url('invalid')]);

        $this->assertFalse(
            $this->transport->send(__FILE__, basename(__FILE__))
        );
    }

    /**
     * @covers ::sendRecursive()
     */
    public function testSendRecursive()
    {
        $target = basename(__DIR__);

        $this->assertTrue(
            $this->transport->sendRecursive(__DIR__, $target)
        );
        $this->assertTrue(
            is_dir(vfsStream::url('root/remote') . '/' . $target)
        );
        $this->assertTrue(
            file_exists(vfsStream::url('root/remote') . '/' . $target . '/' . basename(__FILE__))
        );
    }

    /**
     * @covers            ::sendRecursive()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testInvalidSendRecursive()
    {
        vfsStream::setup('invalid', 0000);
        $transport = new FileSystem(['remotepath' => vfsStream::url('invalid')]);

        $this->assertFalse(
            $this->transport->sendRecursive(vfsStream::url('invalid'), basename(__DIR__))
        );
    }

    /**
     * @covers            ::sendRecursive()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testFileExistSendRecursive()
    {
        $target = basename(__DIR__);
        $this->transport->send(__FILE__, $target);

        $this->assertFalse(
            $this->transport->sendRecursive(__DIR__, $target)
        );
    }

    /**
     * @covers            ::sendRecursive()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testForbiddenSendRecursive()
    {
        chmod(vfsStream::url('root/remote'), 0000);
        $this->assertFalse(
            $this->transport->sendRecursive(__DIR__, basename(__DIR__))
        );
    }

    /**
     * @covers ::get()
     */
    public function testGet()
    {
        mkdir(vfsStream::url('root/source'));
        mkdir(vfsStream::url('root/target'));
        copy(__FILE__, vfsStream::url('root/source/' . basename(__FILE__)));

        $this->assertTrue(
            $this->transport->get(
                vfsStream::url('root/target/' . basename(__FILE__)),
                vfsStream::url('root/source/' . basename(__FILE__))
            )
        );
    }

    /**
     * @covers            ::get()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testExistingGet()
    {
        $this->assertFalse(
            $this->transport->get(__FILE__, basename(__FILE__))
        );
    }

    /**
     * @covers            ::get()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testInvalidGet()
    {
        $this->assertFalse(
            $this->transport->get(vfsStream::url('root/source/' . basename(__FILE__)), basename(__FILE__))
        );
    }

    /**
     * @covers            ::get()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testForbiddenGet()
    {
        mkdir(vfsStream::url('root/source'));
        mkdir(vfsStream::url('root/target'));
        chmod(vfsStream::url('root/target'), 0000);
        copy(__FILE__, vfsStream::url('root/source/' . basename(__FILE__)));

        $this->assertFalse(
            $this->transport->get(
                vfsStream::url('root/target/' . basename(__FILE__)),
                vfsStream::url('root/source/' . basename(__FILE__))
            )
        );
    }

    /**
     * @covers ::getRecursive()
     */
    public function testGetRecursive()
    {
        mkdir(vfsStream::url('root/source'));
        copy(__FILE__, vfsStream::url('root/source/' . basename(__FILE__)));

        $this->assertTrue(
            $this->transport->getRecursive(
                vfsStream::url('root/target'),
                vfsStream::url('root/source')
            )
        );
        $this->assertTrue(
            file_exists(vfsStream::url('root/target/' . basename(__FILE__)))
        );
    }

    /**
     * @covers            ::getRecursive()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testForbiddenGetRecursive()
    {
        mkdir(vfsStream::url('root/source'));
        mkdir(vfsStream::url('root/target'));
        chmod(vfsStream::url('root/target'), 0000);
        copy(__FILE__, vfsStream::url('root/source/' . basename(__FILE__)));

        $this->assertFalse(
            $this->transport->getRecursive(
                vfsStream::url('root/target/target'),
                vfsStream::url('root/source')
            )
        );
    }

    /**
     * @covers            ::getRecursive()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testExistingGetRecursive()
    {
        mkdir(vfsStream::url('root/source'));
        copy(__FILE__, vfsStream::url('root/source/' . basename(__FILE__)));
        copy(__FILE__, vfsStream::url('root/target'));

        $this->assertFalse(
            $this->transport->getRecursive(
                vfsStream::url('root/target'),
                vfsStream::url('root/source')
            )
        );
    }

    /**
     * @covers ::mkdir()
     */
    public function testMkdir()
    {
        $this->assertTrue(
            $this->transport->mkdir('test')
        );
        $this->assertTrue(
            is_dir(vfsStream::url('root/remote/test'))
        );
    }

    /**
     * @covers            ::mkdir()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testInvalidMkdir()
    {
        $this->assertFalse(
            $this->transport->mkdir('test/test', false)
        );
    }

    /**
     * @covers ::delete()
     */
    public function testDelete()
    {
        $this->transport->sendRecursive(__DIR__, basename(__DIR__));
        $this->assertTrue(
            $this->transport->delete(basename(__DIR__), true)
        );
    }

    /**
     * @covers            ::delete()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testForbiddenDirDelete()
    {
        $this->transport->mkdir(basename(__DIR__));
        chmod(vfsStream::url('root/remote'), 0000);
        $this->assertFalse(
            $this->transport->delete(basename(__DIR__))
        );
    }

    /**
     * @covers            ::delete()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testForbiddenFileDelete()
    {
        $this->transport->send(__FILE__, basename(__FILE__));
        chmod(vfsStream::url('root/remote'), 0000);
        $this->assertFalse(
            $this->transport->delete(basename(__FILE__))
        );
    }

    /**
     * @covers ::rename()
     */
    public function testRename()
    {
        $this->transport->send(__FILE__, basename(__FILE__));
        $this->assertTrue(
            $this->transport->rename(basename(__FILE__), 'newname')
        );
        $this->assertTrue(
            file_exists(vfsStream::url('root/remote/newname'))
        );
    }

    /**
     * @covers            ::rename()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testUnknownRename()
    {
        $this->assertFalse(
            $this->transport->rename(basename(__FILE__), 'newname')
        );
    }

    /**
     * @covers            ::rename()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testExistingRename()
    {
        $this->transport->send(__FILE__, basename(__FILE__));
        $this->transport->send(__FILE__, 'existing');
        $this->assertFalse(
            $this->transport->rename(basename(__FILE__), 'existing')
        );
    }

    /**
     * @covers            ::rename()
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testForbiddenRename()
    {
        $this->transport->send(__FILE__, basename(__FILE__));
        chmod(vfsStream::url('root/remote'), 0000);
        $this->assertFalse(
            $this->transport->rename(basename(__FILE__), 'newname')
        );
    }
}
