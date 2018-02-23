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

@trigger_error('The '.__NAMESPACE__.'\ObjectIdentifiableInterface interface is deprecated since version 1.4, '
        . 'to be removed in 1.5. Use Symfony\Component\Security\Acl\Model\DomainObjectInterface '
        . 'and Symfony\Component\Security\Acl\Model\ObjectIdentityInterface instead.', E_USER_DEPRECATED);

/**
 * @deprecated since version 1.4
 * @codeCoverageIgnore
 */
interface ObjectIdentifiableInterface extends DomainObjectInterface
{
    /**
     * Checks for an explicit objects equality.
     *
     * @param  ObjectIdentifiableInterface $identity
     * @return Boolean
     */
    public function equals(ObjectIdentifiableInterface $identity);

    /**
     * Returns the unique identifier for this object.
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns a type for the domain object. Typically, this is the PHP class name.
     *
     * @return string cannot return null
     */
    public function getType();
}
