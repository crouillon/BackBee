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

namespace BackBee\Security\Tests\Encoder;

use BackBee\Security\Encoder\RequestSignatureEncoder;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;

/**
 * Test suite for class RequestSignatureEncoder
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @coversDefaultClass \BackBee\Security\Encoder\RequestSignatureEncoder
 */
class RequestSignatureEncoderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var BBUserToken
     */
    private $token;

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $user = new User();
        $user->setApiKeyPublic('public-key')
            ->setApiKeyPrivate('private-key');

        $this->token = new BBUserToken();
        $this->token
            ->setUser($user)
            ->setNonce('nonce');
    }

    /**
     * @covers ::isApiSignatureValid()
     */
    public function testIsApiSignatureValid()
    {
        $encoder = new RequestSignatureEncoder();
        $signature = $encoder->createSignature($this->token);

        $this->assertTrue($encoder->isApiSignatureValid($this->token, $signature));
        $this->assertFalse($encoder->isApiSignatureValid($this->token, 'bad-signature'));
    }

    /**
     * @covers ::createSignature()
     */
    public function testCreateSignature()
    {
        $expected = md5(sprintf(
            '%s%s%s',
            $this->token->getUser()->getApiKeyPublic(),
            $this->token->getUser()->getApiKeyPrivate(),
            $this->token->getNonce()
        ));

        $this->assertEquals(
            $expected,
            (new RequestSignatureEncoder())->createSignature($this->token)
        );
    }
}
