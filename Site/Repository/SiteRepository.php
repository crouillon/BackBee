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

namespace BackBee\Site\Repository;

use Doctrine\ORM\EntityRepository;

use BackBee\Site\Site;

/**
 * Base repository for Site entities.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class SiteRepository extends EntityRepository
{

    /**
     * Searches for a Site by its name.
     *
     * @param  string $serverName A server name.
     *
     * @return Site|null
     */
    public function findByServerName($serverName)
    {
        return $this
            ->createQueryBuilder('s')
            ->andWhere('s._server_name = :server_name')
            ->setParameter('server_name', $serverName)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Returns site entity according to custom server_name if it exists in sites_config.
     *
     * @param  string $serverName
     * @param  array  $serverConfig
     *
     * @return Site|null
     */
    public function findByCustomServerName($serverName, array $serverConfig)
    {
        $site = null;
        foreach ($serverConfig as $key => $data) {
            if ($serverName === $data['domain']) {
                $site = $this->findOneBy(['_label' => $key]);
                break;
            }
        }

        return $site;
    }
}
