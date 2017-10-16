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

namespace BackBee\Bundle;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

use BackBee\ApplicationInterface;
use BackBee\Bundle\Event\BundleStartEvent;
use BackBee\Config\Config;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\Routing\RouteCollection;

/**
 * Abstract class for BackBee's bundle.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
abstract class AbstractBundle implements BundleInterface
{

    /**
     * Application this bundle belongs to.
     *
     * @var ApplicationInterface
     */
    private $application;

    /**
     * Bundle base directory.
     *
     * @var string
     */
    private $baseDir;

    /**
     * Bundle identifier.
     *
     * @var string
     */
    private $id;

    /**
     * Define if this bundle is already started or not.
     *
     * @var boolean
     */
    private $started;

    /**
     * Formatted list of this bundle exposed actions.
     *
     * @var array
     */
    private $exposedActions;

    /**
     * Indexed by a unique name (controller name + action name), it contains every
     * (controller; action) callbacks.
     *
     * @var array
     */
    private $exposedActionsCallbacks;

    /**
     * AbstractBaseBundle constructor.
     *
     * @param ApplicationInterface $application Application to link current bundle with.
     * @param string|null          $id          Optional, the bundle identifier.
     * @param string|null          $baseDir     Optional, the base directory of the bundle.
     */
    public function __construct(ApplicationInterface $application, $id = null, $baseDir = null)
    {
        $this->id = $id;
        $this->baseDir = $baseDir;
        $this->application = $application;

        $this->started = false;
        $this->exposedActions = [];
        $this->exposedActionsCallbacks = [];

        $bundleLoader = $this->getContainer()->get('bundle.loader');
        $bundleLoader->loadConfigDefinition($this->getConfigServiceId(), $this->getBaseDirectory());

        $this->initBundleExposedActions();
    }

    /**
     * Returns the bundle id.
     *
     * @return string The bundle id.
     */
    public function getId()
    {
        if (null === $this->id) {
            $this->id = basename($this->getBaseDirectory());
        }

        return $this->id;
    }

    /**
     * Returns bundle base directory.
     *
     * @return string The bundle base directory.
     */
    public function getBaseDirectory()
    {
        if (null === $this->baseDir) {
            $bundleLoader = $this->getContainer()->get('bundle.loader');
            $this->baseDir = $bundleLoader->buildBundleBaseDirectoryFromClassname(ClassUtils::getRealClass($this));
        }

        return $this->baseDir;
    }

    /**
     * Returns the default path to the resources folder.
     *
     * @return string The bundle default resources directory.
     */
    public function getResourcesDirectory()
    {
        return $this->getBaseDirectory() . DIRECTORY_SEPARATOR . 'Resources';
    }

    /**
     * Returns bundle property if you provide key, else every properties;
     * a bundle property is any key/value defined in 'bundle' section of config.yml.
     *
     * @param  string|null       $key The name of the property.
     *
     * @return string|array|null      Value of the property if key is not null,
     *                                else an array which contains every properties.
     */
    public function getProperty($key = null)
    {
        $properties = $this->getConfig()->getSection('bundle') ?: [];
        if (null === $key) {
            return $properties;
        }

        return isset($properties[$key]) ? $properties[$key] : null;
    }

    /**
     * Returns the config object of the bundle.
     *
     * @return Config The Config of the bundle.
     */
    public function getConfig()
    {
        return $this->getContainer()->get($this->getConfigServiceId());
    }

    /**
     * Bundle's config service id getter.
     *
     * @return string
     */
    public function getConfigServiceId()
    {
        return strtolower(
            str_replace(
                '%bundle_service_id%',
                str_replace(
                    '%bundle_name%',
                    $this->getId(),
                    BundleInterface::BUNDLE_SERVICE_ID_PATTERN
                ),
                BundleInterface::CONFIG_SERVICE_ID_PATTERN
            )
        );
    }

    /**
     * Bundle base directory getter.
     *
     * @return string
     */
    public function getConfigDirectory()
    {
        $directory = $this->getBaseDirectory() . DIRECTORY_SEPARATOR . BundleInterface::CONFIG_DIRECTORY_NAME;
        if (false === is_dir($directory)) {
            $directory = $this->getBaseDirectory() . DIRECTORY_SEPARATOR . BundleInterface::OLD_CONFIG_DIRECTORY_NAME;
        }

        return $directory;
    }

    /**
     * Defines if current bundle require different config per site or not.
     *
     * @return boolean True if current bundle require a different config per site, else false
     */
    public function isConfigPerSite()
    {
        return (null !== $this->getProperty('config_per_site'))
            ? (bool) $this->getProperty('config_per_site')
            : BundleInterface::DEFAULT_CONFIG_PER_SITE_VALUE;
    }

    /**
     * Returns the application current bundle is registered into.
     *
     * @return ApplicationInterface Application that own current bundle.
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Current DI container.
     *
     * @return ContainerInterface
     *
     * @codeCoverageIgnore
     */
    public function getContainer()
    {
        return $this->getApplication()->getContainer();
    }

    /**
     * Current bundle entity manager.
     *
     * @return EntityManager
     *
     * @codeCoverageIgnore
     */
    public function getEntityManager()
    {
        return $this->getApplication()->getEntityManager();
    }

    /**
     * Defines if current bundle is started or not.
     *
     * @return boolean True if the bundle is started, else false.
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Switch current bundle as started (so self::isStarted() will return true).
     */
    public function started()
    {
        $this->started = true;

        if ($this->getContainer()->has('event.dispatcher')) {
            $this->getContainer()
                ->get('event.dispatcher')
                ->dispatch(sprintf('bundle.%s.started', $this->getId()), new BundleStartEvent($this));
        }
    }

    /**
     * Checks if current bundle is enabled or not (it also defines if it is
     * loaded by BundleLoader into application).
     *
     * @return boolean true If the bundle is enabled, else false.
     */
    public function isEnabled()
    {
        return true === $this->getProperty('enable');
    }

    /**
     * Enable property setter.
     *
     * @param  boolean $enable
     *
     * @return AbstractBundle
     */
    public function setEnable($enable)
    {
        $properties = $this->getProperty();
        $properties['enable'] = (boolean) $enable;
        $this->getConfig()->setSection('bundle', $properties, true);

        return $this;
    }

    /**
     * Category property getter.
     *
     * @return array
     */
    public function getCategory()
    {
        return (array) $this->getProperty('category');
    }

    /**
     * category property setter.
     *
     * @param  string|array $category
     *
     * @return AbstractBundle
     */
    public function setCategory($category)
    {
        $properties = $this->getProperty();
        $properties['category'] = (array) $category;
        $this->getConfig()->setSection('bundle', $properties, true);

        return $this;
    }

    /**
     * Config_per_site property setter.
     *
     * @param  boolean $perSite
     *
     * @return AbstractBundle
     */
    public function setConfigPerSite($perSite)
    {
        $properties = $this->getProperty();
        $properties['config_per_site'] = (boolean) $perSite;
        $this->getConfig()->setSection('bundle', $properties, true);

        return $this;
    }

    /**
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $obj = new \stdClass();
        $obj->id = $this->getId();
        $obj->enable = true;
        $obj->config_per_site = false;
        $obj->category = [];
        $obj->exposed_actions = $this->getExposedActionsMapping();
        $obj->thumbnail = $this->getContainer()
            ->get('routing')
            ->getUri('img/extnd-x/picto-extnd.png', null, null, RouteCollection::RESOURCE_URL);

        foreach ($this->getProperty() as $key => $value) {
            if ('bundle_loader_recipes' !== $key) {
                $obj->$key = $value;
            }
        }

        if (property_exists($obj, 'admin_entry_point')) {
            $entrieDefinition = explode('.', $obj->admin_entry_point);
            $obj->admin_entry_point = (new BundleControllerResolver($this->getApplication()))
                ->resolveBaseAdminUrl(
                    $obj->id,
                    $entrieDefinition[0],
                    isset($entrieDefinition[1]) ? $entrieDefinition[1] : 'index'
                );
        }

        return $obj;
    }

    /**
     * Returns a unique identifier for this domain object.
     *
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->getType() . '(' . $this->getIdentifier() . ')';
    }

    /**
     * Returns the unique identifier for this object.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * Returns a type for the domain object. Typically, this is the PHP class name.
     *
     * @return string cannot return null
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * Checks for an explicit objects equality.
     *
     * @param  ObjectIdentityInterface $identity
     *
     * @return Boolean
     */
    public function equals(ObjectIdentityInterface $identity)
    {
        return $this->getType() === $identity->getType()
                && $this->getIdentifier() === $identity->getIdentifier();
    }

    /**
     * Bundle's exposed actions getter.
     *
     * @return array
     */
    public function getExposedActionsMapping()
    {
        return $this->exposedActions;
    }

    /**
     * Returns the associated callback if "controller name/action name" couple is valid.
     *
     * @param  string $controllerName the controller name (ex.: BackBee\Controller\FrontController => front)
     * @param  string $actionName     the action name (ex.: FrontController::defaultAction => default)
     *
     * @return callable|null          the callback if there is one associated to
     *                                "controller name/action name" couple, else null.
     */
    public function getExposedActionCallback($controllerName, $actionName)
    {
        $uniqueName = $controllerName . '_' . $actionName;

        return isset($this->exposedActionsCallbacks[$uniqueName]) ? $this->exposedActionsCallbacks[$uniqueName] : null;
    }

    /**
     * Initialize bundle exposed actions by building exposed_actions array and exposed_actions_callback array.
     */
    private function initBundleExposedActions()
    {
        if ($this->isEnabled()) {
            foreach ((array) $this->getProperty('exposed_actions') as $controllerId => $actions) {
                if (!$this->getContainer()->has($controllerId)) {
                    throw new \InvalidArgumentException(
                        "Exposed controller with id `$controllerId` not found for " . $this->getId()
                    );
                }

                $controller = $this->getContainer()->get($controllerId);
                $this->formatAndInjectExposedAction($controller, $actions);
            }
        }
    }

    /**
     * Format a valid map between controller and actions and hydrate.
     *
     * @param BundleExposedControllerInterface $controller
     * @param array                            $actions
     */
    private function formatAndInjectExposedAction($controller, $actions)
    {
        $controllerNs = explode('\\', get_class($controller));
        $controllerId = str_replace('controller', '', strtolower(array_pop(($controllerNs))));
        $this->exposedActions[$controllerId] = ['actions' => []];

        if ($controller instanceof BundleExposedControllerInterface) {
            $this->exposedActions[$controllerId]['label'] = $controller->getLabel();
            $this->exposedActions[$controllerId]['description'] = $controller->getDescription();
            array_unshift($actions, 'indexAction');
            $actions = array_unique($actions);
        }

        foreach ($actions as $action) {
            if (method_exists($controller, $action)) {
                $actionId = str_replace('action', '', strtolower($action));
                $uniqueName = $controllerId . '_' . $actionId;

                $this->exposedActionsCallbacks[$uniqueName] = [$controller, $action];
                $this->exposedActions[$controllerId]['actions'][] = $actionId;
            }
        }
    }
}
