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

namespace BackBee\Security\Listeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\SecurityEvents;

use BackBee\Security\Token\UsernamePasswordToken;

@trigger_error('The '.__NAMESPACE__.'\UsernamePasswordAuthenticationListener class is '
        . 'deprecated since version 1.4 and will be removed in 1.5. '
        . 'Use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener '
        . 'instead.', E_USER_DEPRECATED);

/**
 * @deprecated since version 1.4, to be removed in 1.5.
 * @codeCoverageIgnore
 */
class UsernamePasswordAuthenticationListener implements ListenerInterface
{
    private $context;
    private $authenticationManager;
    private $login_path;
    private $check_path;
    private $logger;

    public function __construct(
        SecurityContextInterface $context,
        AuthenticationManagerInterface $authManager,
        $login_path = null,
        $check_path = null,
        LoggerInterface $logger = null
    ) {
        $this->context = $context;
        $this->authenticationManager = $authManager;
        $this->login_path = $login_path;
        $this->check_path = $check_path;
        $this->logger = $logger;
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $token = $this->context->getToken();
        $errmsg = '';

        if (null !== $request->request->get('login') && null !== $request->request->get('password')) {
            $token = new UsernamePasswordToken($request->request->get('login'), $request->request->get('password'));
            $token->setUser($request->request->get('login'), $request->request->get('password'));
            try {
                $token = $this->authenticationManager->authenticate($token);

                if (null !== $this->logger) {
                    $this->logger->info(sprintf('Authentication request succeed for user "%s"', $token->getUsername()));
                }

                $loginEvent = new InteractiveLoginEvent($request, $token);
                $event->getDispatcher()
                        ->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
            } catch (AuthenticationException $e) {
                $event->getDispatcher()
                        ->dispatch(
                            AuthenticationEvents::AUTHENTICATION_FAILURE,
                            new AuthenticationFailureEvent($token, $e)
                        );
                $errmsg = $e->getMessage();
                if (null !== $this->logger) {
                    $this->logger->info(sprintf(
                        'Authentication request failed for user "%s": %s',
                        $token->getUsername(),
                        $e->getMessage()
                    ));
                }
            } catch (\Exception $e) {
                $errmsg = $e->getMessage();
                if (null !== $this->logger) {
                    $this->logger->info(sprintf(
                        'Authentication request failed for user "%s": %s',
                        $token->getUsername(),
                        $e->getMessage()
                    ));
                }
            }
        }

        if (is_a($token, 'BackBee\Security\Token\UsernamePasswordToken') && $errmsg != '') {
            if (null !== $this->login_path) {
                if (preg_match('/%(.*)%/s', $this->login_path, $matches)) {
                    if ($this->context->getApplication()->getContainer()->hasParameter($matches[1])) {
                        $this->login_path = $this->context->getApplication()->getContainer()->getParameter($matches[1]);
                    }
                }

                $redirect = $request->query->get('redirect');
                if (null === $redirect) {
                    $redirect = $request->request->get('redirect', '');
                }
                if ('' === $redirect) {
                    $redirect = $request->getPathInfo();
                }
                if (null !== $qs = $request->getQueryString()) {
                    $redirect .= '?'.$qs;
                }

                $response = new RedirectResponse($event->getRequest()->getUriForPath(
                    $this->login_path
                    .'?redirect='.urlencode($redirect).'&errmsg='.urlencode($errmsg)
                    .'&login='.urlencode($request->request->get('login'))
                ));
                $event->setResponse($response);

                return;
            }

            $response = new Response();
            $response->setStatusCode(403);
            $event->setResponse($response);
        }

        if (null !== $token && is_a($token, 'BackBee\Security\Token\UsernamePasswordToken')) {
            $this->context->setToken($token);

            if ($request->request->get('redirect')) {
                $response = new RedirectResponse($request->getBaseUrl().$request->request->get('redirect'));
                $event->setResponse($response);
            }
        }
    }
}
