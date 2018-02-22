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

namespace BackBee\Util\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

use BackBee\DependencyInjection\ContainerInterface;
use BackBee\Doctrine\RepositoryFactory;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Utils\Collection\Collection;

/**
 * Utility class to create a new Doctrine entity manager.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class EntityManagerCreator
{

    /**
     * Creates a new Doctrine entity manager.
     *
     * @param  array           $options Options provided to get an entity manager, keys should be :
     *                                   - entity_manager EntityManager Optional, an already defined EntityManager
     *                                   - connection     Connection    Optional, an already initialized db connection
     *                                   - proxy_dir      string        The proxy directory
     *                                   - proxy_ns       string        The namespace for Doctrine proxy
     *                                   - charset        string        Optional, the charset to use
     *                                   - collation      string        Optional, the collation to use
     *                                   - ...            mixed         All the required parameter to open a new
     *                                                                  connection
     * @param  LoggerInterface $logger  Optional logger
     * @param  EventManager    $evm     Optional event manager
     *
     * @return EntityManager
     *
     * @throws InvalidArgumentException if an EntityManager instance can not be returned.
     */
    public static function create(
        array $options = [],
        LoggerInterface $logger = null,
        EventManager $evm = null,
        ContainerInterface $container = null
    ) {
        if (isset($options['entity_manager'])) {
            $entityManager = $options['entity_manager'];
            if (!($entityManager instanceof EntityManager)) {
                throw new InvalidArgumentException(
                    'Invalid EntityManager provided',
                    InvalidArgumentException::INVALID_ARGUMENT
                );
            }
        } else {
            // Init ORM Configuration
            $config = self::getORMConfiguration($options, $logger, $container);

            if (isset($options['connection'])) {
                $connection = $options['connection'];
                if (!($connection instanceof Connection)) {
                    throw new InvalidArgumentException(
                        'Invalid Connection provided',
                        InvalidArgumentException::INVALID_ARGUMENT
                    );
                }

                // An existing connection is provided
                $entityManager = self::createEntityManagerWithConnection($options['connection'], $config, $evm);
            } else {
                $entityManager = self::createEntityManagerWithParameters($options, $config, $evm);
            }
        }

        // Force a connection to the database
        $entityManager->getConnection()->errorInfo();

        // Fix collation and chrset issue on mysql
        self::setConnectionCharset($entityManager->getConnection(), $options);
        self::setConnectionCollation($entityManager->getConnection(), $options);

        // Add regexp function to sqlite
        if ('sqlite' === $entityManager->getConnection()->getDatabasePlatform()->getName()) {
            self::expandSqlite($entityManager->getConnection());
        }

        return $entityManager;
    }

    /**
     * Custom SQLite logic.
     *
     * @param Connection $connection
     */
    private static function expandSqlite(Connection $connection)
    {
        // add support for REGEXP operator
        $connection->getWrappedConnection()->sqliteCreateFunction(
            'regexp',
            function ($pattern, $data, $delimiter = '~', $modifiers = 'isuS') {
                if (isset($pattern, $data)) {
                    return (preg_match(sprintf('%1$s%2$s%1$s%3$s', $delimiter, $pattern, $modifiers), $data) > 0);
                }

                return;
            }
        );
    }

    /**
     * Returns a new ORM Configuration.
     *
     * @param  array $options Optional, the options to create the new Configuration.
     *
     * @return Configuration
     */
    private static function getORMConfiguration(
        array $options = [],
        LoggerInterface $logger = null,
        ContainerInterface $container = null
    ) {
        $config = new Configuration();

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([], false));
        $config->setRepositoryFactory(new RepositoryFactory(isset($options['orm']) ? (array) $options['orm'] : []));

        if ($logger instanceof SQLLogger) {
            $config->setSQLLogger($logger);
        }

        if (isset($options['proxy_dir'])) {
            $config->setProxyDir($options['proxy_dir']);
        }

        if (isset($options['proxy_ns'])) {
            $config->setProxyNamespace($options['proxy_ns']);
        }

        if (isset($options['orm'])) {
            if (isset($options['orm']['proxy_namespace'])) {
                $config->setProxyNamespace($options['orm']['proxy_namespace']);
            }

            if (isset($options['orm']['proxy_dir'])) {
                $config->setProxyDir($options['orm']['proxy_dir']);
            }

            if (isset($options['orm']['auto_generate_proxy_classes'])) {
                $config->setAutoGenerateProxyClasses($options['orm']['auto_generate_proxy_classes']);
            }

            if (isset($options['orm']['metadata_cache_driver']) && is_array($options['orm']['metadata_cache_driver'])) {
                if (isset($options['orm']['metadata_cache_driver']['type'])) {
                    if ('service' === $options['orm']['metadata_cache_driver']['type']
                        && isset($options['orm']['metadata_cache_driver']['id'])
                    ) {
                        $service = null;
                        $serviceId = $options['orm']['metadata_cache_driver']['id'];
                        if (is_object($serviceId)) {
                            $service = $serviceId;
                        } elseif (is_string($serviceId)) {
                            $serviceId = str_replace('@', '', $serviceId);
                            if (null !== $container && $container->has($serviceId)) {
                                $service = $container->get($serviceId);
                            }
                        }

                        if (null !== $service) {
                            $config->setMetadataCacheImpl($service);
                        }
                    }
                }
            }

            if (isset($options['orm']['query_cache_driver']) && is_array($options['orm']['query_cache_driver'])) {
                if (isset($options['orm']['query_cache_driver']['type'])) {
                    if ('service' === $options['orm']['query_cache_driver']['type']
                        && isset($options['orm']['query_cache_driver']['id'])
                    ) {
                        $serviceId = str_replace('@', '', $options['orm']['query_cache_driver']['id']);
                        if (null !== $container && $container->has($serviceId)) {
                            $config->setQueryCacheImpl($container->get($serviceId));
                        }
                    }
                }
            }
        }

        return self::addCustomFunctions($config, $options);
    }

    /**
     * Adds userdefined functions.
     *
     * @param  Configuration $config
     * @param  array         $options
     *
     * @return Configuration
     */
    private static function addCustomFunctions(Configuration $config, array $options = [])
    {
        $strFcts = (array) Collection::get($options, 'orm:entity_managers:default:dql:string_functions');
        foreach ($strFcts as $name => $class) {
            if (class_exists($class)) {
                $config->addCustomStringFunction($name, $class);
            }
        }

        $numFcts = (array) Collection::get($options, 'orm:entity_managers:default:dql:numeric_functions');
        foreach ($numFcts as $name => $class) {
            if (class_exists($class)) {
                $config->addCustomNumericFunction($name, $class);
            }
        }

        $datetimeFcts = (array) Collection::get($options, 'orm:entity_managers:default:dql:datetime_functions');
        foreach ($datetimeFcts as $name => $class) {
            if (class_exists($class)) {
                $config->addCustomDatetimeFunction($name, $class);
            }
        }

        return $config;
    }

    /**
     * Returns a new EntityManager with the provided connection.
     *
     * @param  Connection    $connection
     * @param  Configuration $config
     * @param  EventManager  $evm        Optional event manager
     *
     * @return EntityManager
     *
     * @throws InvalidArgumentException if $entityManager can not be created.
     */
    private static function createEntityManagerWithConnection(
        Connection $connection,
        Configuration $config,
        EventManager $evm = null
    ) {
        try {
            return EntityManager::create($connection, $config, $evm);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                'Unable to create new EntityManager with provided Connection',
                InvalidArgumentException::INVALID_ARGUMENT,
                $e
            );
        }
    }

    /**
     * Returns a new EntityManager with the provided parameters.
     *
     * @param  array         $options
     * @param  Configuration $config
     * @param  EventManager  $evm
     *
     * @return EntityManager
     *
     * @throws InvalidArgumentException Occurs if $entityManager can not be created
     */
    private static function createEntityManagerWithParameters(
        array $options,
        Configuration $config,
        EventManager $evm = null
    ) {
        try {
            return EntityManager::create(self::randomizeServerPoolConnection($options), $config, $evm);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                'Unable to create new EntityManager with provided parameters',
                InvalidArgumentException::INVALID_ARGUMENT,
                $e
            );
        }
    }

    /**
     * If an array og db hosts is provided, randomize the selection of one of them.
     *
     * @param  array $options
     *
     * @return array
     */
    private static function randomizeServerPoolConnection($options)
    {
        if (array_key_exists('host', $options) && is_array($options['host'])) {
            if (1 < count($options['host'])) {
                shuffle($options['host']);
            }
            $options['host'] = reset($options['host']);
        }

        return $options;
    }

    /**
     * Sets the character set for the provided connection.
     *
     * @param  Connection $connection
     * @param  array      $options
     *
     * @throws InvalidArgumentException if charset is invalid
     */
    private static function setConnectionCharset(Connection $connection, array $options = [])
    {
        if (isset($options['charset'])) {
            try {
                if ('pdo_mysql' === $connection->getDriver()->getName()) {
                    $connection->executeQuery(
                        'SET SESSION character_set_client = "' . addslashes($options['charset']) . '";'
                    );
                    $connection->executeQuery(
                        'SET SESSION character_set_connection = "' . addslashes($options['charset']) . '";'
                    );
                    $connection->executeQuery(
                        'SET SESSION character_set_results = "' . addslashes($options['charset']) . '";'
                    );
                }
            } catch (\Exception $e) {
                throw new InvalidArgumentException(
                    sprintf('Invalid database character set `%s`', $options['charset']),
                    InvalidArgumentException::INVALID_ARGUMENT,
                    $e
                );
            }
        }
    }

    /**
     * Sets the collation for the provided connection.
     *
     * @param  Connection $connection
     * @param  array      $options
     *
     * @throws InvalidArgumentException if collation is invalid
     */
    private static function setConnectionCollation(Connection $connection, array $options = array())
    {
        if (isset($options['collation'])) {
            try {
                if ('pdo_mysql' === $connection->getDriver()->getName()) {
                    $connection->executeQuery(
                        'SET SESSION collation_connection = "' . addslashes($options['collation']) . '";'
                    );
                }
            } catch (\Exception $e) {
                throw new InvalidArgumentException(
                    sprintf('Invalid database collation `%s`', $options['collation']),
                    InvalidArgumentException::INVALID_ARGUMENT,
                    $e
                );
            }
        }
    }
}
