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

namespace BackBee\Security\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;

use BackBee\BBApplication;
use BackBee\Config\Config;
use BackBee\Routing\Matcher\RequestMatcher;
use BackBee\Security\Listeners\LogoutListener;
use BackBee\Security\SecurityContext;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class SecurityContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\SecurityContext
 */
class SecurityContextTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var SecurityContext
     */
    private $context;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var BBApplication
     */
    private $application;

    /**
     * @var EventDispatherInterface
     */
    private $dispatcher;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->container = new Container();

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSecurityConfig'])
            ->getMock();
        $config->expects($this->any())
            ->method('getSecurityConfig')
            ->willReturn(['contexts' => ['BackBee\Security\Context' => ['AnonymousContext']]]);

        $authenticationManager = $this->getMockForAbstractClass(
            AuthenticationManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->container->set('security.authentication.manager', $authenticationManager);

        $this->dispatcher = $this->getMockForAbstractClass(
            EventDispatcherInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isRestored', 'addListener']
        );

        $this->application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLogging', 'getEventDispatcher', 'getConfig', 'getContainer', 'getEntityManager'])
            ->getMock();
        $this->application->expects($this->any())->method('getEventDispatcher')->willReturn($this->dispatcher);
        $this->application->expects($this->any())->method('getConfig')->willReturn($config);
        $this->application->expects($this->any())->method('getContainer')->willReturn($this->container);

        $this->tokenStorage = $this->getMockForAbstractClass(
            TokenStorageInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getToken', 'setToken']
        );
        $this->authorizationChecker = $this->getMockForAbstractClass(
            AuthorizationCheckerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isGranted']
        );

        $this->context = new SecurityContext(
            $this->application,
            $this->tokenStorage,
            $this->authorizationChecker
        );
    }

    /**
     * @covers ::__construct()
     */
    public function testOldConstruct()
    {
        $authenticationManager = $this->getMockForAbstractClass(
            AuthenticationManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $decisionManager = $this->getMockForAbstractClass(
            AccessDecisionManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $context = new SecurityContext($this->application, $authenticationManager, $decisionManager);

        $this->assertInstanceOf(
            TokenStorageInterface::class,
            $this->invokeProperty($context, 'tokenStorage')
        );
        $this->assertInstanceOf(
            AuthorizationCheckerInterface::class,
            $this->invokeProperty($context, 'authorizationChecker')
        );
    }

    /**
     * @covers            ::__construct()
     * @expectedException \BadMethodCallException
     */
    public function testInvalidConstruct()
    {
        new SecurityContext($this->application, new \stdClass(), new \stdClass());
    }

    /**
     * @covers ::createFirewall()
     * @covers ::getRequestMatcher()
     * @covers ::initContexts()
     * @covers ::loadContexts()
     */
    public function testCreateFirewall()
    {
        $map = $this->getMockBuilder(FirewallMap::class)
                ->disableOriginalConstructor()
                ->setMethods(['add'])
                ->getMock();
        $map->expects($this->once())->method('add')->with(
            $this->isInstanceOf(RequestMatcher::class),
            $this->isType('array'),
            $this->isNull()
        );

        $config = [
            'pattern' => '/',
            'requirements' => ['HTTP-X-API-SIGNATURE' => '\w+'],
            'anonymous' => ''
        ];

        $this->invokeProperty($this->context, 'firewallmap', $map);
        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createFirewall', ['name', $config])
        );
    }

    /**
     * @covers ::createFirewall()
     */
    public function testCreateFirewallNoSecure()
    {
        $map = $this->getMockBuilder(FirewallMap::class)
                ->disableOriginalConstructor()
                ->setMethods(['add'])
                ->getMock();
        $map->expects($this->once())->method('add')->with(
            $this->isInstanceOf(RequestMatcher::class),
            $this->isEmpty(),
            $this->isNull()
        );

        $config = [
            'security' => false
        ];

        $this->invokeProperty($this->context, 'firewallmap', $map);
        $this->invokeMethod($this->context, 'createFirewall', ['name', $config]);
    }

    /**
     * @covers            ::createFirewall()
     * @expectedException \BackBee\Security\Exception\SecurityException
     */
    public function testCreateInvalidFirewall()
    {
        $this->invokeMethod($this->context, 'createFirewall', ['name', []]);
    }

    /**
     * @covers ::createEncoderFactory()
     * @covers ::getEncoderFactory()
     */
    public function testEncoderFactory()
    {
        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createEncoderFactory', [['encoders' => ['encoder']]])
        );
        $this->assertInstanceOf(EncoderFactoryInterface::class, $this->context->getEncoderFactory());
    }

    /**
     * @covers ::createProviders()
     * @covers ::getUserProviders()
     */
    public function testEmptyProviders()
    {
        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createProviders', [[]])
        );
        $this->assertEmpty($this->context->getUserProviders());
    }

    /**
     * @covers ::addUserProvider()
     */
    public function testAddUserProvider()
    {
        $provider = $this->getMockForAbstractClass(UserProviderInterface::class);
        $this->context->addUserProvider('mock', $provider);

        $this->assertEquals($provider, $this->context->getUserProviders()['mock']);
    }

    /**
     * @covers ::createProviders()
     * @covers ::addWebserviceProvider()
     */
    public function testWebserviceProviders()
    {
        $config = [
            'providers' => [
                'name' => [
                    'webservice' => [
                        'class' => 'stdClass'
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createProviders', [$config])
        );
        $this->assertInstanceOf('\stdClass', $this->context->getUserProviders()['name']);
    }

    /**
     * @covers ::createProviders()
     * @covers ::addEntityProvider()
     */
    public function testEntityProviders()
    {
        $config = [
            'providers' => [
                'name' => [
                    'entity' => [
                        'manager_name' => 'default',
                        'class' => 'UserClass',
                    ]
                ]
            ]
        ];

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();
        $entityMng->expects($this->any())->method('getRepository')->willReturn($repository);
        $this->application->expects($this->any())->method('getEntityManager')->willReturn($entityMng);

        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createProviders', [$config])
        );
        $this->assertEquals($repository, $this->context->getUserProviders()['name']);

        unset($config['providers']['name']['entity']['manager_name']);
        $config['providers']['name']['entity']['provider'] = 'stdClass';
        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createProviders', [$config])
        );
        $this->assertInstanceOf('\stdClass', $this->context->getUserProviders()['name']);
    }

    /**
     * @covers ::createACLProvider()
     * @covers ::getACLProvider()
     */
    public function testCreateACLProvider()
    {
        $config = [
            'acl' => [
                'connection' => 'default'
            ]
        ];

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();
        $entityMng->expects($this->once())->method('getConnection')->willReturn($connection);
        $this->application->expects($this->any())->method('getEntityManager')->willReturn($entityMng);

        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createACLProvider', [$config])
        );
        $this->assertInstanceOf(MutableAclProvider::class, $this->context->getACLProvider());

        $this->container->set('security.acl_provider', 'Acl Provider');
        $this->invokeMethod($this->context, 'createACLProvider', [$config]);
        $this->assertEquals('Acl Provider', $this->context->getACLProvider());
    }

    /**
     * @covers ::createFirewallMap()
     */
    public function testCreateFirewallMap()
    {
        $this->assertEquals(
            $this->context,
            $this->invokeMethod($this->context, 'createFirewallMap', [[]])
        );
        $this->assertInstanceOf(FirewallMap::class, $this->invokeProperty($this->context, 'firewallmap'));

        $context = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['createFirewall'])
            ->getMock();

        $config = [
            'firewalls' => [
                'name' > ['firewalldesc']
            ]
        ];

        $context->expects($this->once())
            ->method('createFirewall');

        $this->assertEquals(
            $context,
            $this->invokeMethod($context, 'createFirewallMap', [$config])
        );
    }

    public function testAddFirewall()
    {
        $map = $this->getMockBuilder(FirewallMap::class)
            ->setMethods(['add'])
            ->getMock();
        $map->expects($this->once())->method('add');
        $this->invokeProperty($this->context, 'firewallmap', $map);
        $this->context->addFirewall($this->getMockForAbstractClass(RequestMatcherInterface::class), []);
    }

    /**
     * @covers ::registerFirewall()
     */
    public function testRegisterFirewall()
    {
        $this->invokeMethod($this->context, 'createFirewallMap', [[]]);
        $this->invokeMethod($this->context, 'registerFirewall', [[]]);

        $this->assertInstanceOf(Firewall::class, $this->container->get('security.firewall'));
    }

    /**
     * @covers            ::addAuthProvider
     * @covers            ::getAuthProvider
     * @expectedException \InvalidArgumentException
     */
    public function testAuthProvider()
    {
        $provider = $this->getMockForAbstractClass(AuthenticationProviderInterface::class);
        $this->context->addAuthProvider($provider);
        $this->context->addAuthProvider($provider, 'provider');

        $this->assertEquals($provider, $this->context->getAuthProvider(0));
        $this->assertEquals($provider, $this->context->getAuthProvider('provider'));

        $this->context->getAuthProvider('unknown');
    }

    /**
     * @covers ::getApplication()
     */
    public function testGetApplication()
    {
        $this->assertEquals($this->application, $this->context->getApplication());
    }

    /**
     * @covers ::getAuthenticationManager()
     */
    public function testGetAuthenticationManager()
    {
        $this->assertEquals(
            $this->container->get('security.authentication.manager'),
            $this->context->getAuthenticationManager()
        );
    }

    /**
     * @covers ::getLogger()
     */
    public function testGetLogger()
    {
        $this->application->expects($this->once())->method('getLogging');
        $this->context->getLogger();
    }

    /**
     * @covers ::setLogoutListener()
     * @covers ::getLogoutListener()
     */
    public function testLogoutListener()
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('isRestored')
            ->willReturn(false);

        $this->dispatcher
            ->expects($this->once())
            ->method('addListener');

        $listener = $this->getMockBuilder(LogoutListener::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->context->setLogoutListener($listener);
        $this->assertEquals($listener, $this->context->getLogoutListener());
    }

    /**
     * @covers ::getDispatcher()
     */
    public function testGetDispatcher()
    {
        $this->application->expects($this->once())->method('getEventDispatcher');
        $this->context->getDispatcher();
    }

    /**
     * @covers ::setToken()
     * @covers ::getToken()
     */
    public function testToken()
    {
        $this->tokenStorage->expects($this->once())->method('setToken');
        $this->tokenStorage->expects($this->once())->method('getToken');

        $this->context->setToken();
        $this->context->getToken();
    }

    /**
     * @covers ::isGranted()
     */
    public function testIsGranted()
    {
        $this->authorizationChecker->expects($this->once())->method('isGranted');
        $this->context->isGranted('attributes');
    }
}
