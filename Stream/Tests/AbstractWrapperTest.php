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

namespace BackBee\Stream\Tests;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Repository\ClassContentRepository;
use BackBee\Stream\AbstractWrapper;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class AbstractWrapper
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Stream\AbstractWrapper
 */
class AbstractWrapperTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var AbstractWrapper
     */
    private $wrapper;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->wrapper = $this->getMockForAbstractClass(
            AbstractWrapper::class,
            [],
            '',
            false,
            false,
            true,
            ['stream_open', 'stream_stat', 'url_stat']
        );
    }

    /**
     * @covers ::getNamespace()
     */
    public function testGetNamespace()
    {
        $this->assertEquals(
            trim(AbstractClassContent::CLASSCONTENT_BASE_NAMESPACE, NAMESPACE_SEPARATOR),
            $this->invokeMethod($this->wrapper, 'getNamespace')
        );
    }

    /**
     * @covers ::getDocBlock()
     */
    public function testGetDocBlock()
    {
        $this->invokeProperty($this->wrapper, 'properties', ['name' => 'name', 'description' => 'description']);
        $this->invokeProperty(
            $this->wrapper,
            'elements',
            [
                'invalid' => 'invalid',
                'string' => ['type' => 'string', 'options' => ['label' => 'label']],
                'untyped' => ['options' => ['label' => 'untyped']]
            ]
        );

        $expected = str_replace(
            ["\r", "\n"],
            ['', PHP_EOL],
            ' * Generated class for content name.
 * description

 * @property string $string label
 * @property type $untyped untyped'
        );

        $this->assertEquals($expected, $this->invokeMethod($this->wrapper, 'getDocBlock'));
    }

    /**
     * @covers ::getRepository()
     */
    public function testGetRepository()
    {
        $this->assertEquals(
            ClassContentRepository::class,
            $this->invokeMethod($this->wrapper, 'getRepository')
        );
    }

    /**
     * @covers ::getExtends()
     */
    public function testGetExtends()
    {
        $this->assertEquals(
            NAMESPACE_SEPARATOR . AbstractClassContent::class,
            $this->invokeMethod($this->wrapper, 'getExtends')
        );
    }

    /**
     * @covers ::formatTrait()
     * @covers ::getTraits()
     */
    public function testgetTraits()
    {
        $this->invokeProperty($this->wrapper, 'traits', ['Fake1Trait', 'Fake2Trait']);

        $expected = [
            'Use Fake1Trait;',
            'Use Fake2Trait;',
        ];

        $this->assertEquals(
            $expected,
            $this->invokeMethod($this->wrapper, 'getTraits')
        );
    }

    /**
     * @covers ::formatProperty()
     * @covers ::getProperties()
     */
    public function testgetProperties()
    {
        $this->invokeProperty($this->wrapper, 'properties', ['name' => ['name']]);

        $expected = [
            'name' => sprintf('$this->defineProperty("%s", %s);', 'name', var_export(['name'], true))
        ];

        $this->assertEquals(
            $expected,
            $this->invokeMethod($this->wrapper, 'getProperties')
        );
    }

    /**
     * @covers ::formatElement()
     * @covers ::getElements()
     */
    public function testgetElements()
    {
        $this->invokeProperty($this->wrapper, 'elements', [
            'scalar' => '!!scalar default value',
            'subcontent' => [
                'type' => '\BackBee\ClassContent\Element\Text',
                'label' => 'subcontent'
            ],
            'array' => []
        ]);

        $expected = [
            'scalar' => sprintf(
                '$this->defineData("%s", "%s", %s);',
                'scalar',
                'scalar',
                var_export(['default' => 'default value'], true)
            ),
            'subcontent' => sprintf(
                '$this->defineData("%s", "%s", %s);',
                'subcontent',
                '\BackBee\ClassContent\Element\Text',
                var_export(['label' => 'subcontent'], true)
            ),
            'array' => sprintf(
                '$this->defineData("%s", "%s", %s);',
                'array',
                'array',
                var_export(['default' => []], true)
            ),
        ];

        $this->assertEquals(
            $expected,
            $this->invokeMethod($this->wrapper, 'getElements')
        );
    }

    /**
     * @covers ::formatParameter()
     * @covers ::getParameters()
     */
    public function testgetParameters()
    {
        $this->invokeProperty($this->wrapper, 'parameters', ['name' => ['name']]);

        $expected = [
            'name' => sprintf('$this->defineParam("%s", %s);', 'name', var_export(['name'], true))
        ];

        $this->assertEquals(
            $expected,
            $this->invokeMethod($this->wrapper, 'getParameters')
        );
    }

    /**
     * @covers ::setExtends()
     */
    public function testSetExtends()
    {
        $this->invokeMethod($this->wrapper, 'setExtends', ['Extends']);
        $this->assertEquals('BackBee\ClassContent\Extends', $this->invokeProperty($this->wrapper, 'extends'));

        $this->invokeMethod($this->wrapper, 'setExtends', ['\Extends']);
        $this->assertEquals('\Extends', $this->invokeProperty($this->wrapper, 'extends'));
    }

    /**
     * @covers ::setInterface()
     * @covers ::addFirstNamespaceSlash()
     */
    public function testSetInterface()
    {
        $this->invokeMethod($this->wrapper, 'setInterface', ['BackBee\Stream\WrapperInterface']);
        $this->assertEquals(['\BackBee\Stream\WrapperInterface'], $this->invokeProperty($this->wrapper, 'interfaces'));
    }

    /**
     * @covers            ::setInterface()
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testSetUnknownInterface()
    {
        $this->invokeMethod($this->wrapper, 'setInterface', ['WrapperInterface']);
    }

    /**
     * @covers ::setRepository()
     */
    public function testSetRepository()
    {
        $this->invokeMethod($this->wrapper, 'setRepository', ['Doctrine\ORM\EntityRepository']);
        $this->assertEquals('\Doctrine\ORM\EntityRepository', $this->invokeProperty($this->wrapper, 'repository'));
    }

    /**
     * @covers            ::setRepository()
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testSetUnknownRepository()
    {
        $this->invokeMethod($this->wrapper, 'setRepository', ['EntityRepository']);
    }

    /**
     * @covers            ::setRepository()
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testSetNotARepository()
    {
        $this->invokeMethod($this->wrapper, 'setRepository', [__CLASS__]);
    }

    /**
     * @covers ::setTraits()
     */
    public function testSetTraits()
    {
        $this->invokeMethod($this->wrapper, 'setTraits', ['BackBee\Tests\Traits\InvokeMethodTrait']);
        $this->assertEquals(['\BackBee\Tests\Traits\InvokeMethodTrait'], $this->invokeProperty($this->wrapper, 'traits'));
    }

    /**
     * @covers            ::setTraits()
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testSetUnknownTrait()
    {
        $this->invokeMethod($this->wrapper, 'setTraits', ['InvokeMethodTrait']);
    }

    /**
     * @covers ::setProperties()
     */
    public function testSetProperties()
    {
        $this->invokeMethod($this->wrapper, 'setProperties', [['VaR' => 'value']]);
        $this->assertEquals(['var' => 'value'], $this->invokeProperty($this->wrapper, 'properties'));
    }

    /**
     * @covers ::setElements()
     */
    public function testSetElements()
    {
        $this->invokeMethod($this->wrapper, 'setElements', [['VaR' => 'value']]);
        $this->assertEquals(['var' => 'value'], $this->invokeProperty($this->wrapper, 'elements'));
    }

    /**
     * @covers ::setParameters()
     */
    public function testSetParameters()
    {
        $this->invokeMethod($this->wrapper, 'setParameters', [['VaR' => 'value']]);
        $this->assertEquals(['var' => 'value'], $this->invokeProperty($this->wrapper, 'parameters'));
    }

    /**
     * @covers ::buildClass()
     */
    public function testBuildClass()
    {
        $this->invokeProperty($this->wrapper, 'classname', 'classname');
        $this->invokeProperty($this->wrapper, 'interfaces', ['FakeInterface1', 'FakeInterface2']);
        $this->invokeProperty($this->wrapper, 'traits', ['FakeTrait']);
        $this->invokeProperty($this->wrapper, 'properties', ['name' => 'name']);
        $this->invokeProperty($this->wrapper, 'elements', ['name' => ['type' => 'string', 'options' => ['label' => 'name']]]);
        $this->invokeProperty($this->wrapper, 'parameters', ['name' => ['name']]);

        $search = [
            '<namespace>',
            '<docblock>',
            '<repository>',
            '<classname>',
            '<extends>',
            '<interfaces>',
            '<traits>',
            '<properties>',
            '<elements>',
            '<parameters>',
        ];

        $replace = [
            $this->invokeMethod($this->wrapper, 'getNamespace'),
            $this->invokeMethod($this->wrapper, 'getDocBlock'),
            $this->invokeMethod($this->wrapper, 'getRepository'),
            $this->invokeMethod($this->wrapper, 'getClassname'),
            $this->invokeMethod($this->wrapper, 'getExtends'),
            count($this->invokeMethod($this->wrapper, 'getInterfaces')) ? 'implements ' . implode(', ', $this->invokeMethod($this->wrapper, 'getInterfaces')) : '',
            implode(PHP_EOL, $this->invokeMethod($this->wrapper, 'getTraits')),
            implode(PHP_EOL, $this->invokeMethod($this->wrapper, 'getProperties')),
            implode(PHP_EOL, $this->invokeMethod($this->wrapper, 'getElements')),
            implode(PHP_EOL, $this->invokeMethod($this->wrapper, 'getParameters')),
        ];

        $expected = str_replace($search, $replace, AbstractWrapper::TEMPLATE);

        $this->assertEquals(
            $expected,
            $this->invokeMethod($this->wrapper, 'buildClass')
        );
    }

    /**
     * @covers ::stream_close()
     */
    public function testStreamClose()
    {
        $this->wrapper->context = 'something';
        $this->wrapper->stream_close();

        $this->assertNull($this->wrapper->context);
    }

    /**
     * @covers ::stream_eof()
     */
    public function testStreamEof()
    {
        $this->invokeProperty($this->wrapper, 'data', 'something');
        $this->invokeProperty($this->wrapper, 'position', 0);
        $this->assertFalse($this->wrapper->stream_eof());

        $this->invokeProperty($this->wrapper, 'position', 9);
        $this->assertTrue($this->wrapper->stream_eof());
    }

    /**
     * @covers ::stream_read()
     */
    public function testStreamRead()
    {
        $this->invokeProperty($this->wrapper, 'data', 'something');
        $this->invokeProperty($this->wrapper, 'position', 0);
        $this->assertEquals('some', $this->wrapper->stream_read(4));
        $this->assertEquals(4, $this->wrapper->stream_tell());
    }

    /**
     * @covers ::stream_seek()
     * @covers ::stream_tell()
     */
    public function testStreamSeek()
    {
        $this->invokeProperty($this->wrapper, 'data', 'something');
        $this->invokeProperty($this->wrapper, 'position', 0);

        $this->assertFalse($this->wrapper->stream_seek(-1, SEEK_SET));
        $this->assertFalse($this->wrapper->stream_seek(10, SEEK_SET));
        $this->assertTrue($this->wrapper->stream_seek(4, SEEK_SET));
        $this->assertEquals(4, $this->wrapper->stream_tell());

        $this->assertFalse($this->wrapper->stream_seek(-6, SEEK_CUR));
        $this->assertFalse($this->wrapper->stream_seek(6, SEEK_CUR));
        $this->assertTrue($this->wrapper->stream_seek(2, SEEK_CUR));
        $this->assertEquals(6, $this->wrapper->stream_tell());

        $this->assertFalse($this->wrapper->stream_seek(-10, SEEK_END));
        $this->assertFalse($this->wrapper->stream_seek(1, SEEK_END));
        $this->assertTrue($this->wrapper->stream_seek(-2, SEEK_END));
        $this->assertEquals(7, $this->wrapper->stream_tell());

        $this->assertFalse($this->wrapper->stream_seek(1, 3));
    }

    /**
     * @covers ::getOptions()
     * @covers ::getOption()
     */
    public function testGetOptions()
    {
        $this->invokeProperty($this->wrapper, 'defaultOptions', ['default' => 'option']);

        stream_context_set_default([AbstractWrapper::PROTOCOL => ['option' => 'option']]);
        $this->wrapper->context = stream_context_create([AbstractWrapper::PROTOCOL => ['option' => 'new option', 'other' => 'option']]);

        $expected = [
            'default' => 'option',
            'option' => 'new option',
            'other' => 'option'
        ];

        $this->assertEquals($expected, $this->invokeMethod($this->wrapper, 'getOptions'));
        $this->assertEquals('option', $this->invokeMethod($this->wrapper, 'getOption', ['other']));
        $this->assertNull($this->invokeMethod($this->wrapper, 'getOption', ['unknown']));
    }
}
