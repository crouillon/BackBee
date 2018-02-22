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

namespace BackBee\Site\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\SchemaTool;

use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Tests\Traits\CreateEntityManagerTrait;

/**
 * Site test for class Site.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Site\Site
 */
class SiteTest extends \PHPUnit_Framework_TestCase
{
    use CreateEntityManagerTrait;

    /**
     * @var Site
     */
    private $site;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site = new Site(null, ['label' => 'label']);
    }

    /**
     * @covers ::__construct()
     * @covers ::getUid()
     * @covers ::setLabel()
     * @covers ::getLabel()
     */
    public function testConstruct()
    {
        $this->assertEquals(32, strlen($this->site->getUid()));
        $this->assertInstanceOf(ArrayCollection::class, $this->site->getLayouts());
        $this->assertEquals('label', $this->site->getLabel());
    }

    /**
     * @covers ::setServerName()
     * @covers ::getServerName()
     */
    public function testServerName()
    {
        $this->assertEquals($this->site, $this->site->setServerName('servername'));
        $this->assertEquals('servername', $this->site->getServerName());
    }

    /**
     * @covers ::getDefaultExtension()
     */
    public function testgetDefaultExtension()
    {
        $this->assertEquals('.html', $this->site->getDefaultExtension());
    }

    /**
     * @covers ::addLayout()
     * @covers ::getLayouts()
     */
    public function testLayout()
    {
        $this->assertEquals($this->site, $this->site->addLayout(new Layout('layout1')));
        $this->assertEquals($this->site, $this->site->addLayout(new Layout('layout2')));

        $this->assertEquals('layout1', $this->site->getLayouts()->get(0)->getUid());
        $this->assertEquals('layout2', $this->site->getLayouts()->get(1)->getUid());
    }

    /**
     */
    public function testEntity()
    {
        $entityMng = $this->createEntityManager();
        $schemaTool = new SchemaTool($entityMng);
        $sql = $schemaTool->getCreateSchemaSql([
            $entityMng->getClassMetadata(Site::class)
        ]);

        $expected = [
            'CREATE TABLE site (' .
                'uid VARCHAR(32) NOT NULL, ' .
                'label VARCHAR(255) NOT NULL, ' .
                'created DATETIME NOT NULL, ' .
                'modified DATETIME NOT NULL, ' .
                'server_name VARCHAR(255) DEFAULT NULL, ' .
                'PRIMARY KEY(uid))',
            'CREATE INDEX IDX_SERVERNAME ON site (server_name)',
            'CREATE INDEX IDX_LABEL ON site (label)'
        ];

        $this->assertEquals($expected, $sql);
    }
}
