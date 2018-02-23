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

namespace BackBee\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Abstract class providing methods implementing Object identity interfaces.
 *
 * This abstract impose a getUid() method definition to classes extending it.
 *
 * The main domain objects in BackBee application are :
 *
 * * \BackBee\Bundle\AbstractBundle
 * * \BackBee\ClassContent\AbstractClassContent
 * * \BackBee\NestedNode\AbstractNestedNode
 * * \BackBee\Security\Group
 * * \BackBee\Security\User
 * * \BackBee\Site\Site
 * * \BackBee\Site\Layout
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AbstractObjectIdentifiable implements DomainObjectInterface, ObjectIdentityInterface
{
    /**
     * An abstract method to gets the unique id of the object.
     *
     * @return string
     */
    abstract public function getUid();

    /**
     * Returns a unique identifier for this domain object.
     *
     * @return string
     */
    public function getObjectIdentifier()
    {
        return sprintf('%s(%s)', $this->getType(), $this->getIdentifier());
    }
    /**
     * Obtains a unique identifier for this object. The identifier must not be
     * re-used for other objects with the same type.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getUid();
    }

    /**
     * Returns a type for the domain object. Typically, this is the PHP class name.
     *
     * @return string
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * We specifically require this method so we can check for object equality
     * explicitly, and do not have to rely on referencial equality instead.
     *
     * Though in most cases, both checks should result in the same outcome.
     *
     * Referential Equality: $object1 === $object2
     * Example for Object Equality: $object1->getId() === $object2->getId()
     *
     * @param ObjectIdentityInterface $identity
     *
     * @return bool
     */
    public function equals(ObjectIdentityInterface $identity)
    {
        return (
            $this->getType() === $identity->getType()
            && $this->getIdentifier() === $identity->getIdentifier()
        );
    }
}
