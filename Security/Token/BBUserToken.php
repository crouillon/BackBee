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

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Base class for BackBee token's user.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BBUserToken extends AbstractToken
{

    /**
     * Token default lifetime (20 minutes)
     */
    const DEFAULT_LIFETIME = 1200;

    /**
     * The formatted creation date of the token.
     * Format: 'Y-m-d H:i:s'
     *
     * @var string
     */
    private $created;

    /**
     * Digest to be checks associated to the token.
     *
     * @var string
     */
    private $digest;

    /**
     * User's private nonce for the token.
     *
     * @var string
     */
    private $nonce;

    /**
     * Token max lifetime (in second).
     *
     * @var integer
     */
    private $lifetime = self::DEFAULT_LIFETIME;

    /**
     * Token Constructor.
     *
     * @param array $roles An array of roles
     */
    public function __construct(array $roles = [])
    {
        parent::__construct($roles);

        $this->setAuthenticated(true);
    }

    /**
     * Returns the string formatted creation date of the token.
     * Format: 'Y-m-d H:i:s'
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Sets the creation date.
     *
     * @param  string|\DateTime $created
     *
     * @return BBUserToken
     */
    public function setCreated($created)
    {
        $this->created = $created instanceof \DateTime ? $created->format('Y-m-d H:i:s') : '' . $created;

        return $this;
    }

    /**
     * Returns the credentials (empty for this token).
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * Returns the current digest.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getDigest()
    {
        return $this->digest;
    }

    /**
     * Returns the user's private nonce.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Returns the token max lifetime in seconds.
     *
     * @return integer|null
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Sets token max lifetime.
     *
     * @param int $lifetime The token max lifetime value
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = (int) $lifetime;

        return $this;
    }

    /**
     * Returns true if current token is expired by comparing current timestamp
     * with token created datetime and its max lifetime.
     *
     * @return boolean
     *
     * @throws \LogicException if token max lifetime or/and token created datetime are not setted
     */
    public function isExpired()
    {
        if (null === $this->created || 0 === $this->lifetime) {
            throw new \LogicException(
                'Cannot define if token is expired, created datetime or/and lifetime are missing.'
            );
        }

        return time() > strtotime($this->created) + $this->lifetime;
    }

    /**
     * Sets the digest.
     *
     * @param  string $digest
     *
     * @return BBUserToken
     *
     * @codeCoverageIgnore
     */
    public function setDigest($digest)
    {
        $this->digest = $digest;

        return $this;
    }

    /**
     * Sets the user's private nonce.
     *
     * @param  string $nonce
     *
     * @return BBuserToken
     *
     * @codeCoverageIgnore
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Sets the user in the token.
     *
     * The user can be a UserInterface instance, or an object implementing
     * a __toString method or the username as a regular string.
     *
     * @param string|object $user The user
     *
     * @return BBUserToken
     *
     * @throws \InvalidArgumentException
     */
    public function setUser($user)
    {
        parent::setUser($user);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            is_object($this->getUser()) ? clone $this->getUser() : $this->getUser(),
            $this->isAuthenticated(),
            array_map(function ($role) {return clone $role;}, $this->getRoles()),
            $this->getAttributes(),
            $this->nonce,
            $this->created,
            $this->lifetime,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $array = unserialize($serialized);
        $this->lifetime = array_pop($array);
        $this->created = array_pop($array);
        $this->nonce = array_pop($array);

        parent::unserialize(serialize($array));
    }
}
