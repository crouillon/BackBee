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

namespace BackBee\Security;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Site\Site;

/**
 * A group entity.
 *
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 *
 * @ORM\Entity
 * @ORM\Table(name="`group`", uniqueConstraints={@ORM\UniqueConstraint(name="UNI_IDENTIFIER",columns={"id"})})
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Group extends AbstractObjectIdentifiable implements DomainObjectInterface
{
    /**
     * Unique identifier of the group.
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected $_id;

    /**
     * Group name.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="name")
     *
     * @Serializer\Expose
     */
    protected $_name;

    /**
     * Group description.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="description", nullable=true)
     *
     * @Serializer\Expose
     */
    protected $_description;

    /**
     * A collection of users.
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="BackBee\Security\User", inversedBy="_groups", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(
     *     name="user_group",
     *     joinColumns={
     *         @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *     }
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     * @Serializer\SerializedName("users")
     * @Serializer\ReadOnly
     */
    protected $_users;

    /**
     * Optional site.
     *
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="BackBee\Site\Site", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * Group constructor.
     */
    public function __construct()
    {
        $this->_users = new ArrayCollection();
    }

    /**
     * Returns the group identifier.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the group identifier.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->getId();
    }

    /**
     * Sets the group identifier.
     *
     * @param  integer $id
     *
     * @return Group
     * @codeCoverageIgnore
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * Returns the group name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the group name.
     *
     * @param  string $name
     *
     * @return Group
     * @codeCoverageIgnore
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Returns the group description.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * Sets the group description.
     *
     * @param  string $description
     *
     * @return Group
     * @codeCoverageIgnore
     */
    public function setDescription($description)
    {
        $this->_description = $description;

        return $this;
    }

    /**
     * Returns the group users.
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->_users;
    }

    /**
     * Sets the group users collection.
     *
     * @param  ArrayCollection $users
     *
     * @return Group
     */
    public function setUsers(ArrayCollection $users)
    {
        $this->_users = $users;

        return $this;
    }

    /**
     * Adds a user to the current group.
     *
     * @param  User $user
     *
     * @return Group
     */
    public function addUser(User $user)
    {
        $this->_users->add($user);

        return $this;
    }

    /**
     * Removes an from the group
     *
     * @param  User $user
     *
     * @return Group
     */
    public function removeUser(User $user)
    {
        $this->_users->removeElement($user);

        return $this;
    }

    /**
     * Returns the optional site.
     *
     * @return Site|NULL
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Sets the optional site.
     *
     * @param  Site $site
     *
     * @return Group
     * @codeCoverageIgnore
     */
    public function setSite(Site $site = null)
    {
        $this->_site = $site;

        return $this;
    }

    /**
     * Returns the site uid if exists, null elsewhere.
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_uid")
     *
     * @return string|null
     */
    public function getSiteUid()
    {
        if (null === $this->_site) {
            return;
        }

        return $this->_site->getUid();
    }

    /**
     * @inheritDoc
     */
    public function getObjectIdentifier()
    {
        return $this->getId();
    }
}
