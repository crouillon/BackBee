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

namespace BackBee\Translation\Tests;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\DependencyInjection\Container;

use BackBee\BBApplication;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Translation\Tests\Mock\TranslatorMock;

/**
 * Test suite for class Translator.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Translation\Translator
 */
class TranslatorTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;

    /**
     * @covers ::__construct()
     * @covers ::addResourcesDir()
     */
    public function testConstruct()
    {
        $default = ['Resources' => ['translations' => ['file.fr.xlf' => '']]];
        $vfs = [
            'bbdir' => $default,
            'repository' => $default,
            'baserepository' => $default
        ];
        vfsStream::setup('vfs', 0777, $vfs);

        $container = new Container();
        $container->setParameter('translator.fallback', 'en');

        $application = $this->getMockBuilder(BBApplication::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBBDir', 'getBaseRepository', 'getRepository', 'getContainer'])
            ->getMock();

        $application->expects($this->any())->method('getContainer')->willReturn($container);
        $application->expects($this->once())->method('getBBDir')->willReturn(vfsStream::url('vfs') . '/bbdir');
        $application->expects($this->any())->method('getRepository')->willReturn(vfsStream::url('vfs') . '/repository');
        $application->expects($this->any())
            ->method('getBaseRepository')
            ->willReturn(vfsStream::url('vfs') . '/baserepository');

        $translator = new TranslatorMock($application, 'fr');

        $this->assertEquals(['xliff'], array_keys($this->invokeMethod($translator, 'getLoaders')));
        $this->assertEquals(['en'], $translator->getFallbackLocales());
        $this->assertEquals(['fr'], array_keys($translator->getResources()));
        $this->assertEquals(3, count($translator->getResources()['fr']));
    }
}
