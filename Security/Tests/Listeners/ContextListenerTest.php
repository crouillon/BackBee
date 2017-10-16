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

namespace BackBee\Security\Tests\Listeners;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use BackBee\ApplicationInterface;
use BackBee\Controller\FrontController;
use BackBee\Security\Listeners\ContextListener;

/**
 * Test suite for class ContextListener/
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Listeners\ContextListener
 */
class ContextListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::handle()
     */
    public function testHandle()
    {
        $container = new Container();
        $container->set('bb_session', new Session());

        $application = $this->getMockForAbstractClass(
            ApplicationInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getContainer']
        );
        $application->expects($this->once())->method('getContainer')->willReturn($container);

        $controller = $this->getMockBuilder(FrontController::class)
                ->disableOriginalConstructor()
                ->setMethods(['getApplication'])
                ->getMock();
        $controller->expects($this->once())->method('getApplication')->willReturn($application);

        $request = $this->getMockBuilder(Request::class)
                ->disableOriginalConstructor()
                ->setMethods(['hasSession', 'setSession'])
                ->getMock();

        $request->expects($this->any())->method('hasSession')->willReturn(false);
        $request->expects($this->once())->method('setSession');

        $event = new GetResponseEvent($controller, $request, FrontController::MASTER_REQUEST);

        $tokenStorage = $this->getMockForAbstractClass(TokenStorageInterface::class);
        $listener = new ContextListener($tokenStorage, [], 'key');
        $listener->handle($event);
    }
}
