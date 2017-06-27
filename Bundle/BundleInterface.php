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

use BackBee\ApplicationInterface;
use BackBee\Security\Acl\Domain\ObjectIdentifiableInterface;

/**
 * BundleInterface which define somes methods to implements for BackBee bundles,
 * it also define some constants.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
interface BundleInterface extends ObjectIdentifiableInterface, \JsonSerializable
{

    /**
     * service id pattern (for bundle and bundle's config).
     */
    const BUNDLE_SERVICE_ID_PATTERN = 'bundle.%bundle_name%';
    const CONFIG_SERVICE_ID_PATTERN = '%bundle_service_id%.config';
    const BUNDLE_ADMIN_URL_PATTERN = '/bundle/%bundle_id%/%controller_id%/%action_id%';

    /**
     * Config directories names.
     */
    const CONFIG_DIRECTORY_NAME = 'Config';
    const OLD_CONFIG_DIRECTORY_NAME = 'Ressources';
    const DEFAULT_CONFIG_PER_SITE_VALUE = true;

    /**
     * Returns the bundle id.
     *
     * @return string The bundle id.
     */
    public function getId();

    /**
     * Returns bundle base directory.
     *
     * @return string The bundle base directory.
     */
    public function getBaseDirectory();

    /**
     * Returns bundle property if you provide key, else every properties;
     * a bundle property is any key/value defined in 'bundle' section of config.yml.
     *
     * @param  string|null       $key The name of the property.
     *
     * @return string|array|null      Value of the property if key is not null,
     *                                else an array which contains every properties.
     */
    public function getProperty($key = null);

    /**
     * Method to call when we get the bundle for the first time.
     */
    public function start();

    /**
     * Method to call before stop or destroy of current bundle.
     */
    public function stop();

    /**
     * Returns the application current bundle is registered into.
     *
     * @return ApplicationInterface Application that own current bundle.
     */
    public function getApplication();

    /**
     * Current bundle entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager();

    /**
     * Defines if current bundle is started or not.
     *
     * @return boolean True if the bundle is started, else false.
     */
    public function isStarted();

    /**
     * Switch current bundle as started (so self::isStarted() will return true).
     */
    public function started();

    /**
     * Defines if current bundle require different config per site or not.
     *
     * @return boolean True if current bundle require a different config per site, else false
     */
    public function isConfigPerSite();

    /**
     * Checks if current bundle is enabled or not (it also defines if it is
     * loaded by BundleLoader into application).
     *
     * @return boolean true If the bundle is enabled, else false.
     */
    public function isEnabled();
}
