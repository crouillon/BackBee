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
use BackBee\Tests\Traits\InvokePropertyTrait;
use BackBee\Workflow\State;

/**
 * Test suite for class Layout
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Site\Layout
 */
class LayoutTest extends \PHPUnit_Framework_TestCase
{
    use CreateEntityManagerTrait;
    use InvokePropertyTrait;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->layout = new Layout(null, ['label' => 'label', 'path' => 'path']);
    }

    /**
     * @covers ::__construct()
     * @covers ::getUid()
     * @covers ::setLabel()
     * @covers ::getLabel()
     * @covers ::setPath()
     * @covers ::getPath()
     */
    public function testConstruct()
    {
        $this->assertEquals(32, strlen($this->layout->getUid()));
        $this->assertInstanceOf(ArrayCollection::class, $this->invokeProperty($this->layout, '_pages'));
        $this->assertInstanceOf(ArrayCollection::class, $this->layout->getStates());
        $this->assertEquals('label', $this->layout->getLabel());
        $this->assertEquals('path', $this->layout->getPath());
    }

    /**
     * @covers ::getData()
     * @covers ::setData()
     * @covers ::getDataObject()
     * @covers ::setDataObject()
     * @covers ::virtualGetData()
     */
    public function testData()
    {
        $expectedObject = new \stdClass();
        $expectedObject->property = 'property';
        $expectedString = json_encode($expectedObject);
        $expectedArray = json_decode($expectedString, true);

        $this->assertEquals($this->layout, $this->layout->setData($expectedObject));
        $this->assertEquals($expectedString, $this->layout->getData());
        $this->assertEquals($expectedObject, $this->layout->getDataObject());
        $this->assertEquals($expectedArray, $this->layout->virtualGetData());
    }

    /**
     * @covers ::setPicPath()
     * @covers ::getPicPath()
     */
    public function testPicPath()
    {
        $this->assertEquals($this->layout, $this->layout->setPicPath('picpath'));
        $this->assertEquals('picpath', $this->layout->getPicPath());
    }

    /**
     * @covers ::getSiteUid()
     * @covers ::getSiteLabel()
     * @covers ::getSite()
     * @covers ::setSite()
     */
    public function testSite()
    {
        $this->assertNull($this->layout->getSiteUid());
        $this->assertNull($this->layout->getSiteLabel());
        $this->assertEquals($this->layout, $this->layout->setSite(new Site('uid', ['label' => 'label'])));
        $this->assertEquals('uid', $this->layout->getSiteUid());
        $this->assertEquals('label', $this->layout->getSiteLabel());
    }

    /**
     * @covers ::getZones()
     * @covers ::getZone()
     * @covers ::getZoneOptions()
     *
     */
    public function testZone()
    {
        $this->assertNull($this->layout->getZones());

        $data = new \stdClass();
        $data->templateLayouts = [
            [
                'id' => 'id1',
                'mainZone' => true,
                'defaultClassContent' => 'Element\Text',
                'accept' => ['Element\Text'],
                'maxentry' => 1,
                'defaultContainer' => 'defaultContainer',
                'target' => 'target',
                'gridClassPrefix' => 'gridClassPrefix',
                'gridSize' => 'gridSize'
            ],
            [
                'id' => 'id2',
                'defaultContainer' => 'defaultContainer',
                'target' => 'target',
                'gridClassPrefix' => 'gridClassPrefix',
                'gridSize' => 'gridSize'
            ]
        ];
        $this->layout->setData($data);

        $zone1 = $this->layout->getZone(0);
        $zone2 = $this->layout->getZone(1);
        $this->assertNull($this->layout->getZone(2));
        $this->assertFalse($zone2->mainZone);
        $this->assertNull($zone2->defaultClassContent);

        $expected = [
            'accept' => ['BackBee\ClassContent\Element\Text'],
            'maxentry' => 1
        ];
        $this->assertEquals($expected, $zone1->options);
    }

    /**
     * @covers            ::getZone()
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testInvalidZone()
    {
        $this->layout->getZone('string');
    }

    /**
     * @covers ::setParam()
     * @covers ::getParam()
     * @covers ::isFinal()
     */
    public function testParameter()
    {
        $this->assertEquals($this->layout, $this->layout->setParam(null, ['param' => 'param']));
        $this->assertEquals($this->layout, $this->layout->setParam('is_final', true));
        $this->assertEquals(['param' => 'param', 'is_final' => true], $this->layout->getParam());
        $this->assertEquals('param', $this->layout->getParam('param'));
        $this->assertNull($this->layout->getParam('unknown'));
        $this->assertTrue($this->layout->isFinal());
    }

    /**
     * @covers ::isValid()
     */
    public function testIsValid()
    {
        $this->assertFalse($this->layout->isValid());

        $data = new \stdClass();
        $this->assertFalse($this->layout->setData($data)->isValid());

        $data->templateLayouts = '';
        $this->assertFalse($this->layout->setData($data)->isValid());

        $data->templateLayouts = [];
        $this->assertFalse($this->layout->setData($data)->isValid());

        $data->templateLayouts = [['id' => 'id']];
        $this->assertTrue($this->layout->setData($data)->isValid());
    }

    /**
     * @covers ::addState()
     * @covers ::getStates()
     * @covers ::getWorkflowStates()
     * @covers ::removeState()
     */
    public function testState()
    {
        $state = new State('state-pos', ['code' => 1, 'label' => 'pos']);
        $this->assertEquals($this->layout, $this->layout->addState($state));
        $this->assertEquals(
            $this->layout,
            $this->layout->addState(new State('state-neg', ['code' => -1, 'label' => 'neg']))
        );

        $this->assertEquals('state-pos', $this->layout->getStates()->get(0)->getUid());
        $this->assertEquals('state-neg', $this->layout->getStates()->get(1)->getUid());

        $expected = [
            ['label' => 'Hors ligne', 'code' => '0'],
            ['label' => 'neg', 'code' => '0_-1'],
            ['label' => 'En ligne', 'code' => '1'],
            ['label' => 'pos', 'code' => '1_1'],
        ];
        $this->assertEquals($expected, $this->layout->getWokflowStates());

        $this->layout->removeState($state);
        $this->assertEquals(1, $this->layout->getStates()->count());
    }

    /**
     */
    public function testEntity()
    {
        $entityMng = $this->createEntityManager();
        $schemaTool = new SchemaTool($entityMng);
        $sql = $schemaTool->getCreateSchemaSql([
            $entityMng->getClassMetadata(Layout::class)
        ]);

        $expected = [
            'CREATE TABLE layout (' .
                'uid VARCHAR(32) NOT NULL, ' .
                'site_uid VARCHAR(32) DEFAULT NULL, ' .
                'label VARCHAR(255) NOT NULL, ' .
                'path VARCHAR(255) NOT NULL, ' .
                'data CLOB NOT NULL, ' .
                'created DATETIME NOT NULL, ' .
                'modified DATETIME NOT NULL, ' .
                'picpath VARCHAR(255) DEFAULT NULL, ' .
                'parameters CLOB DEFAULT NULL, ' .
                'PRIMARY KEY(uid), ' .
                'CONSTRAINT FK_3A3A6BE2A7063726 ' .
                    'FOREIGN KEY (site_uid) ' .
                    'REFERENCES site (uid) ' .
                    'NOT DEFERRABLE INITIALLY IMMEDIATE)',
            'CREATE INDEX IDX_3A3A6BE2A7063726 ON layout (site_uid)'
        ];

        $this->assertEquals($expected, $sql);
    }
}
