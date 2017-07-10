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

/**
 * Interface for BackBee Cache
 *
 * @author Mickaël Andrieu
 */
interface CacheInterface
{

    /**
     * Returns the available cache for the given id if found returns false else.
     *
     * @param string    $id          Cache id
     * @param boolean   $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire      Optionnal, the expiration time (now by default)
     *
     * @return string|false
     */
    public function load($id, $bypassCheck, \DateTime $expire);

    /**
     * Tests if a cache is available or not (for the given id).
     *
     * @param string $id Cache id
     *
     * @return int|false The last modified timestamp of the available cache record
     *                   (0 infinite expiration date)
     */
    public function test($id);

    /**
     * Saves some string datas into a cache record.
     *
     * @param string $id       Cache id
     * @param string $data     Datas to cache
     * @param int    $lifetime Optional, the specific lifetime for this record
     *                         (by default null, infinite lifetime)
     * @param string $tag      Optional, an associated tag to the data stored
     *
     * @return boolean         True if cache is stored false otherwise
     */
    public function save($id, $data, $lifetime, $tag);

    /**
     * Removes a cache record.
     *
     * @param string $id Cache id
     *
     * @return boolean   True if cache is removed false otherwise
     */
    public function remove($id);

    /**
     * Clears all cache records.
     *
     * @return boolean True if cache is cleared false otherwise
     */
    public function clear();

    /**
     * Sets the cache logger.
     *
     * @param  LoggerInterface $logger
     *
     * @return CacheInterface
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * Gets the cache logger if exists.
     *
     * @return LoggerInterface|null
     */
    public function getLogger();

    /**
     * Returns the cache context if exists.
     *
     * @return string|null
     */
    public function getContext();

    /**
     * Sets the cache context.
     *
     * @param  string $context
     *
     * @return CacheInterface
     */
    public function setContext($context);
}
