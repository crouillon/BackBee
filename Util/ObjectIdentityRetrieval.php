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

namespace BackBee\Util;

use Doctrine\ORM\EntityManager;

use BackBee\BBApplication;

/**
 * Utility class to retrieve entity instance from a string identifier.
 * Two pattern are supported:
 *   * <<something or null>>(<<object_uid>>, <<object_classname>>)
 *   * <<object_classname>>(<<object_uid>>)
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class ObjectIdentityRetrieval
{

    /**
     * @var string
     */
    private static $pattern1 = '/\((\w+),(.+)\)/';

    /**
     * @var string
     */
    private static $pattern2 = '#(.+)\((\w+)\)$#i';

    /**
     * The extracted object uid.
     *
     * @var string
     */
    private $identifier;

    /**
     * The extracted classname.
     *
     * @var string
     */
    private $class;

    /**
     * An entity manager instance.
     *
     * @var EntityManager
     */
    private $entityMng;

    /**
     * Class constructor
     *
     * @param EntityManager $entityMng  An entity manager instance.
     * @param string        $identifier The extracted object identifier.
     * @param string        $class      The extracted classname.
     */
    public function __construct(EntityManager $entityMng, $identifier, $class)
    {
        $this->entityMng = $entityMng;
        $this->class = $class;
        $this->identifier = $identifier;
    }

    /**
     * Try to retrieve an object instance from the string parameter.
     *
     * @param EntityManager $entityMng      An entity manager instance.
     * @param string        $objectIdentity A string object identifier.
     */
    public static function build($entityMng, $objectIdentity)
    {
        if ($entityMng instanceof BBApplication) {
            @trigger_error(
                'The definition ' . __CLASS__ . '(BBApplication) is deprecated since version 1.4' .
                'and will be removed in 1.5. Use ' . __CLASS__ . '(EntityManager) instead.',
                E_USER_DEPRECATED
            );

            $entityMng = $entityMng->getEntityManager();
        } elseif (!($entityMng instanceof EntityManager)) {
            throw new \InvalidArgumentException('First argument must be an EntityManager instance.');
        }

        $matches = array();
        if (preg_match(self::$pattern1, $objectIdentity, $matches)) {
            return new self($entityMng, trim($matches[1]), trim($matches[2]));
        } elseif (preg_match(self::$pattern2, $objectIdentity, $matches)) {
            return new self($entityMng, trim($matches[2]), trim($matches[1]));
        }

        return new self($entityMng, null, null);
    }

    /**
     * Tries to retrieve an entity instance.
     *
     * @return object
     */
    public function getObject()
    {
        if (null === $this->identifier || null === $this->class) {
            return;
        }

        return $this->entityMng->find($this->class, $this->identifier);
    }
}
