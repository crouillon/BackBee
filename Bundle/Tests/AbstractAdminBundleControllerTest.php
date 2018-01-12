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

namespace BackBee\Bundle\Tests;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use BackBee\BBApplication;
use BackBee\Bundle\AbstractAdminBundleController;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Tests suite for class AbstractAdminBundleController.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\AbstractAdminBundleController
 */
class AbstractAdminBundleControllerTest extends BundleTestCase
{
    use InvokeMethodTrait;

    /**
     * @var AbstractAdminBundleController
     */
    private $controller;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = $this->getMockForAbstractClass(
            AbstractAdminBundleController::class,
            [$application],
            '',
            true,
            true,
            true,
            ['testAction', 'isGranted']
        );
    }

    /**
     * @covers ::__call()
     */
    public function testCallNotPermitted()
    {
        $this->controller
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $response = $this->controller->test();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers ::__call()
     */
    public function testCallUnknown()
    {
        $this->controller
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $response = $this->controller->unknown();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /**
     * @covers ::__call()
     */
    public function testCall()
    {
        $this->controller
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $this->controller
            ->expects($this->once())
            ->method('testAction')
            ->will($this->returnValue('ok'));

        $response = $this->controller->test();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('{"content":"ok","notification":[],"error":""}', $response->getContent());
    }

    /**
     * @covers ::notifyUser()
     */
    public function testNotifyUser()
    {
        $this->controller
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $this->controller
            ->expects($this->once())
            ->method('testAction')
            ->will($this->returnValue('ok'));

        $this->controller->notifyUser('type', 'message');
        $content = $this->controller->test()->getContent();
        $this->assertEquals('{"content":"ok","notification":[{"type":"type","message":"message"}],"error":""}', $content);
    }

    /**
     * @covers            ::decorateResponse()
     * @expectedException \InvalidArgumentException
     */
    public function testDecorateNotResponse()
    {
        $this->invokeMethod($this->controller, 'decorateResponse', [$this->controller, 'test']);
    }

    /**
     * @covers ::decorateResponse()
     */
    public function testDecorateResponse()
    {
        $response = $this->invokeMethod($this->controller, 'decorateResponse', ['response', 'testAction']);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('{"content":"response","notification":[],"error":""}', $response->getContent());
    }
}
