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

namespace BackBee\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\ApiUserInterface;
use BackBee\Security\Encoder\RequestSignatureEncoder;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\PublicKeyToken;
use BackBee\Util\Registry\Repository;

/**
 * Authentication provider for username/password firewall.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class PublicKeyAuthenticationProvider extends BBAuthenticationProvider
{

    /**
     * Role for API user
     *
     * @var string
     */
    private $apiUserRole;

    /**
     * Class constructor.
     *
     * @param UserProviderInterface   $userProvider
     * @param string                  $nonceDir
     * @param int                     $lifetime
     * @param Repository              $registryRepository
     * @param EncoderFactoryInterface $encoderFactory
     * @param string                  $apiUserRole
     */
    public function __construct(
        UserProviderInterface $userProvider,
        $nonceDir,
        $lifetime = 300,
        Repository $registryRepository = null,
        EncoderFactoryInterface $encoderFactory = null,
        $apiUserRole = 'ROLE_API_USER'
    ) {
        parent::__construct($userProvider, $nonceDir, $lifetime, $registryRepository, $encoderFactory);
        $this->apiUserRole = $apiUserRole;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (false === $this->supports($token)) {
            return;
        }

        if (null === $nonce = $this->readNonceValue($token->getNonce())) {
            return $this->onInvalidAuthentication();
        }

        $publicKey = $token->getUsername();
        $user = $this->userProvider->loadUserByPublicKey($publicKey);
        if (null === $user) {
            return $this->onInvalidAuthentication();
        }

        $token->setUser($user);
        $signature_encoder = new RequestSignatureEncoder();
        if (false === $signature_encoder->isApiSignatureValid($token, $nonce[1])) {
            return $this->onInvalidAuthentication();
        }

        if (time() > $nonce[0] + $this->lifetime) {
            $this->removeNonce($token->getNonce());
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);
        }

        $authenticatedToken = new PublicKeyToken($this->getRoles($user));
        $authenticatedToken
            ->setUser($user)
            ->setNonce($token->getNonce())
            ->setCreated(new \DateTime())
            ->setLifetime($this->lifetime)
        ;

        $this->writeNonceValue($authenticatedToken);

        return $authenticatedToken;
    }

    /**
     * Add API user role to authenticated token
     *
     * @param  ApiUserInterface $user
     *
     * @return array
     */
    private function getRoles(ApiUserInterface $user)
    {
        $roles = $user->getRoles();
        if ($user->getApiKeyEnabled()) {
            $roles[] = $this->apiUserRole;
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PublicKeyToken;
    }

    /**
     * Throw a SecurityException with Invalid authentication informations and 401 as status code.
     */
    private function onInvalidAuthentication()
    {
        throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
    }
}
