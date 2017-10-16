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

namespace BackBee\Security\Tests\Authorization\Voter;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use BackBee\Bundle\AbstractBundle;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\ContentSet;
use BackBee\NestedNode\Page;
use BackBee\NestedNode\Section;
use BackBee\Security\Authorization\Voter\BBAclVoter;
use BackBee\Security\Token\BBUserToken;
use BackBee\Tests\Traits\InvokeMethodTrait;

/**
 * Test suite for class BBAclVoter.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authorization\Voter\BBAclVoter
 */
class BBAclVoterTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;

    /**
     * @var AclProviderInterface
     */
    private $provider;

    /**
     * @var PermissionMapInterface
     */
    private $permissionMap;

    /**
     * @var ObjectIdentityRetrievalStrategyInterface
     */
    private $oidStrategy;

    /**
     * @var SecurityIdentityRetrievalStrategyInterface
     */
    private $sidStrategy;

    /**
     * @var BBAclVoter
     */
    private $aclVoter;

    /**
     * Sets up the fixture;
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->getMock(AclProviderInterface::class);
        $this->permissionMap = $this->getMock(PermissionMapInterface::class);
        $this->oidStrategy = $this->getMock(ObjectIdentityRetrievalStrategyInterface::class);
        $this->sidStrategy = $this->getMock(SecurityIdentityRetrievalStrategyInterface::class);
    }

    /**
     * @param  array $methods
     *
     * @return BBAclVoter
     */
    private function getVoterMock(array $methods)
    {
        return $this->getMock(
            BBAclVoter::class,
            $methods,
            [
                $this->provider,
                $this->oidStrategy,
                $this->sidStrategy,
                $this->permissionMap,
                null,
                true
            ]
        );
    }

    /**
     * @covers ::vote()
     */
    public function testVote()
    {
        $voter = $this->getVoterMock([
            'voteForPage',
            'voteForNestedNode',
            'voteForClassContent',
            'voteForObject'
        ]);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(new BBUserToken(), null, [])
        );

        $voter->expects($this->once())->method('voteForPage');
        $voter->vote(new BBUserToken(), new Page(), []);

        $voter->expects($this->once())->method('voteForNestedNode');
        $voter->vote(new BBUserToken(), new Section(), []);

        $voter->expects($this->once())->method('voteForClassContent');
        $voter->vote(new BBUserToken(), new ContentSet(), []);

        $voter->expects($this->once())->method('voteForObject');
        $voter->vote(new BBUserToken(), new \stdClass(), []);

        $bundle = $this->getMockForAbstractClass(AbstractBundle::class, [], '', false);
        $this->assertEquals(BBAclVoter::ACCESS_ABSTAIN, $voter->vote(new BBUserToken(), $bundle, []));
    }

    /**
     * @covers ::voteForObject()
     */
    public function testVoteForObject()
    {
        $voter = $this->getVoterMock(['vote', 'getClassScopeObjectIdentity']);

        $voter->expects($this->any())
            ->method('vote')
            ->willReturn(BBAclVoter::ACCESS_ABSTAIN);

        $voter->expects($this->once())
            ->method('getClassScopeObjectIdentity')
            ->willReturn(new ObjectIdentity('identifier', 'type'));

        $this->invokeMethod($voter, 'voteForObject', [new BBUserToken(), new \stdClass(), []]);
    }

    /**
     * @covers ::getClassScopeObjectIdentity()
     */
    public function testGetClassScopeObjectIdentity()
    {
        $voter = $this->getVoterMock([]);

        $object = $this->getMockForAbstractClass(
            ObjectIdentityInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getType']
        );

        $object->expects($this->once())
            ->method('getType')
            ->willReturn('FakeClass');

        $identity = $this->invokeMethod($voter, 'getClassScopeObjectIdentity', [$object]);

        $this->assertInstanceOf(ObjectIdentity::class, $identity);
        $this->assertEquals('all', $identity->getIdentifier());
        $this->assertEquals('FakeClass', $identity->getType());
    }

    /**
     * @covers ::voteForPage()
     */
    public function testVoteForPage()
    {
        $voter = $this->getVoterMock(['voteForObject']);
        $voter->expects($this->any())
            ->method('voteForObject')
            ->willReturn(BBAclVoter::ACCESS_DENIED);

        $page = $this->getMock(Page::class, ['getParent']);
        $page->expects($this->once())
            ->method('getParent')
            ->willReturn(new Page());

        $this->invokeMethod($voter, 'voteForPage', [new BBUserToken(), $page, []]);
    }

    /**
     * @covers ::voteForNestedNode()
     */
    public function testVoteForNestedNode()
    {
        $voter = $this->getVoterMock(['voteForObject']);
        $voter->expects($this->any())
            ->method('voteForObject')
            ->willReturn(BBAclVoter::ACCESS_DENIED);

        $section = $this->getMock(Section::class, ['getParent']);
        $section->expects($this->once())
            ->method('getParent')
            ->willReturn(new Section());

        $this->invokeMethod($voter, 'voteForNestedNode', [new BBUserToken(), $section, []]);
    }

    /**
     * @covers ::voteForClassContent()
     */
    public function testVoteForContentWithoutCategory()
    {
        $voter = $this->getVoterMock([]);
        $this->assertEquals(
            BBAclVoter::ACCESS_GRANTED,
            $this->invokeMethod($voter, 'voteForClassContent', [new BBUserToken(), new ContentSet(), []])
        );
    }

    /**
     * @covers ::voteForClassContent()
     */
    public function testVoteForContent()
    {
        $voter = $this->getVoterMock(['voteForObject']);
        $voter->expects($this->any())
            ->method('voteForObject')
            ->willReturn(BBAclVoter::ACCESS_DENIED);

        $content = $this->getMock(ContentSet::class, ['getProperty']);
        $content->expects($this->any())->method('getProperty')->willReturn('category');
        $this->assertEquals(
            BBAclVoter::ACCESS_GRANTED,
            $this->invokeMethod($voter, 'voteForClassContent', [new BBUserToken(), $content, []])
        );

        $mock = $this->getMockForAbstractClass(
            AbstractClassContent::class,
            [],
            '',
            false,
            false,
            true,
            ['getProperty']
        );
        $mock->expects($this->any())->method('getProperty')->willReturn('category');
        $this->assertEquals(
            BBAclVoter::ACCESS_ABSTAIN,
            $this->invokeMethod($voter, 'voteForClassContent', [new BBUserToken(), $mock, []])
        );
    }
}
