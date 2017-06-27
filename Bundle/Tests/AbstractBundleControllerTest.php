<?php

/*
 * Copyright (c) 2011-2017 Lp digital system
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
 */

namespace BackBee\Bundle\Tests;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

use BackBee\Bundle\AbstractBundleController;
use BackBee\Site\Site;

/**
 * Tests suite for class AbstractBundleController.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractBundleControllerTest extends BundleTestCase
{

    /**
     * @var AbstractBundleController
     */
    private $controller;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->controller = $this->getMockForAbstractClass(
            AbstractBundleController::class,
            [self::$app],
            '',
            true,
            true,
            true,
            ['testAction']
        );
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::__construct()
     * @covers BackBee\Bundle\AbstractBundleController::getBundle()
     * @covers BackBee\Bundle\AbstractBundleController::setBundle()
     */
    public function testBundle()
    {
        $bundle = $this->getBundle();

        $this->assertEquals($this->controller, $this->controller->setBundle($bundle));
        $this->assertEquals($bundle, $this->controller->getBundle());
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::__call()
     * @covers BackBee\Bundle\AbstractBundleController::checkMethodExist()
     */
    public function testCall()
    {
        $this->controller->expects($this->once())->method('testAction')->will($this->returnValue('ok'));
        $response = $this->controller->test();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('ok', $response->getContent());

        self::$app->setDebugMode(true);
        $badResponse = $this->controller->unknown();
        $this->assertInstanceOf(Response::class, $badResponse);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $badResponse->getStatusCode());
        self::$app->setDebugMode(false);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::render()
     */
    public function testRender()
    {
        $result = $this->controller->render('Element/Text.phtml', ['value' => 'value']);
        $this->assertStringStartsWith('<div>', $result);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::decorateResponse()
     * @expectedException \InvalidArgumentException
     */
    public function testDecorateNotResponse()
    {
        $this->invokeMethod($this->controller, 'decorateResponse', [$this->controller, 'test']);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::decorateResponse()
     */
    public function testDecorateResponse()
    {
        $response = $this->invokeMethod($this->controller, 'decorateResponse', ['response', 'testAction']);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('response', $response->getContent());
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::invockeAction()
     */
    public function testInvockeAction()
    {
        $response = $this->invokeMethod($this->controller, 'invockeAction', ['unknown',[]]);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $this->controller->expects($this->once())->method('testAction')->will($this->returnValue('ok'));
        $this->assertEquals('ok', $this->invokeMethod($this->controller, 'testAction', ['unknown',[]]));
    }

    /**
     * @covers            BackBee\Bundle\AbstractBundleController::checkMethodExist()
     * @expectedException \LogicException
     */
    public function testCheckUnknownMethodExist()
    {
        $this->invokeMethod($this->controller, 'checkMethodExist', ['unknown']);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::checkMethodExist()
     */
    public function testCheckMethodExist()
    {
        $this->assertTrue($this->invokeMethod($this->controller, 'checkMethodExist', ['testAction']));
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::createResponse()
     */
    public function testCreateResponse()
    {
        $response = $this->invokeMethod($this->controller, 'createResponse', ['content']);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::redirect()
     */
    public function testRedirect()
    {
        $response = $this->invokeMethod($this->controller, 'redirect', ['url']);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::getFlashBag()
     * @covers BackBee\Bundle\AbstractBundleController::addFlashSuccess()
     * @covers BackBee\Bundle\AbstractBundleController::addFlashError()
     */
    public function testFlashBag()
    {
        $flashBag = $this->invokeMethod($this->controller, 'getFlashBag');

        $this->assertInstanceOf(FlashBag::class, $flashBag);
        $this->assertEquals($this->controller, $this->invokeMethod($this->controller, 'addFlashSuccess', ['message']));
        $this->assertEquals(['message'], $flashBag->get('success'));
        $this->assertEquals($this->controller, $this->invokeMethod($this->controller, 'addFlashError', ['message']));
        $this->assertEquals(['message'], $flashBag->get('error'));
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::throwsExceptionIfEntityNotFound()
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionEntityNotFound()
    {
        self::$kernel->resetDatabase([self::$em->getClassMetaData(Site::class)]);
        $this->invokeMethod($this->controller, 'throwsExceptionIfEntityNotFound', [Site::class, 'site_uid']);
    }

    /**
     * @covers BackBee\Bundle\AbstractBundleController::getRepository()
     * @covers BackBee\Bundle\AbstractBundleController::throwsExceptionIfEntityNotFound()
     */
    public function testThrowsExceptionIfEntityNotFound()
    {
        self::$kernel->resetDatabase([self::$em->getClassMetaData(Site::class)]);
        $site = new Site('site_uid', ['label' => 'site-test']);
        self::$em->persist($site);
        self::$em->flush();

        $this->assertEquals(
            $site,
            $this->invokeMethod($this->controller, 'throwsExceptionIfEntityNotFound', [Site::class, 'site_uid'])
        );
    }
}
