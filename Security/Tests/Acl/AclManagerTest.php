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

namespace BackBee\Security\Tests;

use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

use BackBee\Security\Acl\AclManager;
use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Security\Acl\Permission\PermissionMap;
use BackBee\Security\SecurityContext;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Test suite for AclManager.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Acl\AclManager
 */
class AclManagerTest extends \PHPUnit_Framework_TestCase
{
    use InvokeMethodTrait;

    /**
     * @var MutableAclProvider
     */
    private $aclProvider;

    /**
     * @var AclManager
     */
    private $manager;

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

        $this->manager = new AclManager(
            $this->aclProvider,
            new PermissionMap()
        );
    }

    /**
     * @covers ::__construct()
     */
    public function testOldDefinitionConstruct()
    {
        $aclProvider = $this->getMockBuilder(MutableAclProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAclProvider'])
            ->getMock();

        $context->expects($this->once())
            ->method('getAclProvider')
            ->willReturn($aclProvider);

        new AclManager($context, new PermissionMap());
    }

    /**
     * @covers            ::__construct()
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstruct()
    {
        new AclManager(new \stdClass(), new PermissionMap());
    }

    /**
     * @covers ::getAcl()
     */
    public function testGetExistingAcl()
    {
        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->will($this->throwException(new AclAlreadyExistsException()));
        $this->aclProvider
            ->expects($this->once())
            ->method('findAcl');

        $this->assertNull($this->manager->getAcl(new ObjectIdentity('identifier', 'type')));
    }

    /**
     * @covers ::getAcl()
     */
    public function testGetAcl()
    {
        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl');
        $this->aclProvider
            ->expects($this->never())
            ->method('findAcl');

        $this->assertNull($this->manager->getAcl(new ObjectIdentity('identifier', 'type')));
    }

    /**
     * @covers ::updateObjectAce()
     * @covers ::insertOrUpdateObjectAce()
     */
    public function testUpdateObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->updateObjectAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
        $this->assertEquals(MaskBuilder::MASK_EDIT, $acl->getObjectAces()[0]->getMask());
    }

    /**
     * @covers ::updateObjectAce()
     * @covers ::insertOrUpdateObjectAce()
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateUnknownObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->manager->updateObjectAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
    }

    /**
     * @covers ::updateClassAce()
     * @covers ::insertOrUpdateClassAce()
     */
    public function testUpdateClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertClassAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->updateClassAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
        $this->assertEquals(MaskBuilder::MASK_EDIT, $acl->getClassAces()[0]->getMask());
    }

    /**
     * @covers ::updateClassAce()
     * @covers ::insertOrUpdateClassAce()
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateUnknownClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->manager->updateClassAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
    }

    /**
     * @covers ::insertOrUpdateObjectAce()
     */
    public function testInsertOrUpdateObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->insertOrUpdateObjectAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
        $this->assertEquals(MaskBuilder::MASK_EDIT, $acl->getObjectAces()[0]->getMask());
    }

    /**
     * @covers ::insertOrUpdateObjectAce()
     */
    public function testInsertOrUpdateUnknownObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->insertOrUpdateObjectAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
        $this->assertEquals(MaskBuilder::MASK_EDIT, $acl->getObjectAces()[0]->getMask());
    }

    /**
     * @covers ::insertOrUpdateClassAce()
     */
    public function testInsertOrUpdateClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertClassAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->insertOrUpdateClassAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
        $this->assertEquals(MaskBuilder::MASK_EDIT, $acl->getClassAces()[0]->getMask());
    }

    /**
     * @covers ::insertOrUpdateClassAce()
     */
    public function testInsertOrUpdateUnknownClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->insertOrUpdateClassAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_EDIT);
        $this->assertEquals(MaskBuilder::MASK_EDIT, $acl->getClassAces()[0]->getMask());
    }

    /**
     * @covers ::deleteClassAce()
     */
    public function testDeleteClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertClassAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->deleteClassAce($objectIdentity, $securityIdentity);
        $this->assertEmpty($acl->getClassAces());
    }

    /**
     * @covers            ::deleteClassAce()
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteUnknownClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->manager->deleteClassAce($objectIdentity, $securityIdentity);
    }

    /**
     * @covers ::deleteObjectAce()
     */
    public function testDeleteObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->aclProvider
            ->expects($this->once())
            ->method('updateAcl');

        $this->manager->deleteObjectAce($objectIdentity, $securityIdentity);
        $this->assertEmpty($acl->getClassAces());
    }

    /**
     * @covers            ::deleteObjectAce()
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteUnknownObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->manager->deleteObjectAce($objectIdentity, $securityIdentity);
    }

    /**
     * @covers ::getClassAce()
     */
    public function testGetClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertClassAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->assertEquals($acl->getClassAces()[0], $this->manager->getClassAce($objectIdentity, $securityIdentity));
    }

    /**
     * @covers            ::getClassAce()
     * @expectedException \InvalidArgumentException
     */
    public function testGetUnknownClassAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->manager->getClassAce($objectIdentity, $securityIdentity);
    }

    /**
     * @covers ::getObjectAce()
     */
    public function testGetObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_VIEW);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->assertEquals($acl->getObjectAces()[0], $this->manager->getObjectAce($objectIdentity, $securityIdentity));
    }

    /**
     * @covers            ::getObjectAce()
     * @expectedException \InvalidArgumentException
     */
    public function testGetUnknownObjectAce()
    {
        $objectIdentity = new ObjectIdentity('identifier', 'type');
        $strategy = new PermissionGrantingStrategy();
        $securityIdentity = new UserSecurityIdentity('username', 'class');
        $acl = new Acl(1, $objectIdentity, $strategy, [$securityIdentity], false);

        $this->aclProvider
            ->expects($this->once())
            ->method('createAcl')
            ->willReturn($acl);

        $this->manager->getObjectAce($objectIdentity, $securityIdentity);
    }

    /**
     * @covers ::getMask()
     */
    public function testGetMask()
    {
        $this->assertEquals(
            MaskBuilder::MASK_EDIT + MaskBuilder::MASK_CREATE,
            $this->manager->getMask(['EDIT', 'CREATE'])
        );
    }

    /**
     * @covers            ::getMask()
     * @expectedException \BackBee\Security\Acl\Permission\InvalidPermissionException
     */
    public function testGetUnknownMask()
    {
        $this->manager->getMask(['EDIT', 'CREATE', 'UNKNOWN']);
    }

    /**
     * @covers ::getPermissionCodes()
     */
    public function testGetPermissionCodes()
    {
        $expected = [
            'view' => MaskBuilder::MASK_VIEW,
            'create' => MaskBuilder::MASK_CREATE,
            'edit' => MaskBuilder::MASK_EDIT,
            'delete' => MaskBuilder::MASK_DELETE,
            'undelete' => MaskBuilder::MASK_UNDELETE,
            'operator' => MaskBuilder::MASK_OPERATOR,
            'master' => MaskBuilder::MASK_MASTER,
            'owner' => MaskBuilder::MASK_OWNER,
            'iddqd' => MaskBuilder::MASK_IDDQD,
            'commit' => MaskBuilder::MASK_COMMIT,
            'publish' => MaskBuilder::MASK_PUBLISH,
        ];

        $this->assertEquals($expected, $this->manager->getPermissionCodes());
    }

    /**
     * @covers ::enforceObjectIdentity()
     */
    public function testEnforceObjectIdentity()
    {
        $identity = $this->getMockForAbstractClass(
            AbstractObjectIdentifiable::class,
            [],
            '',
            false,
            false,
            false,
            ['getUid']
        );

        $identity->expects($this->once())->method('getUid');
        $this->invokeMethod($this->manager, 'enforceObjectIdentity', [&$identity]);
        $this->assertInstanceOf(ObjectIdentity::class, $identity);
    }

    /**
     * @covers            ::enforceObjectIdentity()
     * @expectedException \InvalidArgumentException
     */
    public function testEnforceBdObjectIdentity()
    {
        $identity = new \stdClass();
        $this->invokeMethod($this->manager, 'enforceObjectIdentity', [&$identity]);
    }

    /**
     * @covers ::enforceSecurityIdentity()
     */
    public function testEnforceSecurityIdentity()
    {
        $sid = $this->getMockForAbstractClass(
            DomainObjectInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getObjectIdentifier']
        );

        $sid->expects($this->once())->method('getObjectIdentifier')->willReturn('username');
        $this->invokeMethod($this->manager, 'enforceSecurityIdentity', [&$sid]);
        $this->assertInstanceOf(UserSecurityIdentity::class, $sid);
    }

    /**
     * @covers            ::enforceSecurityIdentity()
     * @expectedException \InvalidArgumentException
     */
    public function testEnforceBadSecurityIdentity()
    {
        $sid = new \stdClass();
        $this->invokeMethod($this->manager, 'enforceSecurityIdentity', [&$sid]);
    }

    /**
     * @covers ::resolveMask()
     */
    public function testResolveMask()
    {
        $this->assertEquals(1, $this->invokeMethod($this->manager, 'resolveMask', [1, new \stdClass()]));
        $this->assertEquals(229, $this->invokeMethod($this->manager, 'resolveMask', ['VIEW', new \stdClass()]));
        $this->assertEquals(230, $this->invokeMethod($this->manager, 'resolveMask', [[1, 'VIEW'], new \stdClass()]));
    }

    /**
     * @covers ::resolveMask()
     * @expectedException \RuntimeException
     */
    public function testResolveInvalidMask()
    {
        $this->invokeMethod($this->manager, 'resolveMask', [new \stdClass(), new \stdClass()]);
    }
}
