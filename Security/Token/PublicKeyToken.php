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

namespace BackBee\Security\Token;

use BackBee\Security\User;

/**
 * Base class for BackBee API token.
 *
 * @author Kenneth Golovin
 */
class PublicKeyToken extends BBUserToken
{

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $signature;

    /**
     * Constructor.
     *
     * @param array $roles An array of roles
     */
    public function __construct(array $roles = [])
    {
        parent::__construct($roles);

        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        if ($this->getUser() instanceof User) {
            return $this->getUser()->getApiKeyPublic();
        }

        return parent::getUsername();
    }

    /**
     * Public key attribute setter.
     *
     * @param  string $publicKey new public key value
     *
     * @return PublicKeyToken
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Public key attribute getter.
     *
     * @return string the current token public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Signature attribute setter.
     *
     * @param string $signature new signature value
     *
     * @return PublicKeyToken
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Signature attribute getter.
     *
     * @return string the current token signature
     */
    public function getSignature()
    {
        return $this->signature;
    }
}
