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

namespace BackBee\Tests\Traits;

use org\bovigo\vfs\vfsStream;

use BackBee\Util\Doctrine\EntityManagerCreator;

/**
 * Trait allowing to create a minimal entity manager.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
trait CreateEntityManagerTrait
{

    /**
     * Returns the default EntityManager options
     *
     * @return array
     */
    private function getOptions()
    {
        vfsStream::umask(0000);
        vfsStream::setup('TEST', 0777);

        return [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'proxy_dir' => vfsStream::url('TEST/Proxies'),
            'proxy_ns' => '__TEST__'
        ];
    }

    /**
     * Creates an EntityManager
     *
     * @param  array $options Optional entity manager options.
     *
     * @return EntityManagerCreator
     */
    public function createEntityManager(array $options = [])
    {
        return EntityManagerCreator::create(array_merge($this->getOptions(), $options));
    }
}
