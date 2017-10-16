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

use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider as sfUserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\Exception\SecurityException;

/**
 * Authentication provider for username/password firewall.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class UserAuthenticationProvider extends sfUserAuthenticationProvider
{
    /**
     * The user provider to query.
     *
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * The encoders factory.
     *
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * Class constructor.
     *
     * @param UserProviderInterface   $userProvider               An user provider.
     * @param EncoderFactoryInterface $encoderFactory             An encoder factory
     * @param string                  $providerKey                A provider key
     * @param bool                    $hideUserNotFoundExceptions Whether to hide user not found exception or not
     */
    public function __construct(
        UserProviderInterface $userProvider,
        EncoderFactoryInterface $encoderFactory = null,
        $providerKey = 'default_key',
        $hideUserNotFoundExceptions = true
    ) {
        if ($userProvider instanceof UserCheckerInterface) {
            parent::__construct($userProvider, $providerKey, $hideUserNotFoundExceptions);
        } else {
            parent::__construct(new UserChecker(), $providerKey, $hideUserNotFoundExceptions);
        }

        $this->userProvider = $userProvider;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * Attempts to authenticate a TokenInterface object.
     *
     * @param TokenInterface $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     *
     * @throws SecurityException if the authentication fails
     */
    public function authenticate(TokenInterface $token)
    {
        try {
            return parent::authenticate($token);
        } catch (\Exception $ex) {
            throw new SecurityException(
                'Invalid authentication informations',
                SecurityException::INVALID_CREDENTIALS,
                $ex
            );
        }
    }

    /**
     * Retrieves the user from an implementation-specific location.
     *
     * @param string                $username The username to retrieve
     * @param UsernamePasswordToken $token    The Token
     *
     * @return UserInterface The user
     *
     * @throws UsernameNotFoundException if the credentials could not be validated
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        if (null === $user = $this->userProvider->loadUserByUsername($username)) {
            $exception = new UsernameNotFoundException(sprintf(
                'Unknown user with username `%s`.',
                $username
            ));
            $exception->setToken($token);

            throw $exception;
        }

        return $user;
    }

    /**
     * Does additional checks on the user and token (like validating the
     * credentials).
     *
     * @param UserInterface         $user  The retrieved UserInterface instance
     * @param UsernamePasswordToken $token The UsernamePasswordToken token to be authenticated
     *
     * @throws BadCredentialsException if the credentials could not be validated
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        try {
            if ($this->encoderFactory) {
                $this->checkAuthenticationWithEncoder($user, $token);
            } else {
                $this->checkAuthenticationWithoutEncoder($user, $token);
            }
        } catch (\Exception $ex) {
            $exception = new BadCredentialsException($ex->getMessage(), $ex->getCode(), $ex);
            $exception->setToken($token);

            throw $exception;
        }
    }

    /**
     * Authenticate a token according to the user provided with password encoder.
     *
     * @param UserInterface         $user  The retrieved UserInterface instance
     * @param UsernamePasswordToken $token The UsernamePasswordToken token to be authenticated
     *
     * @throws \RuntimeException if no encoder found for the user's classname
     * @throws BadCredentialsException if the credentials could not be validated
     */
    private function checkAuthenticationWithEncoder(UserInterface $user, TokenInterface $token)
    {
        if (true !== $this->encoderFactory
                ->getEncoder(ClassUtils::getRealClass($user))
                ->isPasswordValid($user->getPassword(), $token->getCredentials(), $user->getSalt())) {
            throw new BadCredentialsException();
        }
    }

    /**
     * Authenticate a token according to the user provided without any password encoders.
     *
     * @param UserInterface         $user  The retrieved UserInterface instance
     * @param UsernamePasswordToken $token The UsernamePasswordToken token to be authenticated
     *
     * @throws BadCredentialsException if the credentials could not be validated
     */
    private function checkAuthenticationWithoutEncoder(UserInterface $user, TokenInterface $token)
    {
        if ($token->getCredentials() !== $user->getPassword()) {
            throw new BadCredentialsException();
        }
    }
}
