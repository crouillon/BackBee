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

use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use BackBee\Bundle\AbstractBundleController;
use BackBee\Site\Site;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Tests suite for class AbstractBundleController.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\AbstractBundleController
 */
class AbstractBundleControllerTest extends BundleTestCase
{
    use InvokeMethodTrait;

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

        $renderer = $this->getMockRenderer();
        $renderer->expects($this->any())
            ->method('partial')
            ->willReturn('ok');

        $this->application
            ->expects($this->any())
            ->method('getRenderer')
            ->willReturn($renderer);

        $this->application
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->getMockEntityManager());

        $this->application
            ->expects($this->any())
            ->method('getSession')
            ->willReturn(new Session(new MockArraySessionStorage()));

        $this->application
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn(new Request());

        $this->controller = $this->getMockForAbstractClass(
            AbstractBundleController::class,
            [$this->application],
            '',
            true,
            true,
            true,
            ['testAction']
        );
    }

    /**
     * @covers ::__construct()
     * @covers ::getBundle()
     * @covers ::setBundle()
     */
    public function testBundle()
    {
        $bundle = $this->getBundle();

        $this->assertEquals($this->controller, $this->controller->setBundle($bundle));
        $this->assertEquals($bundle, $this->controller->getBundle());
    }

    /**
     * @covers ::__call()
     * @covers ::checkMethodExist()
     */
    public function testCall()
    {
        $this->controller
            ->expects($this->once())
            ->method('testAction')
            ->will($this->returnValue('ok'));

        $response = $this->controller->test();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('ok', $response->getContent());
    }

    /**
     * @covers ::__call()
     * @covers ::checkMethodExist()
     */
    public function testUnknownMethod()
    {
        $this->application
            ->expects($this->once())
            ->method('isDebugMode')
            ->willReturn(true);

        $badResponse = $this->controller->unknown();
        $this->assertInstanceOf(Response::class, $badResponse);
        $this->assertEquals(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $badResponse->getStatusCode()
        );
    }

    /**
     * @covers ::render()
     */
    public function testRender()
    {
        $result = $this->controller->render('Element/Text.phtml', ['value' => 'value']);
        $this->assertEquals('ok', $result);
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
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('response', $response->getContent());
    }

    /**
     * @covers ::invockeAction()
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
     * @covers            ::checkMethodExist()
     * @expectedException \LogicException
     */
    public function testCheckUnknownMethodExist()
    {
        $this->invokeMethod($this->controller, 'checkMethodExist', ['unknown']);
    }

    /**
     * @covers ::checkMethodExist()
     */
    public function testCheckMethodExist()
    {
        $this->assertTrue($this->invokeMethod($this->controller, 'checkMethodExist', ['testAction']));
    }

    /**
     * @covers ::createResponse()
     */
    public function testCreateResponse()
    {
        $response = $this->invokeMethod($this->controller, 'createResponse', ['content']);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @covers ::redirect()
     */
    public function testRedirect()
    {
        $response = $this->invokeMethod($this->controller, 'redirect', ['url']);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @covers ::getFlashBag()
     * @covers ::addFlashSuccess()
     * @covers ::addFlashError()
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
     * @covers            ::throwsExceptionIfEntityNotFound()
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionEntityNotFound()
    {
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $this->application
            ->getEntityManager()
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->invokeMethod($this->controller, 'throwsExceptionIfEntityNotFound', [Site::class, 'site_uid']);
    }

    /**
     * @covers ::getRepository()
     * @covers ::throwsExceptionIfEntityNotFound()
     */
    public function testThrowsExceptionIfEntityNotFound()
    {
        $site = new Site('site_uid', ['label' => 'site-test']);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->willReturn($site);

        $this->application
            ->getEntityManager()
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
 
        $this->assertEquals(
            $site,
            $this->invokeMethod($this->controller, 'throwsExceptionIfEntityNotFound', [Site::class, 'site_uid'])
        );
    }
}
