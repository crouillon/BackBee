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

namespace BackBee\Cache;

use Psr\Log\LoggerInterface;

use BackBee\Cache\Exception\CacheException;

/**
 * Abstract class for cache adapters.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * Cache adapter options.
     *
     * @var array
     */
    protected $instanceOptions = [];

    /**
     * Default cache apdater options.
     *
     * @var array
     */
    private $defaultInstanceOptions = [
        'min_lifetime' => null,
        'max_lifetime' => null,
    ];

    /**
     * A logger.
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * A cache context.
     *
     * @var string
     */
    private $context = null;

    /**
     * Class constructor.
     *
     * @param array                $options Optional, an array of options required to construct the cache adapter.
     * @param string|null          $context An optional cache context.
     * @param LoggerInterface|null $logger  An optional logger.
     */
    public function __construct(array $options = [], $context = null, LoggerInterface $logger = null)
    {
        if (property_exists($this, '_instance_options')) {
            @trigger_error('The protected property '.get_class($this).'::_instance_options is deprecated '
                    . 'since 1.4 and will be removed in 1.5, use AbstractCache::instanceOptions instead', E_USER_DEPRECATED);
            $this->instanceOptions = $this->_instance_options;
        }

        $this->setContext($context)
            ->setLogger($logger)
            ->setOptions(array_merge($this->defaultInstanceOptions, $this->getOptions()));

        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    /**
     * Returns the available cache for the given id if found returns FALSE else.
     *
     * @param  string    $id          Cache id.
     * @param  boolean   $bypassCheck Allow to find cache without test it before.
     * @param  \DateTime $expire      Optionnal, the expiration time (now by default).
     *
     * @return string|false
     */
    abstract public function load($id, $bypassCheck = false, \DateTime $expire = null);

    /**
     * Tests if a cache is available or not (for the given id).
     *
     * @param  string $id Cache id.
     *
     * @return int|false  The last modified timestamp of the available cache record
     *                    (0 infinite expiration date).
     */
    abstract public function test($id);

    /**
     * Saves some string datas into a cache record.
     *
     * @param string $id       Cache id.
     * @param string $data     Datas to cache.
     * @param int    $lifetime Optional, the specific lifetime for this record
     *                         (by default null, infinite lifetime).
     * @param string $tag      Optional, an associated tag to the data stored.
     *
     * @return boolean         True if cache is stored false otherwise.
     */
    abstract public function save($id, $data, $lifetime = null, $tag = null);

    /**
     * Removes a cache record.
     *
     * @param string $id Cache id.
     *
     * @return boolean TRUE if cache is removed FALSE otherwise.
     */
    abstract public function remove($id);

    /**
     * Clears all cache records.
     *
     * @return boolean True if cache is cleared false otherwise.
     */
    abstract public function clear();

    /**
     * Sets the cache logger.
     *
     * @param  LoggerInterface|null $logger
     *
     * @return CacheInterface
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the cache logger.
     *
     * @return LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Returns the cache context.
     *
     * @return string|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the cache context.
     *
     * @param string|null $context
     *
     * @return CacheInterface
     */
    public function setContext($context = null)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Logs a message on provided level if a logger is defined.
     *
     * @param string $level   The log level.
     * @param string $message The message to log.
     * @param array  $context Optional, the logging context (default ['cache']).
     */
    public function log($level, $message, array $context = ['cache'])
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Returns the expiration timestamp.
     *
     * @param  int     $lifetime      A lifetime duration in seconds.
     * @param  boolean $bypassControl Optional, if true bypass the limits control (default: false).
     *
     * @return int                    The expire datetime or 0 if no expiration.
     */
    public function getExpireTime($lifetime = null, $bypassControl = false)
    {
        $expire = 0;
        if (!empty($lifetime)) {
            $now = new \DateTime();

            if (0 < $lifetime) {
                $now->add(new \DateInterval('PT'.$lifetime.'S'));
            } else {
                $now->sub(new \DateInterval('PT'.(-1 * $lifetime).'S'));
            }

            $expire = $now->getTimestamp();
        }

        if (true === $bypassControl) {
            return $expire;
        }

        return $this->getControledExpireTime($expire);
    }

    /**
     * Control the lifetime against min and max lifetime options if provided.
     *
     * @param  int $lifetime
     *
     * @return int
     */
    public function getControlledLifetime($lifetime)
    {
        $minLifetime = $this->getOption('min_lifetime');
        $maxLifetime = $this->getOption('max_lifetime');

        if (null !== $minLifetime && $minLifetime > $lifetime) {
            $lifetime = $minLifetime;
        } elseif (null !== $maxLifetime && $maxLifetime < $lifetime) {
            $lifetime = $maxLifetime;
        }

        return $lifetime;
    }

    /**
     * Control the expiration time against min and max lifetime options if provided.
     *
     * @param  int $expire
     *
     * @return int
     */
    private function getControlledExpireTime($expire)
    {
        $lifetime = $this->getControledLifetime($expire - time());

        if (0 < $lifetime) {
            return time() + $lifetime;
        }

        return $expire;
    }

    /**
     * Returns an option value or all options.
     *
     * @param  string $name An option name.
     *
     * @return mixed
     *
     * @throws CacheException if a provided option name is unknown for this adapter.
     */
    protected function getOption($name)
    {
        if (!array_key_exists($name, $this->getOptions())) {
            throw new CacheException(
                sprintf('Unknown option %s for cache adapter %s.', $name, get_class($this))
            );
        }

        return $this->instanceOptions[$name];
    }

    /**
     * Sets an option value for this adapter.
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return CacheInterface
     *
     * @throws CacheException if a provided option name is unknown for this adapter.
     */
    protected function setOption($name, $value = null)
    {
        $this->getOption($name);
        $this->instanceOptions[$name] = $value;

        return $this;
    }

    /**
     * Returns the options for this cache adapter.
     *
     * @return array
     */
    protected function getOptions()
    {
        return $this->instanceOptions;
    }

    /**
     * Sets the options of this cache adapter.
     *
     * @param  array $options
     *
     * @return CacheInterface
     */
    protected function setOptions(array $options = [])
    {
        $this->instanceOptions = $options;

        return $this;
    }

    /**
     * Alias to AbstractCache::getControlledLifetime.
     *
     * @deprecated since version 1.4, to be removed in 1.5.
     * @codeCoverageIgnore
     */
    public function getControledLifetime($lifetime)
    {
        return $this->getControlledLifetime($lifetime);
    }

    /**
     * Alias to AbstractCache::getControlledExpireTime.
     *
     * @deprecated since version 1.4, to be removed in 1.5.
     * @codeCoverageIgnore
     */
    private function getControledExpireTime($expire)
    {
        return $this->getControlledExpireTime($expire);
    }
}
