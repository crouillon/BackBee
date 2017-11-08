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

namespace BackBee\Session;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * Allow to configure a PDO session handler regarding the environment.
 *
 * @author Mickaël Andrieu
 */
class PdoSessionHandlerFactory
{

    /**
     * The current driver connection.
     *
     * @var Connection
     */
    private $pdo;

    /**
     * An array of handler options.
     *
     * @var type
     */
    private $config;

    /**
     * Factory constructor.
     *
     * @param EntityManager $entityManager
     * @param array         $config
     */
    public function __construct(EntityManager $entityManager, array $config)
    {
        $this->pdo = $entityManager->getConnection()
            ->getWrappedConnection()
        ;
        $this->config = $config;
    }

    /**
     * Creates a PDO session handler.
     *
     * @return PdoSessionHandler
     */
    public function createPdoHandler()
    {
        $this->config = array_merge($this->config, ['lock_mode' => PdoSessionHandler::LOCK_NONE]);
        
        return new PdoSessionHandler($this->pdo, $this->config);
    }
}
