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

namespace BackBee\Security\Tests\Context;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\ApplicationInterface;
use BackBee\Security\Context\RestfulContext;
use BackBee\Security\Listeners\PublicKeyAuthenticationListener;
use BackBee\Security\SecurityContext;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Util\Registry\Repository;

/**
 * Test suite for class RestfulContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Context\RestfulContext
 */
class RestFulContextTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;

    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @var RestfulContext
     */
    private $context;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getApplication',
                'getUserProviders',
                'getAuthenticationManager',
                'getEncoderFactory',
                'getLogger'])
            ->getMock();

        $application = $this->getMockForAbstractClass(
            ApplicationInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getCacheDir', 'getEntityManager', 'getContainer', 'getEventDispatcher']
        );

        $container = new Container();
        $dispatcher = $this->getMockForAbstractClass(
            EventDispatcherInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isRestored']
        );
        $application->expects($this->any())->method('getContainer')->willReturn($container);
        $application->expects($this->any())->method('getEventDispatcher')->willReturn($dispatcher);
        $this->securityContext->expects($this->any())->method('getApplication')->willReturn($application);

        $this->context = new RestfulContext($this->securityContext);
    }

    /**
     * @covers ::loadListeners()
     * @covers ::loadLogoutListener()
     */
    public function testLoadListeners()
    {
        $this->assertEquals([], $this->context->loadListeners([]));

        $application = $this->securityContext->getApplication();
        $application->expects($this->any())->method('getCacheDir')->willReturn(dirname(__DIR__));

        $container = $application->getContainer();
        $container->setParameter('bbapp.securitycontext.role.apiuser', 'api-role');
        $container->setParameter('bbapp.securitycontext.roles.prefix', 'prefix');

        $provider = $this->getMockForAbstractClass(UserProviderInterface::class);
        $this->securityContext->expects($this->once())->method('getUserProviders')->willReturn([$provider]);

        $manager = $this->getMockForAbstractClass(
            AuthenticationManagerInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['addProvider']
        );
        $manager->expects($this->any())->method('addProvider')->willReturn($manager);
        $this->securityContext->expects($this->any())->method('getAuthenticationManager')->willReturn($manager);

        $listeners = $this->context->loadListeners(['restful' => ['nonce_dir' => basename(__DIR__)]]);
        $this->assertInstanceOf(PublicKeyAuthenticationListener::class, $listeners[0]);
    }

    /**
     * @covers ::getApiUserRole()
     */
    public function testGetApiUserRole()
    {
        $this->assertNull($this->invokeMethod($this->context, 'getApiUserRole'));

        $application = $this->securityContext->getApplication();

        $container = $application->getContainer();
        $container->setParameter('bbapp.securitycontext.role.apiuser', 'api-role');
        $container->setParameter('bbapp.securitycontext.roles.prefix', 'prefix');

        $this->assertEquals('prefixapi-role', $this->invokeMethod($this->context, 'getApiUserRole'));
    }

    /**
     * @covers ::getNonceDirectory()
     */
    public function testGetNonceDirectory()
    {
        $application = $this->securityContext->getApplication();
        $application->expects($this->once())->method('getCacheDir')->willReturn(dirname(__DIR__));

        $this->assertEquals(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'test',
            $this->invokeMethod($this->context, 'getNonceDirectory', [['nonce_dir' => 'test']])
        );
    }

    /**
     * @covers ::getRegistryRepository()
     */
    public function testGetRegistryRepository()
    {
        $entityMng = $this->getMockBuilder(EntityManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['getRepository'])
                ->getMock();

        $repository = $this->getMockBuilder(Repository::class)
                ->disableOriginalConstructor()
                ->getMock();

        $application = $this->securityContext->getApplication();
        $application->expects($this->once())->method('getEntityManager')->willReturn($entityMng);
        $entityMng->expects($this->once())->method('getRepository')->willReturn($repository);

        $this->assertEquals($repository, $this->invokeMethod($this->context, 'getRegistryRepository'));
    }
}
