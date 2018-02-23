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

namespace BackBee\Security\Authentication;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\ProviderNotFoundException;

use BackBee\Security\Exception\SecurityException;

/**
 * AuthenticationProviderManager uses a list of AuthenticationProviderInterface
 * instances to authenticate a Token.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AuthenticationManager implements AuthenticationManagerInterface
{

    /**
     * @var AuthenticationProviderInterface[]
     */
    private $providers;

    /**
     * @var bool
     */
    private $eraseCredentials;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Manager constructor.
     *
     * @param AuthenticationProviderInterface[]  $providers
     * @param EventDispatcherInterface           $dispatcher
     * @param bool                               $eraseCredentials
     */
    public function __construct(array $providers, EventDispatcherInterface $dispatcher = null, $eraseCredentials = true)
    {
        $this->addProviders($providers)
            ->setEventDispatcher($dispatcher);

        $this->eraseCredentials = (bool) $eraseCredentials;
    }

    /**
     * Sets an event dispather.
     *
     * @param  EventDispatcherInterface|null $dispatcher
     *
     * @return AuthenticationManager
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher = null)
    {
        $this->eventDispatcher = $dispatcher;

        return $this;
    }

    /**
     * Adds an authentication provider to the list.
     *
     * @param  AuthenticationProviderInterface $provider
     *
     * @return AuthenticationManager
     */
    public function addProvider(AuthenticationProviderInterface $provider)
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * Adds an array og authentication provider to the list.
     *
     * @param  AuthenticationProviderInterface[] $providers
     *
     * @return AuthenticationManager
     *
     * @throws \InvalidArgumentException if a provider isn't implementing AuthenticationProviderInterface
     */
    public function addProviders(array $providers)
    {
        foreach ($providers as $provider) {
            if (!$provider instanceof AuthenticationProviderInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    'Provider "%s" must implement the AuthenticationProviderInterface.',
                    get_class($provider)
                ));
            }

            $this->addProvider($provider);
        }

        return $this;
    }

    /**
     * Attempts to authenticate a TokenInterface object.
     *
     * @param  TokenInterface $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     *
     * @throws AuthenticationException if the authentication fails
     */
    public function authenticate(TokenInterface $token)
    {
        $lastException = null;
        $result = null;

        foreach ($this->providers as $provider) {
            if (!$provider->supports($token)) {
                continue;
            }

            try {
                $result = $provider->authenticate($token);

                if (null !== $result) {
                    break;
                }
            } catch (AccountStatusException $e) {
                $e->setToken($token);

                throw $e;
            } catch (AuthenticationException $e) {
                $lastException = $e;
            } catch (SecurityException $e) {
                $lastException = new AuthenticationException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if (null !== $result) {
            if (true === $this->eraseCredentials) {
                $result->eraseCredentials();
            }

            if (null !== $this->eventDispatcher) {
                $this->eventDispatcher->dispatch(
                    AuthenticationEvents::AUTHENTICATION_SUCCESS,
                    new AuthenticationEvent($result)
                );
            }

            return $result;
        }

        if (null === $lastException) {
            $lastException = new ProviderNotFoundException(sprintf(
                'No Authentication Provider found for token of class "%s".',
                get_class($token)
            ));
        }

        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                AuthenticationEvents::AUTHENTICATION_FAILURE,
                new AuthenticationFailureEvent($token, $lastException)
            );
        }

        $lastException->setToken($token);

        throw $lastException;
    }
}
