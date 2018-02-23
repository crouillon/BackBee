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

namespace BackBee\Security\Tests\Context;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;

use BackBee\ApplicationInterface;
use BackBee\Security\Context\UsernamePasswordContext;
use BackBee\Security\SecurityContext;

/**
 * Test suite for class UsernamePasswordContext
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Context\UsernamePasswordContext
 */
class UsernamePasswordContextTest extends \PHPUnit_Framework_TestCase
{

    public function testLoadListeners()
    {
        $controller = $this->getMockForAbstractClass(HttpKernelInterface::class);
        $application = $this->getMockForAbstractClass(
            ApplicationInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getController']
        );
        $application->expects($this->any())->method('getController')->willReturn($controller);

        $provider = $this->getMockForAbstractClass(UserProviderInterface::class);
        $manager = $this->getMockForAbstractClass(
            AuthenticationManagerInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['addProvider']
        );

        $securityContext = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getApplication',
                'getUserProviders',
                'getAuthenticationManager',
                'getLogger',
                'getEncoderFactory'
            ])
            ->getMock();

        $securityContext->expects($this->any())
            ->method('getApplication')
            ->willReturn($application);

        $securityContext->expects($this->once())
            ->method('getUserProviders')
            ->willReturn([$provider]);

        $securityContext->expects($this->any())
            ->method('getAuthenticationManager')
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('addProvider')
            ->will($this->returnCallback(function ($provider) {
                \PHPUnit_Framework_Assert::isInstanceOf(UserAuthenticationProvider::class, $provider);
            }));

        $listener = new UsernamePasswordContext($securityContext);
        $this->assertEquals([], $listener->loadListeners([]));

        $listeners = $listener->loadListeners([
            'form_login' => [
                'login_path' => 'login_path',
                'check_path' => 'check_path',
            ],
        ]);
        $this->assertInstanceOf(UsernamePasswordFormAuthenticationListener::class, $listeners[0]);
    }
}
