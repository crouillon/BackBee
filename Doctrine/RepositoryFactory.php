<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
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
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class RepositoryFactory extends DefaultRepositoryFactory
{
    /**
     * @var array
     */
    private $customEntyRepository = [];

    /**
     * @var array
     */
    private $alreadyChecked = [];

    public function __construct(array $config)
    {
        if (isset($config['entity_custom_repository'])) {
            $this->customEntyRepository = (array) $config['entity_custom_repository'];
        }
    }

    /**
     * {@see DefaultRepositoryFactory::getRepository}
     *
     * This method extends DefaultRepositoryFactory::getRepository to allow late override
     * of an entity.
     *
     * @throws \InvalidArgumentException
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        if (!in_array($entityName, $this->alreadyChecked) && isset($this->customEntyRepository[$entityName])) {
            $repositoryClass = $this->customEntyRepository[$entityName];
            if (!class_exists($repositoryClass)) {
                throw new \InvalidArgumentException(sprintf(
                    'Class %s does not exist. Cannot be used as %s custom repository.',
                    $repositoryClass,
                    $entityName
                ));
            }

            $metadata = $entityManager->getClassMetadata($entityName);
            $metadata->customRepositoryClassName = $repositoryClass;
        }

        $this->alreadyChecked[] = $entityName;

        return parent::getRepository($entityManager, $entityName);
    }
}
