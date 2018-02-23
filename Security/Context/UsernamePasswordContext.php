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

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;

use BackBee\Security\Authentication\Provider\UserAuthenticationProvider;

/**
 * Add a default UsernamePasswordFormAuthentication listener.
 *
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class UsernamePasswordContext extends AbstractContext implements ContextInterface
{

    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = [];
        if (isset($config['form_login'])
            && (false !== ($defaultProvider = $this->getDefaultProvider($config)))
        ) {
            $provider = new UserAuthenticationProvider(
                $defaultProvider,
                $this->getSecurityContext()->getEncoderFactory(),
                isset($config['context']) ? $config['context'] : 'secret_key'
            );

            $this->getSecurityContext()
                ->getAuthenticationManager()
                ->addProvider($provider);

            $listeners[] = new UsernamePasswordFormAuthenticationListener(
                $this->getSecurityContext(),
                $this->getSecurityContext()->getAuthenticationManager(),
                new SessionAuthenticationStrategy(SessionAuthenticationStrategy::NONE),
                new HttpUtils(),
                isset($config['context']) ? $config['context'] : 'secret_key',
                new DefaultAuthenticationSuccessHandler(new HttpUtils(), $config['form_login']),
                new DefaultAuthenticationFailureHandler(
                    $this->getSecurityContext()->getApplication()->getController(),
                    new HttpUtils(),
                    $config['form_login']
                ),
                [],
                $this->getSecurityContext()->getLogger()
            );
        }

        return $listeners;
    }
}
