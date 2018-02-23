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

namespace BackBee\Security\Context;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;
use BackBee\Security\Authentication\Provider\PublicKeyAuthenticationProvider;
use BackBee\Security\Listeners\LogoutListener;
use BackBee\Security\Listeners\PublicKeyAuthenticationListener;
use BackBee\Security\Logout\BBLogoutHandler;
use BackBee\Security\Logout\BBLogoutSuccessHandler;
use BackBee\Util\Registry\Registry;

/**
 * Restful Security Context.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class RestfulContext extends AbstractContext
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = [];

        if (isset($config['restful'])) {
            $config = array_merge([
                'nonce_dir' => 'security/nonces',
                'lifetime' => 1200,
                'use_registry' => false,
            ], (array) $config['restful']);

            if (false !== ($defaultProvider = $this->getDefaultProvider($config))) {
                $context = $this->getSecurityContext();

                $context->getAuthenticationManager()
                    ->addProvider(
                        new PublicKeyAuthenticationProvider(
                            $defaultProvider,
                            $this->getNonceDirectory($config),
                            $config['lifetime'],
                            true === $config['use_registry'] ? $this->getRegistryRepository() : null,
                            $context->getEncoderFactory(),
                            $this->getApiUserRole()
                        )
                    )
                    ->addProvider(
                        $bbProvider = new BBAuthenticationProvider(
                            $defaultProvider,
                            $this->getNonceDirectory($config),
                            $config['lifetime'],
                            true === $config['use_registry'] ? $this->getRegistryRepository() : null,
                            $context->getEncoderFactory()
                        )
                    )
                ;

                $listeners[] = new PublicKeyAuthenticationListener(
                    $context,
                    $context->getAuthenticationManager(),
                    $context->getLogger()
                );

                $this->loadLogoutListener($bbProvider);
            }
        }

        return $listeners;
    }

    /**
     * Gets the API user role from container
     *
     * @return string
     */
    private function getApiUserRole()
    {
        $apiUserRole = null;

        $container = $this->getSecurityContext()->getApplication()->getContainer();
        if ($container->hasParameter('bbapp.securitycontext.role.apiuser')) {
            $apiUserRole = $container->getParameter('bbapp.securitycontext.role.apiuser');

            if ($container->hasParameter('bbapp.securitycontext.roles.prefix')) {
                $apiUserRole = $container->getParameter('bbapp.securitycontext.roles.prefix') . $apiUserRole;
            }
        }

        return $apiUserRole;
    }

    /**
     * Load LogoutListener into security context.
     *
     * @param AuthenticationProviderInterface $bbProvider
     */
    private function loadLogoutListener(AuthenticationProviderInterface $bbProvider)
    {
        if (null === $this->getSecurityContext()->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $this->getSecurityContext()->setLogoutListener(
                new LogoutListener(
                    $this->getSecurityContext(),
                    $httpUtils,
                    new BBLogoutSuccessHandler($httpUtils)
                )
            );
        }

        $this->getSecurityContext()->getLogoutListener()->addHandler(new BBLogoutHandler($bbProvider));
    }

    /**
     * Returns the nonce directory path.
     *
     * @param array $config
     *
     * @return string the nonce directory path
     */
    private function getNonceDirectory(array $config)
    {
        return $this->getSecurityContext()->getApplication()->getCacheDir().DIRECTORY_SEPARATOR.$config['nonce_dir'];
    }

    /**
     * Returns the repository to Registry entities.
     *
     * @return \BackBuillder\Bundle\Registry\Repository
     */
    private function getRegistryRepository()
    {
        $repository = null;
        if (null !== $em = $this->getSecurityContext()->getApplication()->getEntityManager()) {
            $repository = $em->getRepository(Registry::class);
        }

        return $repository;
    }
}
