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

namespace BackBee\Workflow\Tests;

use BackBee\Site\Layout;
use BackBee\Workflow\ListenerInterface;
use BackBee\Workflow\State;

/**
 * Tests suite for class State.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Workflow\State
 */
class StateTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var State
     */
    private $state;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->state = new State(null, ['code' => 421, 'label' => 'label']);
    }

    /**
     * @covers ::__construct()
     * @covers ::getUid()
     * @covers ::setLabel()
     * @covers ::getLabel()
     * @covers ::setCode()
     * @covers ::getCode()
     */
    public function testConstruct()
    {
        $this->assertEquals(32, strlen($this->state->getUid()));
        $this->assertEquals('label', $this->state->getLabel());
        $this->assertEquals(421, $this->state->getCode());
    }

    /**
     * @covers                   ::setCode()
     * @expectedException        \BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage The code of a workflow state has to be an integer
     */
    public function testSetWrongCodeTypeThrowsException()
    {
        $this->state->setCode('123');
    }

    /**
     * @covers ::setLayout()
     * @covers ::getLayout()
     * @covers ::getLayoutUid()
     */
    public function testLayout()
    {
        $layout = new Layout('layout-uid');
        $this->assertNull($this->state->getLayoutUid());
        $this->assertEquals($this->state, $this->state->setLayout($layout));
        $this->assertEquals($layout, $this->state->getLayout());
        $this->assertEquals('layout-uid', $this->state->getLayoutUid());
    }

    /**
     * @covers ::setListener()
     * @covers ::getListener()
     * @covers ::getListenerInstance()
     */
    public function testSetListener()
    {
        $listener = $this->getMockForAbstractClass(ListenerInterface::class);

        $this->assertEquals($this->state, $this->state->setListener(get_class($listener)));
        $this->assertEquals(get_class($listener), $this->state->getListener());
        $this->assertInstanceOf(ListenerInterface::class, $this->state->getListenerInstance());

        $this->state->setListener(null);
        $this->assertNull($this->state->getListener());
        $this->assertNull($this->state->getListenerInstance());
    }

    /**
     * @covers                   ::setListener()
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Workflow state listener must be type of null, object or string, boolean given.
     */
    public function testSetInvalidListenerTypeThrowsException()
    {
        $this->state->setListener(true);
    }

    /**
     * @covers                   ::setListener()
     * @expectedException        \LogicException
     * @expectedExceptionMessage Workflow state listener must implement BackBee\Workflow\ListenerInterface.
     */
    public function testSetInvalidListenerThrowsException()
    {
        $this->state->setListener(new \stdClass());
    }

    /**
     * @covers ::jsonSerialize()
     */
    public function testJsonSerialize()
    {
        $expected = [
            'uid'        => $this->state->getUid(),
            'layout_uid' => $this->state->getLayoutUid(),
            'code'       => $this->state->getCode(),
            'label'      => $this->state->getLabel(),
        ];

        $this->assertEquals($expected, $this->state->jsonSerialize());
    }
}
