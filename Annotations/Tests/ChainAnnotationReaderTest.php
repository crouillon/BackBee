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

namespace BackBee\Annotations\Tests;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\Reader;

use BackBee\Annotations\ChainAnnotationReader;

/**
 * Test suite for class ChainAnnotationReader.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Annotations\ChainAnnotationReader
 */
class ChainAnnotationReaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Reader[]
     */
    private $delegates;

    /**
     * @var ChainAnnotationReader
     */
    private $reader;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        $this->delegates = [
            $this->getMockReader(),
            $this->getMockReader()
        ];

        $this->reader = new ChainAnnotationReader($this->delegates);
    }

    /**
     * @covers ::__construct()
     * @covers ::getClassAnnotations()
     */
    public function testGetClassAnnotations()
    {
        $expected = [
            'Annotation1',
            'Annotation2'
        ];

        $this->delegates[0]
            ->expects($this->once())
            ->method('getClassAnnotations')
            ->will($this->throwException(new AnnotationException()));

        $this->delegates[1]
            ->expects($this->once())
            ->method('getClassAnnotations')
            ->willReturn($expected);
        
        $this->assertEquals(
            $expected,
            $this->reader->getClassAnnotations(new \ReflectionClass($this))
        );
    }

    /**
     * @covers ::getClassAnnotation()
     */
    public function testGetClassAnnotation()
    {
        $this->delegates[0]
            ->expects($this->any())
            ->method('getClassAnnotations')
            ->willReturn([$this]);

        $this->delegates[1]
            ->expects($this->any())
            ->method('getClassAnnotations')
            ->willReturn([]);

        $this->assertNull(
            $this->reader->getClassAnnotation(new \ReflectionClass($this), 'StdClass')
        );
        $this->assertEquals(
            $this,
            $this->reader->getClassAnnotation(new \ReflectionClass($this), self::class)
        );
    }

    /**
     * @covers ::getPropertyAnnotations()
     */
    public function testGetPropertyAnnotations()
    {
        $expected = [
            'Annotation1',
            'Annotation2'
        ];

        $this->delegates[0]
            ->expects($this->once())
            ->method('getPropertyAnnotations')
            ->will($this->throwException(new AnnotationException()));

        $this->delegates[1]
            ->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn($expected);
        
        $this->assertEquals(
            $expected,
            $this->reader->getPropertyAnnotations(new \ReflectionProperty($this, 'reader'))
        );
    }

    /**
     * @covers ::getPropertyAnnotation()
     */
    public function testGetPropertyAnnotation()
    {
        $this->delegates[0]
            ->expects($this->any())
            ->method('getPropertyAnnotations')
            ->willReturn([$this]);

        $this->delegates[1]
            ->expects($this->any())
            ->method('getPropertyAnnotations')
            ->willReturn([]);

        $this->assertNull(
            $this->reader->getPropertyAnnotation(new \ReflectionProperty($this, 'reader'), 'StdClass')
        );
        $this->assertEquals(
            $this,
            $this->reader->getPropertyAnnotation(new \ReflectionProperty($this, 'reader'), self::class)
        );
    }

    /**
     * @covers ::getMethodAnnotations()
     */
    public function testGetMethodAnnotations()
    {
        $expected = [
            'Annotation1',
            'Annotation2'
        ];

        $this->delegates[0]
            ->expects($this->once())
            ->method('getMethodAnnotations')
            ->will($this->throwException(new AnnotationException()));

        $this->delegates[1]
            ->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn($expected);
        
        $this->assertEquals(
            $expected,
            $this->reader->getMethodAnnotations(new \ReflectionMethod($this, 'setUp'))
        );
    }

    /**
     * @covers ::getMethodAnnotation()
     */
    public function testGetMethodAnnotation()
    {
        $this->delegates[0]
            ->expects($this->any())
            ->method('getMethodAnnotations')
            ->willReturn([$this]);

        $this->delegates[1]
            ->expects($this->any())
            ->method('getMethodAnnotations')
            ->willReturn([]);

        $this->assertNull(
            $this->reader->getMethodAnnotation(new \ReflectionMethod($this, 'setUp'), 'StdClass')
        );
        $this->assertEquals(
            $this,
            $this->reader->getMethodAnnotation(new \ReflectionMethod($this, 'setUp'), self::class)
        );
    }

    /**
     * @return Reader
     */
    private function getMockReader()
    {
        return $this->getMockForAbstractClass(
            Reader::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getClassAnnotations',
                'getPropertyAnnotations',
                'getMethodAnnotations'
            ]
        );
    }
}
