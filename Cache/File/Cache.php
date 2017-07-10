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

namespace BackBee\Cache\File;

use Psr\Log\LoggerInterface;

use BackBee\Cache\AbstractCache;
use BackBee\Cache\Exception\CacheException;
use BackBee\Utils\StringUtils;

/**
 * Filesystem cache adapter.
 * A simple cache system storing data in files, it does not provide tag or expire features
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Cache extends AbstractCache
{
    /**
     * The cache directory.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * File adapter options.
     *
     * @var array
     */
    protected $instanceOptions = [
        'cachedir'          => null,
        'cacheautogenerate' => true,
    ];

    /**
     * Class constructor.
     *
     * @param  array                $options Initial options for the cache adapter:
     *                                          - cachedir          string  The cache directory
     *                                          - cacheautogenerate boolean
     * @param  string|null          $context An optional cache context
     * @param  LoggerInterface|null $logger  An optional logger
     *
     * @throws CacheException                if the cache directory doesn't exist, can not
     *                                       be created or is not writable.
     */
    public function __construct(array $options = [], $context = null, LoggerInterface $logger = null)
    {
        parent::__construct($options, $context, $logger);

        $this->cacheDir = $this->getOption('cachedir');
        if (null !== $this->getContext()) {
            $this->cacheDir .= DIRECTORY_SEPARATOR . StringUtils::toPath($this->getContext());
        }

        if (true === $this->getOption('cacheautogenerate')
            && false === is_dir($this->cacheDir)
            && false === @mkdir($this->cacheDir, 0755, true)
        ) {
            throw new CacheException(sprintf('Unable to create the cache directory `%s`.', $this->cacheDir));
        }

        if (true === $this->getOption('cacheautogenerate')
            && false === is_writable($this->cacheDir)
        ) {
            throw new CacheException(sprintf('Unable to write in the cache directory `%s`.', $this->cacheDir));
        }

        $this->log('info', sprintf('File cache system initialized with directory set to `%s`.', $this->cacheDir));
    }

    /**
     * Returns the available cache for the given id if found returns false else.
     *
     * @param  string         $id          Cache id
     * @param  boolean        $bypassCheck Allow to find cache without test it before
     *                                     (default: false)
     * @param  \DateTime|null $expire      Optionnal, the expiration time (now by default)
     *
     * @return string|false
     */
    public function load($id, $bypassCheck = false, \DateTime $expire = null)
    {
        if (null === $expire) {
            $expire = new \DateTime();
        }

        $last_timestamp = $this->test($id);
        if (true === $bypassCheck
            || 0 === $last_timestamp
            || $expire->getTimestamp() <= $last_timestamp
        ) {
            if (false !== $data = @file_get_contents($this->getCacheFile($id))) {
                $this->log('debug', sprintf('Reading file cache data for id `%s`.', $id));

                return $data;
            }

            $this->log('warning', sprintf('Unable to read data in file cache for id `%s`.', $id));
        } else {
            $this->log('debug', sprintf(
                'None available file cache found for id `%s` newer than %s.',
                $id,
                $expire->format('d/m/Y H:i')
            ));
        }

        return false;
    }

    /**
     * Tests if a cache is available or not (for the given id).
     *
     * @param string $id Cache id
     *
     * @return int|false The last modified timestamp of the available cache record
     */
    public function test($id)
    {
        $result = false;
        $cache_file = $this->getCacheFile($id);
        if (is_readable($cache_file)) {
            $result = stat($cache_file);
            $result = $result['mtime'];
        }

        return $result;
    }

    /**
     * Save some string datas into a cache record.
     *
     * @param string $id       Cache id
     * @param string $data     Datas to cache
     * @param int    $lifetime Optional, the specific lifetime for this record
     *                         (by default null, infinite lifetime)
     * @param string $tag      Optional, an associated tag to the data stored
     *
     * @return boolean         True if cache is stored false otherwise
     */
    public function save($id, $data, $lifetime = null, $tag = null)
    {
        if (null !== $lifetime || null !== $tag) {
            $this->log(
                'warning',
                sprintf('Lifetime and tag features are not available for cache adapter `%s`.', get_class($this))
            );
        }

        if (false !== $result = @file_put_contents($this->getCacheFile($id), $data)) {
            $this->log(
                'debug',
                sprintf('Storing data in cache for id `%s`.', $id)
            );
        } else {
            $this->log(
                'warning',
                sprintf('Unable to save data to file cache for id `%s` in directory `%s`.', $id, $this->cacheDir)
            );
        }

        return (false !== $result);
    }

    /**
     * Removes a cache record.
     *
     * @param string $id Cache id
     *
     * @return boolean   True if cache is removed false otherwise
     */
    public function remove($id)
    {
        if (true === $result = @unlink($this->getCacheFile($id))) {
            $this->log('debug', sprintf('Cache data removed for id `%s`.', $id));
        }

        return $result;
    }

    /**
     * Clears all cache records.
     *
     * @return boolean True if cache is cleared false otherwise
     */
    public function clear()
    {
        $result = false;
        if (false !== $files = @scandir($this->cacheDir)) {
            foreach ($files as $file) {
                $file = $this->cacheDir.DIRECTORY_SEPARATOR.$file;
                if (is_dir($file)) {
                    continue;
                }

                if (false === @unlink($file)) {
                    $this->log('warning', sprintf('Enable to remove cache file `%s`.', $file));
                } else {
                    $result = true;
                }
            }
        }

        if (true === $result) {
            $this->log('debug', sprintf('File system cache cleared in `%s`.', $this->cacheDir));
        }

        return $result;
    }

    /**
     * Returns the cache file if a cache is available for the given id.
     *
     * @param string $id Cache id
     *
     * @return string    The file path of the available cache record
     */
    private function getCacheFile($id)
    {
        $cachefile = $this->cacheDir . DIRECTORY_SEPARATOR . $id;

        return $cachefile;
    }
}
