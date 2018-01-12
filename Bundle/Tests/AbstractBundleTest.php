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

use org\bovigo\vfs\vfsStream;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

use BackBee\Bundle\AbstractBundle;
use BackBee\Bundle\BundleExposedControllerInterface;
use BackBee\Security\Acl\Domain\ObjectIdentifiableInterface;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Tests suite for class AbstractBundle.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\AbstractBundle
 */
class AbstractBundleTest extends BundleTestCase
{
    use InvokeMethodTrait;

    /**
     * @covers ::__construct()
     * @covers ::getApplication()
     * @covers ::getBaseDirectory()
     * @covers ::getId()
     * @covers ::getExposedActionsMapping()
     * @covers ::isStarted()
     */
    public function testConstruct()
    {
        $bundle = $this->getBundle('mockbundle', vfsStream::url('MockBundle'));

        $this->assertEquals($this->application, $bundle->getApplication());
        $this->assertEquals(vfsStream::url('MockBundle'), $bundle->getBaseDirectory());
        $this->assertEquals('mockbundle', $bundle->getId());
        $this->assertEquals([], $bundle->getExposedActionsMapping());
        $this->assertFalse($bundle->isStarted());
    }

    /**
     * @covers ::__construct()
     * @covers ::getId()
     * @covers ::getBaseDirectory()
     */
    public function testConstructWithoutId()
    {
        $bundle = $this->getBundle();

        $this->assertEquals('MockBundle', $bundle->getId());
        $this->assertEquals(vfsStream::url('MockBundle'), $bundle->getBaseDirectory());
    }

    /**
     * @covers ::getResourcesDirectory()
     */
    public function testGetResourcesDirectory()
    {
        $this->assertEquals(
            vfsStream::url('MockBundle') . DIRECTORY_SEPARATOR . 'Resources',
            $this->getBundle()->getResourcesDirectory()
        );
    }

