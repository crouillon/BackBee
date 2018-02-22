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

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Util\ClassUtils;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Revision;
use BackBee\NestedNode\Page;

/**
 * Utility class to deal with managed Doctrine entities.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class ScheduledEntities
{
    /**
     * Returns an array of scheduled entities by classname for insertions.
     *
     * @param  EntityManager $entityMng
     * @param  string|array  $classnames
     *
     * @return array
     */
    public static function getScheduledEntityInsertionsByClassname(EntityManager $entityMng, $classnames)
    {
        $entities = [];
        $classnames = (array) $classnames;

        foreach ($entityMng->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if (in_array(ClassUtils::getRealClass($entity), $classnames)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of scheduled entities by classname for updates.
     *
     * @param  EntityManager $entityMng
     * @param  string|array  $classnames
     *
     * @return array
     */
    public static function getScheduledEntityUpdatesByClassname(EntityManager $entityMng, $classnames)
    {
        $entities = [];
        $classnames = (array) $classnames;

        foreach ($entityMng->getUnitOfWork()->getScheduledEntityUpdates() as $entity) {
            if (in_array(ClassUtils::getRealClass($entity), $classnames)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of scheduled entities by classname for deletions.
     *
     * @param  EntityManager $entityMng
     * @param  string|array  $classnames
     *
     * @return array
     */
    public static function getScheduledEntityDeletionsByClassname(EntityManager $entityMng, $classnames)
    {
        $entities = [];
        $classnames = (array) $classnames;

        foreach ($entityMng->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if (in_array(ClassUtils::getRealClass($entity), $classnames)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of scheduled entities by classname for insertions, updates or deletions.
     *
     * @param  EntityManager $entityMng
     * @param  string|array  $classnames
     *
     * @return array
     */
    public static function getScheduledEntityByClassname(EntityManager $entityMng, $classnames)
    {
        return array_merge(
            self::getScheduledEntityInsertionsByClassname($entityMng, $classnames),
            self::getScheduledEntityUpdatesByClassname($entityMng, $classnames),
            self::getScheduledEntityDeletionsByClassname($entityMng, $classnames)
        );
    }

    /**
     * Returns an array of AbstractClassContent scheduled for insertions.
     *
     * @param  EntityManager $entityMng
     * @param  boolean       $withRevision   Include AClassContent which has scheduled revision
     * @param  boolean       $excludeElement Exclude element content
     *
     * @return array
     */
    public static function getScheduledAClassContentInsertions(
        EntityManager $entityMng,
        $withRevision = false,
        $excludeElement = false
    ) {
        $entities = [];
        foreach ($entityMng->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if (false !== $tmp = self::getScheduledEntity($entity, $withRevision, $excludeElement)) {
                $entities[] = $tmp;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of AbstractClassContent scheduled for updates.
     *
     * @param  EntityManager $entityMng
     * @param  boolean       $withRevision   Include AClassContent which has scheduled revision
     * @param  boolean       $excludeElement Exclude element content
     *
     * @return array
     */
    public static function getScheduledAClassContentUpdates(
        EntityManager $entityMng,
        $withRevision = false,
        $excludeElement = false
    ) {
        $entities = [];
        foreach ($entityMng->getUnitOfWork()->getScheduledEntityUpdates() as $entity) {
            if (false !== $tmp = self::getScheduledEntity($entity, $withRevision, $excludeElement)) {
                $entities[] = $tmp;
            }
        }

        return $entities;
    }

    /**
     * Returns content according to options.
     *
     * @param  object  $entity
     * @param  boolean $withRevision   Include AClassContent which has scheduled revision
     * @param  boolean $excludeElement Exclude element content
     *
     * @return AbstractClassContent|false
     */
    private static function getScheduledEntity($entity, $withRevision, $excludeElement)
    {
        if ($entity instanceof AbstractClassContent &&
            (!$excludeElement || !$entity->isElementContent())
        ) {
            return $entity;
        } elseif (true === $withRevision && $entity instanceof Revision) {
            if (!$excludeElement || !$entity->isElementContent()) {
                return $entity->getContent();
            }
        }

        return false;
    }

    /**
     * Returns an array of AbstractClassContent scheduled for deletions.
     *
     * @param  EntityManager $entityMng
     * @param  boolean       $excludeElement Exclude element content
     *
     * @return array
     */
    public static function getSchedulesAClassContentDeletions(EntityManager $entityMng, $excludeElement = false)
    {
        $entities = array();
        foreach ($entityMng->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AbstractClassContent &&
                (!$excludeElement || !$entity->isElementContent())) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of AbstractClassContent scheduled for insertions or updates.
     *
     * @param  EntityManager $entityMng
     * @param  boolean       $withRevision Include AClassContent which has scheduled revision
     *
     * @return array
     */
    public static function getScheduledAClassContentNotForDeletions(EntityManager $entityMng, $withRevision = false)
    {
        return array_merge(
            self::getScheduledAClassContentInsertions($entityMng, $withRevision),
            self::getScheduledAClassContentUpdates($entityMng, $withRevision)
        );
    }

    /**
     * Returns TRUE if an entity of $classname is scheduled for insertion or update.
     *
     * @param  EntityManager $entityMng
     * @param  string        $classname
     *
     * @return boolean
     */
    public static function hasScheduledEntitiesNotForDeletions(EntityManager $entityMng, $classname)
    {
        $entities = array_merge(
            $entityMng->getUnitOfWork()->getScheduledEntityInsertions(),
            $entityMng->getUnitOfWork()->getScheduledEntityUpdates()
        );

        foreach ($entities as $entity) {
            if (is_a($entity, $classname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns TRUE if a Page is scheduled for insertion or update.
     *
     * @param  EntityManager $entityMng
     *
     * @return boolean
     */
    public static function hasScheduledPageNotForDeletions(EntityManager $entityMng)
    {
        return self::hasScheduledEntitiesNotForDeletions($entityMng, Page::class);
    }
}
