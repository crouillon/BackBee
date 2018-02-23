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

namespace BackBee\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\Encoder\RequestSignatureEncoder;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\BBUserToken;
use BackBee\Util\Registry\Registry;
use BackBee\Util\Registry\Repository;

/**
 * Retrieves BBUser for BBUserToken.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BBAuthenticationProvider implements AuthenticationProviderInterface
{

    /**
     * The nonce directory.
     *
     * @var string
     */
    private $nonceDir;

    /**
     * The user provider use to retrieve user.
     *
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * The life time of the connection.
     *
     * @var int
     */
    protected $lifetime;

    /**
     * The DB Registry repository to used to store nonce rather than file.
     *
     * @var Repository
     */
    private $registryRepository;

    /**
     * The encoders factory.
     *
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * Class constructor.
     *
     * @param UserProviderInterface   $userProvider
     * @param string                  $nonceDir
     * @param int                     $lifetime
     * @param Repository              $registryRepository
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(
        UserProviderInterface $userProvider,
        $nonceDir,
        $lifetime = 300,
        Repository $registryRepository = null,
        EncoderFactoryInterface $encoderFactory = null
    ) {
        $this->userProvider = $userProvider;
        $this->nonceDir = $nonceDir;
        $this->lifetime = $lifetime;
        $this->registryRepository = $registryRepository;
        $this->encoderFactory = $encoderFactory;

        if (null === $this->registryRepository && false === file_exists($this->nonceDir)) {
            mkdir($this->nonceDir, 0700, true);
        }
    }

    /**
     * Attempts to authenticates a TokenInterface object.
     *
     * @param  TokenInterface $token
     *
     * @return BBUserToken
     *
     * @throws SecurityException
     */
    public function authenticate(TokenInterface $token)
    {
        if (false === $this->supports($token)) {
            throw new SecurityException('Invalid token provided', SecurityException::UNSUPPORTED_TOKEN);
        }

        try {
            $this->checkNonce($token, $this->getSecret($token));
        } catch (\Exception $e) {
            $this->clearNonce($token);
            throw $e;
        }

        $validToken = new BBUserToken($token->getUser()->getRoles());
        $validToken
            ->setUser($token->getUser())
            ->setNonce($token->getNonce())
            ->setCreated(new \DateTime())
            ->setLifetime($this->lifetime)
        ;

        $this->writeNonceValue($validToken);

        return $validToken;
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param  TokenInterface $token
     *
     * @return bool
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof BBUserToken;
    }

    /**
     * Clear nonce file for the current token.
     *
     * @param TokenInterface $token
     */
    public function clearNonce(TokenInterface $token)
    {
        if (true === $this->supports($token) && null !== $token->getNonce()) {
            $this->removeNonce($token->getNonce());
        }
    }

    /**
     * Checks for a valid nonce file according to the WSE.
     *
     * @param  string $digest  The digest string send by the client
     * @param  string $nonce   The nonce file
     * @param  string $created The creation date of the nonce
     * @param  string $secret  The secret (ie password) to be check
     *
     * @return bool
     *
     * @throws SecurityException
     */
    protected function checkNonce(BBUserToken $token, $secret)
    {
        $digest = $token->getDigest();
        $nonce = $token->getNonce();
        $created = $token->getCreated();

        if (time() - strtotime($created) > 300) {
            throw new SecurityException('Request expired', SecurityException::EXPIRED_TOKEN);
        }

        if (md5($nonce.$created.$secret) !== $digest) {
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
        }

        $value = $this->readNonceValue($nonce);
        if (null !== $value && $value[0] + $this->lifetime < time()) {
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);
        }

        return true;
    }

    /**
     * Returns the nonce value if found, NULL otherwise.
     *
     * @param  string $nonce
     *
     * @return mixed
     */
    protected function readNonceValue($nonce)
    {
        $value = null;

        if (null === $this->registryRepository) {
            if (true === is_readable($this->nonceDir.DIRECTORY_SEPARATOR.$nonce)) {
                $value = file_get_contents($this->nonceDir.DIRECTORY_SEPARATOR.$nonce);
            }
        } else {
            $value = $this->getRegistry($nonce)->getValue();
        }

        if (null !== $value) {
            $value = explode(';', $value);
        }

        return $value;
    }

    /**
     * Updates the nonce value.
     *
     * @param BBUserToken $token
     */
    protected function writeNonceValue(BBUserToken $token)
    {
        $now = strtotime($token->getCreated());
        $nonce = $token->getNonce();

        $generator = new RequestSignatureEncoder();
        $signature = $generator->createSignature($token);

        if (null === $this->registryRepository) {
            file_put_contents($this->nonceDir.DIRECTORY_SEPARATOR.$nonce, "$now;$signature");
        } else {
            $registry = $this->getRegistry($nonce)->setValue("$now;$signature");
            $this->registryRepository->save($registry);
        }
    }

    /**
     * Removes the nonce.
     *
     * @param string $nonce
     */
    protected function removeNonce($nonce)
    {
        if (null === $this->registryRepository) {
            @unlink($this->nonceDir.DIRECTORY_SEPARATOR.$nonce);
        } else {
            $registry = $this->getRegistry($nonce);
            $this->registryRepository->remove($registry);
        }
    }

    /**
     * Returns a Registry entry for $nonce.
     *
     * @param  string $nonce
     *
     * @return Registry
     */
    private function getRegistry($nonce)
    {
        if (null === $registry = $this->registryRepository->findOneBy(['key' => $nonce, 'scope' => 'SECURITY.NONCE'])) {
            $registry = new Registry();
            $registry->setKey($nonce)->setScope('SECURITY.NONCE');
        }

        return $registry;
    }
    /**
     * Returns the encoded secret from the token.
     *
     * @param  TokenInterface $token
     *
     * @return string
     */
    protected function getSecret(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByUsername($token->getUsername());
        $token->setUser($user);

        $password = $user->getPassword();
        $secret = md5($password);

        if ($this->encoderFactory) {
            try {
                $encoder = $this->encoderFactory->getEncoder($user);
                $secret = $encoder->encodePassword($password, '');
            } catch (\RuntimeException $e) {
                // no encoder defined
            }
        }

        return $secret;
    }
}
