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

namespace BackBee\Security\Tests\Authorization;

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use BackBee\Security\Authorization\ExpressionLanguage;
use BackBee\Security\SecurityContext;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Tests\Traits\InvokePropertyTrait;

/**
 * Test suite for class ExpressionLanguage
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Authorization\ExpressionLanguage
 */
class ExpressionLanguageTest extends \PHPUnit_Framework_TestCase
{

    use InvokeMethodTrait;
    use InvokePropertyTrait;

    /**
     * @var ExpressionLanguage
     */
    private $expr;

    /**
     * @var array
     */
    private $functions;

    /**
     * @var AuthenticationTrustResolver
     */
    private $resolver;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->expr = new ExpressionLanguage();
        $this->invokeMethod($this->expr, 'registerFunctions');
        $this->functions = $this->invokeProperty($this->expr, 'functions');

        $this->resolver = $this->getMockBuilder(AuthenticationTrustResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAnonymous', 'isFullFledged', 'isRememberMe'])
            ->getMock();
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testRegisterFunctions()
    {
        $this->assertTrue(isset($this->functions['is_anonymous']));
        $this->assertTrue(isset($this->functions['is_authenticated']));
        $this->assertTrue(isset($this->functions['is_fully_authenticated']));
        $this->assertTrue(isset($this->functions['is_remember_me']));
        $this->assertTrue(isset($this->functions['has_role']));
        $this->assertTrue(isset($this->functions['is_granted']));
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testIsAnonymous()
    {
        $function = $this->functions['is_anonymous'];
        $this->assertEquals(
            '$trust_resolver->isAnonymous($token)',
            call_user_func($function['compiler'])
        );

        $this->resolver->expects($this->once())
            ->method('isAnonymous');

        call_user_func(
            $function['evaluator'],
            ['trust_resolver' => $this->resolver, 'token' => new AnonymousToken('secret', 'user')]
        );
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testIsAuthenticated()
    {
        $function = $this->functions['is_authenticated'];
        $this->assertEquals(
            '$token && !$trust_resolver->isAnonymous($token)',
            call_user_func($function['compiler'])
        );

        $this->resolver->expects($this->once())
            ->method('isAnonymous');

        call_user_func(
            $function['evaluator'],
            ['trust_resolver' => $this->resolver, 'token' => new AnonymousToken('secret', 'user')]
        );
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testIsFullyAuthenticated()
    {
        $function = $this->functions['is_fully_authenticated'];
        $this->assertEquals(
            '$trust_resolver->isFullFledged($token)',
            call_user_func($function['compiler'])
        );

        $this->resolver->expects($this->once())
            ->method('isFullFledged');

        call_user_func(
            $function['evaluator'],
            ['trust_resolver' => $this->resolver, 'token' => new AnonymousToken('secret', 'user')]
        );
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testIsRememberMe()
    {
        $function = $this->functions['is_remember_me'];
        $this->assertEquals(
            '$trust_resolver->isRememberMe($token)',
            call_user_func($function['compiler'])
        );

        $this->resolver->expects($this->once())
            ->method('isRememberMe');

        call_user_func(
            $function['evaluator'],
            ['trust_resolver' => $this->resolver, 'token' => new AnonymousToken('secret', 'user')]
        );
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testHasRole()
    {
        $function = $this->functions['has_role'];
        $this->assertEquals(
            'in_array(role, $roles)',
            call_user_func($function['compiler'], 'role')
        );

        $this->assertTrue(
            call_user_func(
                $function['evaluator'],
                ['roles' => ['role']],
                'role'
            )
        );
    }

    /**
     * @covers ::registerFunctions()
     */
    public function testIsGranted()
    {
        $function = $this->functions['is_granted'];
        $this->assertEquals(
            '$security_context->isGranted(attributes, object)',
            call_user_func($function['compiler'], 'attributes', 'object')
        );

        $context = $this->getMockBuilder(SecurityContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock();

        $context->expects($this->once())
            ->method('isGranted');

        call_user_func(
            $function['evaluator'],
            ['security_context' => $context],
            'attributes'
        );
    }
}
