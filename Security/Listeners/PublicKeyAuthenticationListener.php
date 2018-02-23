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

namespace BackBee\Security\Listeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\PublicKeyToken;

/**
 * Default implementation of an authentication via public key and API signature.
 *
 * @author Kenneth Golovin
 */
class PublicKeyAuthenticationListener implements ListenerInterface
{

    const AUTH_PUBLIC_KEY_TOKEN = 'X-API-KEY';
    const AUTH_SIGNATURE_TOKEN = 'X-API-SIGNATURE';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Listener constructor.
     *
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authManager
     * @param LoggerInterface|null           $logger
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authManager,
        LoggerInterface $logger = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authManager;
        $this->logger = $logger;
    }

    /**
     * Handles REST API headers authentication.
     *
     * @param  GetResponseEvent $event
     *
     * @throws SecurityException
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $publicKey = $request->headers->get(self::AUTH_PUBLIC_KEY_TOKEN);
        $signature = $request->headers->get(self::AUTH_SIGNATURE_TOKEN);

        $token = new PublicKeyToken();
        $token->setUser($publicKey)
                ->setPublicKey($publicKey)
                ->setNonce($signature);

        try {
            $authenticatedToken = $this->authenticationManager->authenticate($token);

            if (null !== $this->logger) {
                $this->logger->info(sprintf(
                    'PubliKey Authentication request succeed for public key "%s"',
                    $authenticatedToken->getUsername()
                ));
            }

            $this->tokenStorage->setToken($authenticatedToken);
        } catch (SecurityException $e) {
            if (null !== $this->logger) {
                $this->logger->info(sprintf(
                    'PubliKey Authentication request failed for public key "%s": %s',
                    $token->getUsername(),
                    str_replace("\n", ' ', $e->getMessage())
                ));
            }

            throw $e;
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error($e->getMessage());
            }

            throw $e;
        }
    }
}
