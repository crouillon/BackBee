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

namespace BackBee\Util\Registry;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * Entity repository for Registry
 *
 * @author Nicolas Dufreche
 */
class Repository extends EntityRepository
{

    /**
     * @var integer
     */
    private $last_inserted_id;

    /**
     * Saves the registry entry in DB, persist it if need.
     *
     * @param  Registry $registry
     *
     * @return Registry
     */
    public function save(Registry $registry)
    {
        if (!$this->getEntityManager()->contains($registry)) {
            $this->getEntityManager()->persist($registry);
        }

        $this->getEntityManager()->flush($registry);

        return $registry;
    }

    /**
     * Removes the registry entry from DB.
     *
     * @param  Registry $registry
     *
     * @return  Registry
     */
    public function remove(Registry $registry)
    {
        $state = $this->getEntityManager()->getUnitOfWork()->getEntityState($registry);
        if (UnitOfWork::STATE_NEW !== $state) {
            $this->getEntityManager()->remove($registry);
            $this->getEntityManager()->flush($registry);
        }

        return $registry;
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function removeEntity($entity)
    {
        $registries = $this->findRegistriesEntityById(get_class($entity), $entity->getObjectIdentifier());

        foreach ($registries as $registry) {
            $this->remove($registry);
        }
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function findRegistryEntityByIdAndScope($id, $scope)
    {
        $result = $this->_em->getConnection()->executeQuery(sprintf(
            'SELECT `key`, `value`, `scope` FROM registry WHERE `key` = "%s" AND `scope` = "%s"',
            $id,
            $scope
        ))->fetch();

        $registry = null;
        if (false !== $result) {
            $registry = new \BackBee\Bundle\Registry();
            $registry->setKey($result['key']);
            $registry->setValue($result['value']);
            $registry->setScope($result['scope']);
        }

        return $registry;
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function findRegistriesEntityById($identifier, $id)
    {
        $sql = 'SELECT * FROM registry AS r ' .
                'WHERE (r.type = :identifier OR r.scope = :identifier) ' .
                'AND ((r.key = "identifier" AND r.value = :id) OR (r.scope = :id))';
        $query = $this->_em->createNativeQuery($sql, $this->getResultSetMapping());
        $query->setParameters([
            'identifier' => $identifier,
            'id' => $id,
        ]);

        return $query->getResult();
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function findEntityById($identifier, $id)
    {
        return $this->buildEntity($identifier, $this->findRegistriesEntityById($identifier, $id));
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function findEntity($id)
    {
        return $this->findEntityById($this->getEntityName(), $id);
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function count($descriminator = null)
    {
        if (null === $descriminator) {
            $descriminator = $this->getEntityName();
        }

        $sql = 'SELECT count(*) as count FROM registry AS br WHERE br.%s = "%s"';

        if (class_exists($descriminator) && (new Builder())->isRegistryEntity(new $descriminator())) {
            $count = $this->countEntities($descriminator, $this->executeSql(sprintf($sql, 'type', $descriminator)));
        } else {
            $count = $this->executeSql(sprintf($sql, 'scope', $descriminator));
        }

        return $count;
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function findAllEntities($identifier = null)
    {
        if (null === $identifier) {
            $identifier = $this->getEntityName();
        }
        $sql = 'SELECT * FROM registry AS r WHERE ' .
                'r.key = "identifier" ' .
                'AND (r.type = :identifier OR r.scope = :identifier) ' .
                'ORDER BY r.id';
        $query = $this->_em->createNativeQuery($sql, $this->getResultSetMapping());
        $query->setParameter('identifier', $identifier);

        $entities = [];
        foreach ($query->getResult() as $key => $value) {
            $entities[$key] = $this->findEntityById($identifier, $value->getValue());
        }

        return $entities;
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    private function getResultSetMapping()
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('BackBee\Bundle\Registry', 'br');
        $rsm->addFieldResult('br', 'id', 'id');
        $rsm->addFieldResult('br', 'type', 'type');
        $rsm->addMetaResult('br', 'key', 'key');
        $rsm->addMetaResult('br', 'value', 'value');
        $rsm->addMetaResult('br', 'scope', 'scope');

        return $rsm;
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    private function countEntities($classname, $total)
    {
        $property_number = count((new $classname())->getObjectProperties());

        if ($property_number != 0) {
            $count = $total / ($property_number + 1);
        } else {
            $count = $total;
        }

        return $count;
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    public function persist($entity)
    {
        if ($entity instanceof DomainObjectInterface && null === $entity->getObjectIdentifier()) {
            if (!$this->last_inserted_id) {
                $this->last_inserted_id = $this->getLastInsertedId();
            }
            $entity->setObjectIdentifier($this->last_inserted_id++);
        }

        foreach ((new Builder())->setEntity($entity)->getRegistries() as $registry) {
            $this->_em->persist($registry);
            $this->_em->flush($registry);
        }
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    private function getLastInsertedId()
    {
        return $this->_em->getConnection()->lastInsertId('registry');
    }

    /**
     * @deprecated since version 1.4, will removed in 1.5.
     * @codeCoverageIgnore
     */
    private function buildEntity($classname, $contents)
    {
        return (new Builder())->setRegistries($contents, $classname)->getEntity();
    }
}
