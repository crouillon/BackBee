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

use org\bovigo\vfs\vfsStream;
use Symfony\Component\EventDispatcher\EventDispatcher;

use BackBee\Bundle\AbstractBundle;
use BackBee\Bundle\BundleExposedControllerInterface;
use BackBee\Security\Acl\Domain\ObjectIdentifiableInterface;

/**
 * Tests suite for class AbstractBundle.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractBundleTest extends BundleTestCase
{

    /**
     * @covers BackBee\Bundle\AbstractBundle::__construct()
     * @covers BackBee\Bundle\AbstractBundle::getApplication()
     * @covers BackBee\Bundle\AbstractBundle::getBaseDirectory()
     * @covers BackBee\Bundle\AbstractBundle::getId()
     * @covers BackBee\Bundle\AbstractBundle::getExposedActionsMapping()
     * @covers BackBee\Bundle\AbstractBundle::isStarted()
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
     * @covers BackBee\Bundle\AbstractBundle::__construct()
     * @covers BackBee\Bundle\AbstractBundle::getId()
     * @covers BackBee\Bundle\AbstractBundle::getBaseDirectory()
     */
    public function testConstructWithoutId()
    {
        $bundle = $this->getBundle();

        $this->assertEquals('MockBundle', $bundle->getId());
        $this->assertEquals(vfsStream::url('MockBundle'), $bundle->getBaseDirectory());
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::getResourcesDirectory()
     */
    public function testGetResourcesDirectory()
    {
        $this->assertEquals(
            vfsStream::url('MockBundle') . DIRECTORY_SEPARATOR . 'Resources',
            $this->getBundle()->getResourcesDirectory()
        );
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::getProperty()
     */
    public function testGetProperty()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(['name' => 'mock'], $bundle->getProperty());
        $this->assertEquals('mock', $bundle->getProperty('name'));
        $this->assertNull($bundle->getProperty('unknown'));
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::getConfig()
     */
    public function testGetConfig()
    {
        $this->assertEquals(
            $this->container->get('bundle.mockbundle.config'),
            $this->getBundle()->getConfig()
        );
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::getConfigServiceId()
     */
    public function testGetConfigServiceId()
    {
        $this->assertEquals(
            'bundle.mockbundle.config',
            $this->getBundle()->getConfigServiceId()
        );
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::getConfigDirectory()
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
     * @covers BackBee\Bundle\AbstractBundle::isConfigPerSite()
     * @covers BackBee\Bundle\AbstractBundle::setConfigPerSite()
     */
    public function testConfigPerSite()
    {
        $bundle = $this->getBundle();

        $this->assertEquals(AbstractBundle::DEFAULT_CONFIG_PER_SITE_VALUE, $bundle->isConfigPerSite());
        $this->assertFalse($bundle->setConfigPerSite(false)->isConfigPerSite());
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::started()
     * @covers BackBee\Bundle\AbstractBundle::isStarted()
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
     * @covers BackBee\Bundle\AbstractBundle::isEnabled()
     * @covers BackBee\Bundle\AbstractBundle::setEnable()
     */
    public function testEnabled()
    {
        $bundle = $this->getBundle();

        $this->assertFalse($bundle->isEnabled());
        $this->assertTrue($bundle->setEnable(true)->isEnabled());
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::getCategory()
     * @covers BackBee\Bundle\AbstractBundle::setCategory()
     */
    public function testCategory()
    {
        $bundle = $this->getBundle();

        $this->assertEquals([], $bundle->getCategory());
        $this->assertEquals(['category'], $bundle->setCategory('category')->getCategory());
    }

    /**
     * @covers BackBee\Bundle\AbstractBundle::jsonSerialize()
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
     * @covers BackBee\Bundle\AbstractBundle::getObjectIdentifier()
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
     * @covers BackBee\Bundle\AbstractBundle::getIdentifier()
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
     * @covers BackBee\Bundle\AbstractBundle::getType()
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
     * @covers BackBee\Bundle\AbstractBundle::equals()
     */
    public function testEquals()
    {
        $bundle = $this->getBundle();
        $identity = $this->getMockBuilder(ObjectIdentifiableInterface::class)
            ->setMethods(['getType', 'getIdentifier', 'equals', 'getObjectIdentifier'])
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
     * @covers BackBee\Bundle\AbstractBundle::equals()
     */
    public function testNotEqualsType()
    {
        $bundle = $this->getBundle();
        $identity = $this->getMockBuilder(ObjectIdentifiableInterface::class)
            ->setMethods(['getType', 'getIdentifier', 'equals', 'getObjectIdentifier'])
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
     * @covers BackBee\Bundle\AbstractBundle::equals()
     */
    public function testNotEqualsIdentifier()
    {
        $bundle = $this->getBundle();
        $identity = $this->getMockBuilder(ObjectIdentifiableInterface::class)
            ->setMethods(['getType', 'getIdentifier', 'equals', 'getObjectIdentifier'])
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
     * @covers            BackBee\Bundle\AbstractBundle::initBundleExposedActions()
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
     * @covers BackBee\Bundle\AbstractBundle::initBundleExposedActions()
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
     * @covers BackBee\Bundle\AbstractBundle::formatAndInjectExposedAction()
     * @covers BackBee\Bundle\AbstractBundle::getExposedActionCallback()
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