    /**
     * @covers ::getProperty()
     */
    public function testGetProperty()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(['name' => 'mock'], $bundle->getProperty());
        $this->assertEquals('mock', $bundle->getProperty('name'));
        $this->assertNull($bundle->getProperty('unknown'));
    }

    /**
     * @covers ::getConfig()
     */
    public function testGetConfig()
    {
        $this->assertEquals(
            $this->container->get('bundle.mockbundle.config'),
            $this->getBundle()->getConfig()
        );
    }

    /**
     * @covers ::getConfigServiceId()
     */
    public function testGetConfigServiceId()
    {
        $this->assertEquals(
            'bundle.mockbundle.config',
            $this->getBundle()->getConfigServiceId()
        );
    }

    /**
     * @covers ::getConfigDirectory()
     */
    public function testGetConfigDirectory()
    {
        $this->assertEquals(
            vfsStream::url('MockBundle') . DIRECTORY_SEPARATOR . AbstractBundle::CONFIG_DIRECTORY_NAME,
            $this->getBundle()->getConfigDirectory()
        );

        $mockDir = ['Ressources' => ['config.yml' => '{"bundle":{"name":"mock"}}'],];
        vfsStream::setup('Mock2Bundle', 0777, $mockDir);

        $bundle = $this->getBundle('mockbundle', vfsStream::url('Mock2Bundle'));

        $this->assertEquals(
            vfsStream::url('Mock2Bundle') . DIRECTORY_SEPARATOR . AbstractBundle::OLD_CONFIG_DIRECTORY_NAME,
            $bundle->getConfigDirectory()
        );
    }

    /**
     * @covers ::isConfigPerSite()
     * @covers ::setConfigPerSite()
     */
    public function testConfigPerSite()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(AbstractBundle::DEFAULT_CONFIG_PER_SITE_VALUE, $bundle->isConfigPerSite());
        $this->assertFalse($bundle->setConfigPerSite(false)->isConfigPerSite());
    }

    /**
     * @covers ::started()
     * @covers ::isStarted()
     */
    public function testStarted()
    {
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->setMethods(['dispatch'])
            ->getMock();

        $this->container->set('event.dispatcher', $dispatcher);

        $dispatcher
            ->expects($this->once())
            ->method('dispatch');

        $bundle = $this->getBundle();
        $bundle->started();

        $this->assertTrue($bundle->isStarted());
    }

    /**
     * @covers ::isEnabled()
     * @covers ::setEnable()
     */
    public function testEnabled()
    {
        $bundle = $this->getBundle();

        $this->assertFalse($bundle->isEnabled());
        $this->assertTrue($bundle->setEnable(true)->isEnabled());
    }

    /**
     * @covers ::getCategory()
     * @covers ::setCategory()
     */
    public function testCategory()
    {
        $bundle = $this->getBundle();

        $this->assertEquals([], $bundle->getCategory());
        $this->assertEquals(['category'], $bundle->setCategory('category')->getCategory());
    }

    /**
     * @covers ::jsonSerialize()
     */
    public function testJsonSerialize()
    {
        $bundle = $this->getBundle();

        $routing = $this->getMockBuilder(\BackBee\Routing\RouteCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUri'])
            ->getMock();

        $routing->expects($this->any())
                ->method('getUri')
                ->will($this->returnValue('thumbnail'));

        $this->container->set('routing', $routing);

        $expected = new \stdClass();
        $expected->id = 'MockBundle';
        $expected->name = 'mock';
        $expected->enable = true;
        $expected->config_per_site = false;
        $expected->category = [];
        $expected->thumbnail = 'thumbnail';
        $expected->exposed_actions = [];
        $this->assertEquals($expected, $bundle->jsonSerialize());

        $bundle->setEnable(false)
            ->setConfigPerSite(true)
            ->setCategory(['category'])
            ->getConfig()
            ->setSection('bundle', ['thumbnail' => 'custom', 'admin_entry_point' => 'controller.action']);

        $expected->enable = false;
        $expected->config_per_site = true;
        $expected->category = ['category'];
        $expected->thumbnail = 'custom';
        $expected->admin_entry_point = '/bundle/MockBundle/controller/action';
        $this->assertEquals($expected, $bundle->jsonSerialize());
    }

    /**
     * @covers ::getObjectIdentifier()
     */
    public function testGetObjectIdentifier()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(
            sprintf('%s(%s)', $bundle->getType(), $bundle->getIdentifier()),
            $bundle->getObjectIdentifier()
        );
    }

    /**
     * @covers ::getIdentifier()
     */
    public function testGetIdentifier()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(
            $bundle->getId(),
            $bundle->getIdentifier()
        );
    }

    /**
     * @covers ::getType()
     */
    public function testGetType()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(
            get_class($bundle),
            $bundle->getType()
        );
    }

    /**
     * @covers ::equals()
     */
    public function testEquals()
    {
        $bundle = $this->getBundle();
        $identity = $this->getMockBuilder(ObjectIdentityInterface::class)
            ->setMethods(['getType', 'getIdentifier', 'equals'])
            ->getMock();

        $identity->expects($this->any())
                ->method('getType')
                ->will($this->returnValue($bundle->getType()));
        $identity->expects($this->any())
                ->method('getIdentifier')
                ->will($this->returnValue($bundle->getIdentifier()));

        $this->assertTrue($bundle->equals($identity));
    }

    /**
     * @covers ::equals()
     */
    public function testNotEqualsType()
    {
        $bundle = $this->getBundle();
        $identity = $this->getMockBuilder(ObjectIdentityInterface::class)
            ->setMethods(['getType', 'getIdentifier', 'equals'])
            ->getMock();

        $identity->expects($this->any())
                ->method('getType')
                ->will($this->returnValue('Unknown\Class'));
        $identity->expects($this->any())
                ->method('getIdentifier')
                ->will($this->returnValue($bundle->getIdentifier()));

        $this->assertFalse($bundle->equals($identity));
    }

    /**
     * @covers ::equals()
     */
    public function testNotEqualsIdentifier()
    {
        $bundle = $this->getBundle();
        $identity = $this->getMockBuilder(ObjectIdentityInterface::class)
            ->setMethods(['getType', 'getIdentifier', 'equals'])
            ->getMock();

        $identity->expects($this->any())
                ->method('getType')
                ->will($this->returnValue($bundle->getType()));
        $identity->expects($this->any())
                ->method('getIdentifier')
                ->will($this->returnValue('otherId'));

        $this->assertFalse($bundle->equals($identity));
    }

    /**
     * @covers            ::initBundleExposedActions()
     * @expectedException \InvalidArgumentException
     */
    public function testInitBundleExposedActionsWithUnknownController()
    {
        $bundle = $this->getBundle();
        $bundle->getConfig()->setSection(
            'bundle',
            ['enable' => true, 'exposed_actions' => ['controller.id' => ['action']]]
        );

        $this->invokeMethod($bundle, 'initBundleExposedActions');
    }

    /**
     * @covers ::initBundleExposedActions()
     */
    public function testInitBundleExposedActions()
    {
        $this->container->set('controller.id', new \stdClass());

        $bundle = $this->getBundle();
        $bundle->getConfig()->setSection(
            'bundle',
            ['enable' => true, 'exposed_actions' => ['controller.id' => ['action']]]
        );

        $this->assertNull($this->invokeMethod($bundle, 'initBundleExposedActions'));
    }

    /**
     * @covers ::formatAndInjectExposedAction()
     * @covers ::getExposedActionCallback()
     */
    public function testFormatAndInjectExposedAction()
    {
        $bundle = $this->getBundle();
        $controller = $this->getMockBuilder(BundleExposedControllerInterface::class)
                        ->setMethods(['indexAction', 'getLabel', 'getDescription'])
                        ->getMock();
        $controller->expects($this->once())
                ->method('getLabel')
                ->will($this->returnValue('label'));
        $controller->expects($this->once())
                ->method('getDescription')
                ->will($this->returnValue('desc'));

        $this->invokeMethod($bundle, 'formatAndInjectExposedAction', [$controller, []]);

        $uniqueName = strtolower(str_replace('Controller', '', get_class($controller)));
        $expectedMapping = [
            $uniqueName => [
                'actions' => ['index'],
                'label' => 'label',
                'description' => 'desc'
            ]
        ];

        $expectedCallback = [$controller, 'indexAction'];

        $this->assertEquals($expectedMapping, $bundle->getExposedActionsMapping());
        $this->assertEquals($expectedCallback, $bundle->getExposedActionCallback($uniqueName, 'index'));
        $this->assertNull($bundle->getExposedActionCallback($uniqueName, 'save'));
    }
}
