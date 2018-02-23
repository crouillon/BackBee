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

namespace BackBee\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\FirewallMap;

use BackBee\BBApplication;
use BackBee\Routing\Matcher\RequestMatcher;
use BackBee\Security\Authentication\AuthenticationManager;
use BackBee\Security\Context\ContextInterface;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Listeners\LogoutListener;

/**
 * SecurityContext is the main entry point for the BackBee security component.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class SecurityContext implements TokenStorageInterface, AuthorizationCheckerInterface
{

    /**
     * @var BBApplication
     */
    private $application;

    /**
     * @var array
     */
    private $config;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var FirewallMap
     */
    private $firewallmap;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authmanager;

    /**
     * @var AuthenticationProviderInterface[]
     */
    private $authproviders;

    /**
     * @var UserProviderInterface
     */
    private $userproviders;

    /**
     * @var MutableAclProvider
     */
    private $aclprovider;

    /**
     * @var ContextInterface[]
     */
    private $contexts;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * Security context constructor.
     *
     * @param BBApplication                                                $application
     * @param TokenStorageInterface|AuthenticationManagerInterface         $tokenStorage
     * @param AuthorizationCheckerInterface|AccessDecisionManagerInterface $authorizationChecker
     */
    public function __construct(BBApplication $application, $tokenStorage, $authorizationChecker)
    {
        $oldSignature = $tokenStorage instanceof AuthenticationManagerInterface
            && $authorizationChecker instanceof AccessDecisionManagerInterface;
        $newSignature = $tokenStorage instanceof TokenStorageInterface
            && $authorizationChecker instanceof AuthorizationCheckerInterface;

        // confirm possible signatures
        if (!$oldSignature && !$newSignature) {
            throw new \BadMethodCallException(
                'Unable to construct SecurityContext, please provide the correct arguments'
            );
        }

        if ($oldSignature) {
            @trigger_error('The '.__CLASS__.'(BBApplication, AuthenticationManagerInterface, '
                . 'AccessDecisionManagerInterface) is deprecated since v1.4 and will be removed '
                . 'in v1.5. Use '.__CLASS__.'(BBApplication, TokenStorageInterface, '
                . 'AuthorizationCheckerInterface) instead.', E_USER_DEPRECATED);

            // renamed for clarity
            $authenticationManager = $tokenStorage;
            $accessDecisionManager = $authorizationChecker;
            $tokenStorage = new TokenStorage();
            $authorizationChecker = new AuthorizationChecker(
                $tokenStorage,
                $authenticationManager,
                $accessDecisionManager,
                false
            );
        }

        $this->application = $application;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;

        $this->authmanager = $this->application->getContainer()->get('security.authentication.manager');

        $this->config = (array) $this->application->getConfig()->getSecurityConfig();

        $this->createEncoderFactory($this->config)
            ->createProviders($this->config)
            ->createACLProvider($this->config)
            ->createFirewallMap($this->config)
            ->registerFirewall();
    }

    /**
     * Create an encoders factory if need.
     *
     * @param  array $config
     *
     * @return SecurityContext
     */
    private function createEncoderFactory(array $config)
    {
        if (isset($config['encoders'])) {
            $this->encoderFactory = new EncoderFactory($config['encoders']);
        }

        return $this;
    }

    /**
     * Returns the encoder factory or null if not defined.
     *
     * @return EncoderFactoryInterface|null
     */
    public function getEncoderFactory()
    {
        return $this->encoderFactory;
    }

    /**
     * @param  array $config
     *
     * @return RequestMatcher
     */
    private function getRequestMatcher(array $config)
    {
        $requestMatcher = new RequestMatcher();
        if (isset($config['pattern'])) {
            $requestMatcher->matchPath($config['pattern']);
        }

        if (isset($config['requirements'])) {
            foreach ((array) $config['requirements'] as $key => $value) {
                if (0 === strpos($key, 'HTTP-')) {
                    $requestMatcher->matchHeader(substr($key, 5), $value);
                }
            }
        }

        return $requestMatcher;
    }

    /**
     * Creates a new security firewall.
     *
     * @param  string $name
     * @param  array  $config
     *
     * @return SecurityContext
     *
     * @throws SecurityException if no authentication listener registered for the firewall.
     */
    public function createFirewall($name, $config)
    {
        if (isset($config['security']) && false === $config['security']) {
            $this->addFirewall($this->getRequestMatcher($config), []);
        } else {
            $config['firewall_name'] = $name;
            $listeners = $this->loadContexts($config);
            if (0 == count($listeners)) {
                throw new SecurityException(sprintf('No authentication listener registered for firewall "%s".', $name));
            }

            $this->addFirewall($this->getRequestMatcher($config), $listeners);
        }

        return $this;
    }

    /**
     * Loads the security contexts defined in configuration.
     *
     * @param array $config
     */
    private function initContexts()
    {
        $this->contexts = [];
        foreach ($this->config['contexts'] as $namespace => $classnames) {
            foreach ($classnames as $classname) {
                $class = implode(NAMESPACE_SEPARATOR, [$namespace, $classname]);
                if (is_a($class, ContextInterface::class, true)) {
                    $this->contexts[$class] = new $class($this);
                }
            }
        }

        return $this;
    }

    /**
     * Loads the listeners activated for a firewall config.
     *
     * @param  array $config
     *
     * @return array Array of listeners.
     */
    public function loadContexts($config)
    {
        if (empty($this->contexts)) {
            $this->initContexts();
        }

        $listeners = [];
        foreach ($this->contexts as $context) {
            $listeners = array_merge($listeners, $context->loadListeners($config));
        }

        return $listeners;
    }

    /**
     * Adds an user proviver throw entity repository.
     *
     * @param string $name
     * @param array  $config
     */
    private function addEntityProvider($name, array $config)
    {
        $manager = $this->application->getEntityManager();
        if (isset($config['manager_name'])) {
            $manager = $this->application->getEntityManager($config['manager_name']);
        }

        if (null !== $manager) {
            if (isset($config['class']) && isset($config['provider'])) {
                $providerClass = $config['provider'];
                $this->userproviders[$name] = new $providerClass($manager->getRepository($config['class']));
            } elseif (isset($config['class'])) {
                $this->userproviders[$name] = $manager->getRepository($config['class']);
            }
        }
    }

    /**
     * Adds an user proviver throw webservice.
     *
     * @param string $name
     * @param array  $config
     */
    private function addWebserviceProvider($name, array $config)
    {
        if (isset($config['class'])) {
            $userprovider = $config['class'];
            $this->userproviders[$name] = new $userprovider($this->getApplication());
        }
    }

    /**
     * Creates an array of UserProviderInterface according to the security configuration.
     *
     * @param  array $config
     *
     * @return SeurityContext
     */
    public function createProviders($config)
    {
        $this->userproviders = [];

        if (!isset($config['providers'])) {
            return $this;
        }

        $providers = (array) $config['providers'];
        foreach ($providers as $name => $provider) {
            if (isset($provider['entity'])) {
                $this->addEntityProvider($name, $provider['entity']);
            }

            if (isset($provider['webservice'])) {
                $this->addWebserviceProvider($name, $provider['webservice']);
            }
        }

        return $this;
    }

    /**
     * Adds an authentication provider to the security context.
     *
     * @param AuthenticationProviderInterface $provider
     * @param string                          $key
     */
    public function addAuthProvider(AuthenticationProviderInterface $provider, $key = null)
    {
        if (is_null($key)) {
            $this->authproviders[] = $provider;
        } else {
            $this->authproviders[$key] = $provider;
        }
    }

    /**
     * Gets the authentication provider.
     *
     * @param string $key
     *
     * @return AuthenticationProviderInterface
     *
     * @throws InvalidArgumentException if provider not found
     */
    public function getAuthProvider($key)
    {
        if (array_key_exists($key, $this->authproviders)) {
            return $this->authproviders[$key];
        }

        throw new \InvalidArgumentException(sprintf("Auth provider doesn't exists", $key));
    }

    /**
     * Adds an user provider to th security context.
     *
     * @param string                $name
     * @param UserProviderInterface $provider
     */
    public function addUserProvider($name, UserProviderInterface $provider)
    {
        $this->userproviders[$name] = $provider;
    }

    /**
     * Adds a firewall to the map of the security context.
     *
     * @param RequestMatcherInterface $requestMatcher
     * @param array                   $listeners
     * @param ExceptionListener       $exceptionListener
     */
    public function addFirewall(
        RequestMatcherInterface $requestMatcher,
        $listeners,
        ExceptionListener $exceptionListener = null
    ) {
        $this->firewallmap->add($requestMatcher, $listeners, $exceptionListener);
    }

    /**
     * @return BBApplication
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return UserProviderInterface[]
     */
    public function getUserProviders()
    {
        return $this->userproviders;
    }

    /**
     * @return AuthenticationManager
     */
    public function getAuthenticationManager()
    {
        return $this->authmanager;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->getApplication()->getLogging();
    }

    /**
     * Returns the ogout listener if exists.
     *
     * @return LogoutListener
     */
    public function getLogoutListener()
    {
        return $this->getApplication()->getContainer()->has('security.logout_listener')
            ? $this->getApplication()->getContainer()->get('security.logout_listener')
            : null;
    }

    /**
     * Sets the logout listener.
     *
     * @param LogoutListener $listener
     */
    public function setLogoutListener(LogoutListener $listener)
    {
        if (!$this->getApplication()->getContainer()->has('security.logout_listener')) {
            $this->getApplication()->getContainer()->set('security.logout_listener', $listener);

            if (false === $this->getDispatcher()->isRestored()) {
                $this->getDispatcher()->addListener(
                    'frontcontroller.request.logout',
                    ['@security.logout_listener', 'handle']
                );
            }
        }
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->getApplication()->getEventDispatcher();
    }

    /**
     * Creates a fireall map for the security context.
     *
     * @param  array $config
     *
     * @return SecurityContext
     */
    private function createFirewallMap($config)
    {
        $this->firewallmap = new FirewallMap();

        if (!isset($config['firewalls'])) {
            return $this;
        }

        $firewalls = (array) $config['firewalls'];
        foreach ($firewalls as $name => $firewall) {
            $this->createFirewall($name, $firewall);
        }

        return $this;
    }

    /**
     * Returns the ACL provider.
     *
     * @return MutableAclProvider|null
     */
    public function getACLProvider()
    {
        return $this->aclprovider;
    }

    /**
     * Creates the ACL provider is need.
     *
     * @param  array $config
     * @return $this
     */
    private function createACLProvider(array $config)
    {
        if (isset($config['acl'])
            && isset($config['acl']['connection'])
            && 'default' === $config['acl']['connection']
            && null !== $this->getApplication()->getEntityManager()
        ) {
            if (false === $this->application->getContainer()->has('security.acl_provider')) {
                $this->aclprovider = new MutableAclProvider(
                    $this->getApplication()->getEntityManager()->getConnection(),
                    new PermissionGrantingStrategy(),
                    [
                        'class_table_name'         => 'acl_classes',
                        'entry_table_name'         => 'acl_entries',
                        'oid_table_name'           => 'acl_object_identities',
                        'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
                        'sid_table_name'           => 'acl_security_identities',
                    ]
                );
            } else {
                $this->aclprovider = $this->application->getContainer()->get('security.acl_provider');
            }
        }

        return $this;
    }

    /**
     * Registers a new Firewall for the context.
     */
    private function registerFirewall()
    {
        $firewall = new Firewall($this->firewallmap, $this->getDispatcher());

        $this->application->getContainer()->set('security.firewall', $firewall);
        if (!$this->getDispatcher()->isRestored()) {
            $this->getDispatcher()->addListener(
                'frontcontroller.request',
                ['@security.firewall', 'onKernelRequest']
            );
        }
    }

    /**
     * Returns the current security token.
     *
     * @return TokenInterface|null A TokenInterface instance or null if no authentication information is available
     */
    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }

    /**
     * Sets the authentication token.
     *
     * @param TokenInterface $token A TokenInterface token, or null if no further authentication
     *                              information should be stored
     */
    public function setToken(TokenInterface $token = null)
    {
        return $this->tokenStorage->setToken($token);
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted($attributes, $object = null)
    {
        return $this->authorizationChecker->isGranted($attributes, $object);
    }
}
