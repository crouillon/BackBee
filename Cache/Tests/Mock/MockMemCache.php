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

namespace BackBee\Cache\Tests\Mock;

/**
 * A mock MemCache Object
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MockMemCache
{

    /**
     * @var int
     */
    private $index;

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $weigth
     *
     * @return boolean
     */
    public function addServer($host, $port, $weigth)
    {
        return 'validHost' === $host;
    }

    /**
     * @param  string $id
     *
     * @return boolean
     */
    public function get($id)
    {
        return 'validId' === $id ? time() : false;
    }

    /**
     * @param  string  $id
     * @param  string  $data
     * @param  boolean $compression
     * @param  int     $lifetime
     *
     * @return boolean
     */
    public function set($id, $data, $compression, $lifetime)
    {
        return 'validId' === $id;
    }

    /**
     * @param  string $id
     *
     * @return boolean
     */
    public function delete($id)
    {
        return 'validId' === $id;
    }

    /**
     * @return boolean Alternatively true and false
     */
    public function flush()
    {
        return (0 === $this->index++ % 2);
    }
}
