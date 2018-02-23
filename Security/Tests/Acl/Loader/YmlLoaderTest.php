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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

use BackBee\ApplicationInterface;
use BackBee\Bundle\AbstractBundle;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\ContentSet;
use BackBee\Security\Acl\Loader\YmlLoader;
use BackBee\Security\Group;
use BackBee\Security\SecurityContext;
use BackBee\Site\Site;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Test for YmlLoader class
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Security\Acl\Loader\YmlLoader
 */
class YmlLoaderTest extends \PHPUnit_Framework_TestCase
{
    use InvokeMethodTrait;

    /**
     * @var YmlLoader
     */
    private $loader;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var MutableAclProvider
     */
    private $aclProvider;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->aclProvider = $this->getMockBuilder(MutableAclProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['createAcl', 'findAcl', 'updateAcl'])
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'persist', 'flush'])
            ->getMock();

        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getACLProvider'])
            ->getMock();

        $securityContext->expects($this->any())
            ->method('getACLProvider')
            ->willReturn($this->aclProvider);

        $definition = new Definition();
        $bundle = $this->getMockBuilder(AbstractBundle::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inlinedDefinition = new \SplObjectStorage();

        $this->container = new ContainerBuilder();
        $this->container->set('em', $entityManager);
        $this->container->set('security.context', $securityContext);
        $this->container->set('bundle.mock', $bundle);
        $this->container->setDefinition('bundle.mock', new Definition())
            ->addTag('bundle', ['dispatch_event' => false]);

        $this->loader = new YmlLoader();
        $this->loader->setContainer($this->container);
    }

    /**
     * @covers                   ::load()
     * @covers                   ::initialize()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Services container missing
     */
    public function testLoadWithoutContainer()
    {
        $loader = new YmlLoader();
        $loader->load('[]');
    }

    /**
     * @covers                   ::load()
     * @covers                   ::initialize()
     * @covers                   ::getService()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Entity manager missing
     */
    public function testLoadWithoutEntityManager()
    {
        $loader = new YmlLoader();
        $loader->setContainer(new ContainerBuilder());
        $loader->load('[]');
    }

    /**
     * @covers                   ::load()
     * @covers                   ::initialize()
     * @covers                   ::getService()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Security context missing
     */
    public function testLoadWithoutSecurityContext()
    {
        $container = new ContainerBuilder();
        $container->set('em', 'em');

        $loader = new YmlLoader();
        $loader->setContainer($container);
        $loader->load('[]');
    }

    /**
     * @covers                   ::load()
     * @covers                   ::initialize()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage ACL provider missing
     */
    public function testLoadWithoutACLProvider()
    {
        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = new ContainerBuilder();
        $container->set('em', 'em');
        $container->set('security.context', $securityContext);

        $loader = new YmlLoader();
        $loader->setContainer($container);
        $loader->load('[]');
    }

    /**
     * @covers            ::load()
     * @expectedException Symfony\Component\Yaml\Exception\ParseException
     */
    public function testLoadUnparsableContent()
    {
        $this->loader->load('"');
    }

    /**
     * @covers                   ::loadFromFile()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Cannot read file
     */
    public function testLoadFromUnreadableFile()
    {
        $this->loader->loadFromFile(__DIR__ . DIRECTORY_SEPARATOR . 'unknown.yml');
    }

    /**
     * @covers ::initialize()
     * @covers ::loadFromFile()
     * @covers ::load()
     * @covers ::updateRights()
     * @covers ::addSitesRights()
     * @covers ::addLayoutsRights()
     * @covers ::addPagesRights()
     * @covers ::addMediafoldersRights()
     * @covers ::addGroupsRights()
     * @covers ::addUsersRights()
     *
     * @return void
     */
    public function testLoadYml()
    {
        $group = new Group();
        $group->setId(1);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $repository->expects($this->at(0))->method('findOneBy')->willReturn($group);
        $this->container->get('em')->expects($this->any())->method('getRepository')->willReturn($repository);
        $acl = $this->getMockForAbstractClass(MutableAclInterface::class, [], '', false, false, true, ['getObjectAces']);
        $acl->expects($this->any())->method('getObjectAces')->willReturn([]);
        $this->aclProvider->expects($this->any())->method('createAcl')->willReturn($acl);

        $this->loader->loadFromFile(__DIR__ . DIRECTORY_SEPARATOR . 'acl.yml');
    }

    /**
     * @covers ::getLoadedBundles()
     */
    public function testGetLoadedBundles()
    {
        $bundles = $this->invokeMethod($this->loader, 'getLoadedBundles');

        $this->assertTrue(isset($bundles['bundle.mock']));
        $this->assertInstanceOf(AbstractBundle::class, $bundles['bundle.mock']);
    }

    /**
     * @covers ::getActionsOnResources()
     * @covers ::getActions()
     */
    public function testGetActionsOnResources()
    {
        $config = [
            'resources' => ['res1', '!res2', 'res3'],
            'actions' => ['view', 'create']
        ];

        $this->assertEquals(
            ['res1' => ['view', 'create'], 'res2' => [], 'res3' => ['view', 'create']],
            $this->invokeMethod($this->loader, 'getActionsOnResources', [$config])
        );

        $configAll = [
            'resources' => 'res1',
            'actions' => 'all'
        ];

        $this->assertEquals(
            ['res1' => ['view', 'create', 'edit', 'delete', 'publish']],
            $this->invokeMethod($this->loader, 'getActionsOnResources', [$configAll])
        );
    }

    /**
     * @covers ::addObjectRights()
     */
    public function testAddObjectRights()
    {
        $configAll = [
            'resources' => 'all',
            'actions' => 'all'
        ];

        $configOne = [
            'resources' => 'uid',
            'actions' => 'all'
        ];

        $configNone = [
            'resources' => 'Unknown',
            'actions' => 'all'
        ];

        $userSecurityIdentity = new UserSecurityIdentity('id', 'user');

        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->container->get('em')->expects($this->at(1))->method('find')->willReturn(new Site('uid'));
        $this->container->get('em')->expects($this->at(2))->method('find')->willReturn(null);
        $this->container->get('em')->expects($this->exactly(2))->method('getRepository')->willReturn($repository);
        $acl = $this->getMockForAbstractClass(MutableAclInterface::class, [], '', false, false, true, ['getObjectAces']);
        $acl->expects($this->exactly(2))->method('getObjectAces')->willReturn([]);
        $acl->expects($this->once())->method('insertClassAce')->with($userSecurityIdentity);
        $acl->expects($this->once())->method('insertObjectAce')->with($userSecurityIdentity);
        $this->aclProvider->expects($this->exactly(2))->method('createAcl')->willReturn($acl);
        $this->aclProvider->expects($this->exactly(2))->method('updateAcl')->with($acl);

        $this->invokeMethod($this->loader, 'initialize');
        $this->invokeMethod($this->loader, 'addObjectRights', [Site::class, $configAll, $userSecurityIdentity]);
        $this->invokeMethod($this->loader, 'addObjectRights', [Site::class, $configOne, $userSecurityIdentity]);
        $this->invokeMethod($this->loader, 'addObjectRights', [Site::class, $configNone, $userSecurityIdentity]);
    }

    /**
     * @covers ::addBundlesRights()
     * @covers ::addObjectAcl()
     */
    public function testAddBundlesRights()
    {
        $configAll = [
            'resources' => 'all',
            'actions' => 'all'
        ];

        $configOne = [
            'resources' => 'mock',
            'actions' => 'all'
        ];

        $configNone = [
            'resources' => 'unknown',
            'actions' => 'all'
        ];

        $objectIdentity = ObjectIdentity::fromDomainObject($this->container->get('bundle.mock'));
        $userSecurityIdentity = new UserSecurityIdentity('id', 'user');

        $acl = $this->getMockForAbstractClass(MutableAclInterface::class, [], '', false, false, true, ['getObjectAces']);
        $acl->expects($this->exactly(2))->method('getObjectAces')->willReturn([]);
        $acl->expects($this->exactly(2))->method('insertObjectAce')->with($userSecurityIdentity);
        $this->aclProvider->expects($this->exactly(2))->method('createAcl')->with($objectIdentity)->willReturn($acl);
        $this->aclProvider->expects($this->exactly(2))->method('updateAcl')->with($acl);

        $this->invokeMethod($this->loader, 'initialize');
        $this->invokeMethod($this->loader, 'addBundlesRights', [$configAll, $userSecurityIdentity]);
        $this->invokeMethod($this->loader, 'addBundlesRights', [$configOne, $userSecurityIdentity]);
        $this->invokeMethod($this->loader, 'addBundlesRights', [$configNone, $userSecurityIdentity]);
    }

    /**
     * @covers ::addContentsRights()
     * @covers ::addClassAcl()
     * @covers ::addAcl()
     */
    public function testAddContentsRights()
    {
        $configAll = [
            'resources' => 'all',
            'actions' => 'all'
        ];

        $configOne = [
            'resources' => 'ContentSet',
            'actions' => 'all'
        ];

        $configNone = [
            'resources' => 'Unknown',
            'actions' => 'all'
        ];

        $userSecurityIdentity = new UserSecurityIdentity('id', 'user');

        $acl = $this->getMockForAbstractClass(MutableAclInterface::class, [], '', false, false, true, ['getObjectAces']);
        $acl->expects($this->exactly(2))->method('getObjectAces')->willReturn([]);
        $acl->expects($this->exactly(2))->method('insertClassAce')->with($userSecurityIdentity);
        $this->aclProvider->expects($this->exactly(2))->method('createAcl')->willReturn($acl);
        $this->aclProvider->expects($this->exactly(2))->method('updateAcl')->with($acl);

        $this->invokeMethod($this->loader, 'initialize');
        $this->invokeMethod($this->loader, 'addContentsRights', [$configAll, $userSecurityIdentity]);
        $this->invokeMethod($this->loader, 'addContentsRights', [$configOne, $userSecurityIdentity]);
        $this->invokeMethod($this->loader, 'addContentsRights', [$configNone, $userSecurityIdentity]);
    }

    /**
     * @covers ::addAcl()
     */
    public function testAddAcl()
    {
        $rights = ['view', 'create'];
        $objectIdentity = new ObjectIdentity('id', 'object');
        $userSecurityIdentity = new UserSecurityIdentity('id', 'user');

        $expectedMask = new Maskbuilder();
        $expectedMask->add('view')->add('create');

        $ace = $this->getMockForAbstractClass(EntryInterface::class, [], '', false, false, true, ['getSecurityIdentity', 'getMask']);
        $acl = $this->getMockForAbstractClass(MutableAclInterface::class, [], '', false, false, true, ['getObjectAces', 'updateObjectAce', 'insertObjectAce']);

        $ace->expects($this->once())->method('getSecurityIdentity')->willReturn($userSecurityIdentity);
        $ace->expects($this->once())->method('getMask')->willReturn(0);
        $acl->expects($this->once())->method('getObjectAces')->willReturn([$ace]);
        $acl->expects($this->once())->method('updateObjectAce')->with(0, 0 & ~$expectedMask->get());
        $acl->expects($this->once())->method('insertObjectAce')->with($userSecurityIdentity, $expectedMask->get());
        $this->aclProvider->expects($this->once())->method('createAcl')->willThrowException(new \Exception);
        $this->aclProvider->expects($this->once())->method('findAcl')->with($objectIdentity)->willReturn($acl);
        $this->aclProvider->expects($this->once())->method('updateAcl')->with($acl);

        $this->invokeMethod($this->loader, 'initialize');
        $this->invokeMethod($this->loader, 'addAcl', [$objectIdentity, $userSecurityIdentity, $rights]);
    }
}
