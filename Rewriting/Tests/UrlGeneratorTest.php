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

namespace BackBee\Rewriting\Tests;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

use BackBee\BBApplication;
use BackBee\ClassContent\Element\File;
use BackBee\ClassContent\Element\Text;
use BackBee\Config\Config;
use BackBee\DependencyInjection\Container;
use BackBee\NestedNode\Page;
use BackBee\NestedNode\Repository\PageQueryBuilder;
use BackBee\NestedNode\Repository\PageRepository;
use BackBee\Rewriting\UrlGenerator;
use BackBee\Site\Layout;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class UrlGenerator.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Rewriting\UrlGenerator
 */
class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var UrlGenerator
     */
    private $generator;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->container = new Container();
        $this->container->set('em', $this->getEntityMng());
        $this->container->set('config', $this->getConfig());

        $this->generator = new UrlGenerator();
        $this->generator->setContainer($this->container);
    }

    /**
     * @covers ::__construct()
     */
    public function testOldSignature()
    {
        $application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();

        $application->expects($this->once())
            ->method('getContainer')
            ->willReturn($this->container);

        new UrlGenerator($application);
    }

    /**
     * @covers            ::getService()
     * @expectedException \RuntimeException
     */
    public function testGetServiceWithoutContainer()
    {
        $this->invokeMethod(new UrlGenerator(), 'getService', ['unknown']);
    }

    /**
     * @covers ::getService()
     */
    public function testGetService()
    {
        $this->assertNull($this->invokeMethod($this->generator, 'getService', ['unknown']));
        $this->assertNotNull($this->invokeMethod($this->generator, 'getService', ['config']));
    }

    /**
     * @covers ::setContainer()
     * @covers ::setSchemes()
     * @covers ::getSchemes()
     */
    public function testSchemes()
    {
        $this->assertFalse(empty($this->generator->getSchemes()));
        $this->assertEquals($this->generator, $this->generator->setSchemes([]));
        $this->assertTrue(empty($this->generator->getSchemes()));
    }

    /**
     * @covers ::setContainer()
     * @covers ::setPreserveOnline()
     * @covers ::isPreserveOnline()
     */
    public function testPreserveOnline()
    {
        $this->assertTrue($this->generator->isPreserveOnline());
        $this->assertEquals($this->generator, $this->generator->setPreserveOnline(false));
        $this->assertFalse($this->generator->isPreserveOnline());
    }

    /**
     * @covers ::setContainer()
     * @covers ::setPreserveUnicity()
     * @covers ::isPreserveUnicity()
     */
    public function testPreserveUnicity()
    {
        $this->assertFalse($this->generator->isPreserveUnicity());
        $this->assertEquals($this->generator, $this->generator->setPreserveUnicity(true));
        $this->assertTrue($this->generator->isPreserveUnicity());
    }

    /**
     * @covers ::setContainer()
     * @covers ::getDiscriminators()
     */
    public function testGetDiscriminators()
    {
        $this->assertEquals(
            ['BackBee\ClassContent\Element\Text'],
            $this->generator->getDiscriminators()
        );
    }

    /**
     * @covers ::generate()
     */
    public function testGenerate()
    {
        $page = new Page();
        $page->setUrl('/url')
            ->setRedirect('/url-redirect')
            ->setState(Page::STATE_ONLINE);
        $this->assertEquals('/url', $this->generator->generate($page));
        $this->assertEquals('/root', $this->generator->generate($page, null, true));

        $page->setParent(new Page())
            ->setState(Page::STATE_OFFLINE)
            ->setLayout(new Layout('layout_uid'));
        $this->assertEquals('/layout', $this->generator->generate($page));

        $page->setLayout(new Layout());
        $this->assertEquals('/discriminator', $this->generator->generate($page, new Text()));
        $this->assertEquals('/default', $this->generator->generate($page, new File()));

        $this->invokeProperty($this->generator, 'schemes', []);
        $this->assertEquals('/url', $this->generator->generate($page));
        $this->assertEquals('/page-uid', $this->generator->generate(new Page('page-uid'), null, false, false));
    }

    /**
     * @covers            ::generate()
     * @expectedException \BackBee\Rewriting\Exception\RewritingException
     */
    public function testMissingSchemeGenerate()
    {
        $this->invokeProperty($this->generator, 'schemes', []);
        $this->assertEquals('/url', $this->generator->generate(new Page()));
    }

    /**
     * @covers ::getUniqueness()
     */
    public function testGetUniquenessNotExists()
    {
        $this->assertEquals('/url', $this->invokeMethod($this->generator, 'getUniqueness', [new Page(), '/url']));

        $repository = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $this->container
            ->get('em')
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->generator->setPreserveUnicity(true);
        $this->assertEquals('/url', $this->invokeMethod($this->generator, 'getUniqueness', [new Page(), '/url']));
    }

    /**
     * @covers ::getUniqueness()
     */
    public function testGetUniqueness()
    {
        $existing = new Page();
        $existing->setUrl('/url-3');

        $queryBuilder = new PageQueryBuilder($this->container->get('em'));
        $repository = $this->getRepository($queryBuilder);
        $query = $this->getQuery([$existing]);

        $this->container
            ->get('em')
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->container
            ->get('em')
            ->expects($this->once())
            ->method('createQuery')
            ->willReturn($query);

        $this->generator->setPreserveUnicity(true);
        $this->assertEquals(
            '/url-4',
            $this->invokeMethod($this->generator, 'getUniqueness', [new Page('page-uid'), '/url'])
        );

        $this->assertEquals(
            'SELECT p FROM BackBee\NestedNode\Page p INNER JOIN p._section p_s ' .
            'WHERE p_s._root = :root0 AND p._state < 4 AND p._uid != :uid AND p._url LIKE :url',
            $queryBuilder->getDQL()
        );
        $this->assertEquals('page-uid', $queryBuilder->getParameter('uid')->getValue());
        $this->assertEquals('/url-%', $queryBuilder->getParameter('url')->getValue());
    }

    /**
     * @covers ::getUniqueness()
     */
    public function testGetUniquenessFolder()
    {
        $existing = new Page();
        $existing->setUrl('/url/');

        $queryBuilder = new PageQueryBuilder($this->container->get('em'));
        $repository = $this->getRepository($queryBuilder);
        $query = $this->getQuery([$existing]);

        $this->container
            ->get('em')
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->container
            ->get('em')
            ->expects($this->once())
            ->method('createQuery')
            ->willReturn($query);

        $this->generator->setPreserveUnicity(true);
        $this->assertEquals(
            '/url-1/',
            $this->invokeMethod($this->generator, 'getUniqueness', [new Page(), '/url/'])
        );
    }

    /**
     * @covers ::onPageFlush()
     */
    public function testOnPageFlushWithUrlChange()
    {
        $page = new Page('uid');
        $page->setUrl('/url');

        $this->generator->setPreserveUnicity(false);
        $this->invokeProperty($this->generator, 'alreadyDone', ['uid-done']);

        $unitOfWork = $this->getUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['_url' => []]);

        $this->container
            ->get('em')
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->generator->onPageFlush($page);
        $this->generator->onPageFlush(new Page('uid-done'));

        $this->assertEquals(['uid-done', 'uid'], $this->invokeProperty($this->generator, 'alreadyDone'));
    }

    /**
     * @covers ::onPageFlush()
     */
    public function testOnManagedPageFlushWithStateChange()
    {
        $page = new Page('uid');
        $page->setUrl('/url');

        $this->generator->setPreserveUnicity(false);
        $this->invokeProperty($this->generator, 'alreadyDone', ['uid-done']);

        $entityMng = $this->container->get('em');
        $repository = $this->getRepository(new PageQueryBuilder($entityMng));
        $metadata = new ClassMetadata(Page::class);

        $repository->expects($this->once())
            ->method('getDescendants')
            ->willReturn([new Page('uid-done')]);

        $unitOfWork = $this->getUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['_state' => [Page::STATE_OFFLINE]]);

        $unitOfWork->expects($this->any())
            ->method('isScheduledForInsert')
            ->willReturn(false);

        $unitOfWork->expects($this->once())
            ->method('isScheduledForUpdate')
            ->willReturn(true);

        $unitOfWork->expects($this->once())
            ->method('recomputeSingleEntityChangeSet');

        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $entityMng->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $entityMng->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->generator->onPageFlush($page);

        $this->assertEquals('/root', $page->getUrl());
    }

    /**
     * @covers ::onPageFlush()
     */
    public function testOnNotManagedPageFlushWithStateChange()
    {
        $page = new Page('uid');
        $page->setUrl('/url');

        $this->generator->setPreserveUnicity(false);

        $entityMng = $this->container->get('em');
        $repository = $this->getRepository(new PageQueryBuilder($entityMng));
        $metadata = new ClassMetadata(Page::class);

        $repository->expects($this->once())
            ->method('getDescendants')
            ->willReturn([]);

        $unitOfWork = $this->getUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['_state' => [Page::STATE_OFFLINE]]);

        $unitOfWork->expects($this->once())
            ->method('isScheduledForDelete')
            ->willReturn(false);

        $unitOfWork->expects($this->once())
            ->method('computeChangeSet');

        $entityMng->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $entityMng->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $entityMng->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->generator->onPageFlush($page);

        $this->assertEquals('/root', $page->getUrl());
    }

    /**
     * @covers ::getMaincontent()
     */
    public function testGetManagedMainContent()
    {
        $page = new Page();
        $content = new Text();
        $content->setMainNode($page);

        $unitOfWork = $this->getUnitOfWork();

        $unitOfWork->expects($this->once())
            ->method('isScheduledForInsert')
            ->willReturn(true);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$content]);

        $entityMng = $this->container->get('em');
        $entityMng->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->assertEquals(
            $content,
            $this->invokeMethod($this->generator, 'getMaincontent', [$page])
        );
    }

    /**
     * @covers ::getMaincontent()
     */
    public function testGetNotManagedMainContent()
    {
        $page = new Page();
        $content = new Text();

        $entityMng = $this->container->get('em');

        $repository = $this->getRepository(new PageQueryBuilder($entityMng));
        $repository->expects($this->once())
            ->method('getLastByMainnode')
            ->willReturn($content);

        $entityMng->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $unitOfWork = $this->getUnitOfWork();

        $entityMng->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork->expects($this->once())
            ->method('isScheduledForInsert')
            ->willReturn(false);

        $this->assertEquals(
            $content,
            $this->invokeMethod($this->generator, 'getMaincontent', [$page])
        );
    }

    /**
     * @covers ::doGenerate()
     */
    public function testDoGenerateWithParent()
    {
        $parent = new Page();
        $parent->setUrl('/parent//');

        $page = new Page('uid');
        $page->setTitle('Title of the page')
            ->setCreated(new \DateTime('2001-01-01 12:00:00'))
            ->setParent($parent);

        $this->assertEquals(
            '/parent/20010101120000/20010101/120000/uid/title-of-the-page',
            $this->invokeMethod($this->generator, 'doGenerate', ['$parent/$datetime/$date/$time/$uid/$title', $page])
        );
    }

    /**
     * @covers ::doGenerate()
     */
    public function testDoGenerateWithContent()
    {
        $page = new Page();
        $text = new Text();
        $text->value = 'Value of the text';

        $this->assertEquals(
            '/value-of-the-text/',
            $this->invokeMethod($this->generator, 'doGenerate', ['/$content->value/$content->unknown', $page, $text])
        );
    }

    /**
     * @covers ::doGenerate()
     */
    public function testDoGenerateWithAncestor()
    {
        $page = new Page();
        $page->setLevel(2);

        $ancestor = new Page();
        $ancestor->setUrl('/ancestor');

        $queryBuilder = new PageQueryBuilder($this->container->get('em'));
        $repository = $this->getRepository($queryBuilder);

        $repository->expects($this->any())
            ->method('getAncestor')
            ->willReturn($ancestor);

        $this->container
            ->get('em')
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->assertEquals(
            '/ancestor1/2/',
            $this->invokeMethod($this->generator, 'doGenerate', ['/$ancestor[1]1/$ancestor[2]2/', $page])
        );
    }

    /**
     * @return Config
     */
    private function getConfig()
    {
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRewritingConfig'])
            ->getMock();

        $config->expects($this->any())
            ->method('getRewritingConfig')
            ->willReturn([
                'preserve-online' => true,
                'preserve-unicity' => false,
                'scheme' => [
                    '_default_' => '/default',
                    '_root_' => '/root',
                    '_layout_' => [
                        'layout_uid' => '/layout'
                    ],
                    '_content_' => [
                        'Element\Text' => '/discriminator',
                    ]
                ]
            ]);

        return $config;
    }

    /**
     * @return EntityManager
     */
    private function getEntityMng()
    {
        $entityMng = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRepository',
                'createQuery',
                'getUnitOfWork',
                'getClassMetadata'
            ])
            ->getMock();

        return $entityMng;
    }

    /**
     * @return UnitOfWork
     */
    private function getUnitOfWork()
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getScheduledEntityInsertions',
                'getEntityChangeSet',
                'isScheduledForInsert',
                'isScheduledForUpdate',
                'isScheduledForDelete',
                'recomputeSingleEntityChangeSet',
                'computeChangeSet'
            ])
            ->getMock();

        return $unitOfWork;
    }

    /**
     * @param  PageQueryBuilder $queryBuilder
     *
     * @return PageRepository
     */
    private function getRepository(PageQueryBuilder $queryBuilder)
    {
        $repository = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'findOneBy',
                'createQueryBuilder',
                'getAncestor',
                'getDescendants',
                'getLastByMainnode'
            ])
            ->getMock();

        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(new Page());

        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder->select('p')->from(Page::class, 'p'));

        return $repository;
    }

    /**
     * @param  array $result
     *
     * @return AbstractQuery
     */
    private function getQuery($result = [])
    {
        $query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            false,
            true,
            ['getResult', 'setFirstResult', 'setMaxResults']
        );

        $query->expects($this->once())->method('setFirstResult')->willReturn($query);
        $query->expects($this->once())->method('setMaxResults')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($result);

        return $query;
    }
}
