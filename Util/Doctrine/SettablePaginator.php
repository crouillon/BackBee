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

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Settable paginator.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class SettablePaginator extends Paginator
{
    /**
     * @var int
     */
    private $count;

    /**
     * @var array
     */
    private $result;

    /**
     * Sets the number of results.
     *
     * @param  int $count
     *
     * @return SettablePaginator
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Sets the first set of results.
     *
     * @param  array $result
     *
     * @return SettablePaginator
     */
    public function setResult(array $result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (null === $this->count) {
            return parent::count();
        }

        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (null === $this->result) {
            return parent::getIterator();
        }

        return new \ArrayIterator($this->result);
    }
}
