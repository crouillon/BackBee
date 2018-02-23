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

namespace BackBee\Security;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;

/**
 * User object in BackBee.
 *
 * @author Michel Baptista <michel.baptista@lp-digital.fr>
 *
 * @ORM\Entity(repositoryClass="BackBee\Security\Repository\UserRepository")
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="UNI_LOGIN",columns={"login"})})
 * @ORM\HasLifecycleCallbacks
 *
 * @Serializer\ExclusionPolicy("all")
 */
class User extends AbstractObjectIdentifiable implements ApiUserInterface
{

    const PASSWORD_NOT_PICKED = 0;
    const PASSWORD_PICKED = 1;

    /**
     * Unique identifier of the user.
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     * @Serializer\ReadOnly
     */
    protected $_id;

    /**
     * The login of this user.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="login")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_login;

    /**
     * The login of this user.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="email")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_email;

    /**
     * The raw password of this user.
     *
     * @var string
     */
    protected $_raw_password;

    /**
     * The password of this user.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="password")
     *
     * @Serializer\Exclude()
     */
    protected $_password;

    /**
     * The User state.
     *
     * @var integer
     *
     * @ORM\Column(
     *     type="integer",
     *     name="state",
     *     length=2,
     *     options={"default": \BackBee\Security\User::PASSWORD_NOT_PICKED}
     * )
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    protected $_state = self::PASSWORD_NOT_PICKED;

    /**
     * The access state.
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="activated")
     *
     * @Serializer\Expose
     * @Serializer\Type("boolean")
     */
    protected $_activated = false;

    /**
     * The firstame of this user.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="firstname", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_firstname;

    /**
     * The lastname of this user.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="lastname", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_lastname;

    /**
     * A collection on user's revisions.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\NestedNode\PageRevision", mappedBy="_user", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    protected $_revisions;

    /**
     * A collection of user's groups.
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="BackBee\Security\Group", mappedBy="_users", fetch="EXTRA_LAZY")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     * @Serializer\SerializedName("groups")
     * @Serializer\ReadOnly
     */
    protected $_groups;

    /**
     * User's public api key.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="api_key_public", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_api_key_public;

    /**
     * User's private api key.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="api_key_private", nullable=true)
     *
     * @Serializer\Exclude()
     * @Serializer\Type("string")
     */
    protected $_api_key_private;

    /**
     * Whether the api key is enabled (default false).
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="api_key_enabled", options={"default": false})
     *
     * @Serializer\Expose
     * @Serializer\Type("boolean")
     */
    protected $_api_key_enabled = false;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created")
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime")
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="modified")
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime")
     */
    protected $_modified;

    /**
     * Class constructor.
     *
     * @param string $login
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     */
    public function __construct($login = null, $password = null, $firstname = null, $lastname = null)
    {
        $this->_login = (is_null($login)) ? '' : $login;
        $this->_password = (is_null($password)) ? '' : $password;
        $this->_firstname = $firstname;
        $this->_lastname = $lastname;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();

        $this->_groups = new ArrayCollection();
        $this->_revisions = new ArrayCollection();
    }

    /**
     * Returns the user's unique identifier.
     *
     * @return integer
     */
    public function getUid()
    {
        return $this->getId();
    }

    /**
     * Stringify the user object.
     *
     * @return string
     */
    public function __toString()
    {
        return trim($this->_firstname . ' ' . $this->_lastname . ' (' . $this->_login . ')');
    }

    /**
     * Serialize the user object using JSON.
     *
     * @return string
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        $serialized->username = $this->_login;
        $serialized->commonname = trim($this->_firstname . ' ' . $this->_lastname);

        return json_encode($serialized);
    }

    /**
     * Changes the user's status.
     *
     * @param  boolean $bool
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setActivated($bool)
    {
        if (is_bool($bool)) {
            $this->_activated = $bool;
        }

        return $this;
    }

    /**
     * Sets the user's login.
     *
     * @param  string $login
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setLogin($login)
    {
        $this->_login = $login;

        return $this;
    }

    /**
     * Sets the user's email.
     *
     * @param  string $email
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setEmail($email)
    {
        $this->_email = $email;

        return $this;
    }

    /**
     * Sets the user's password.
     *
     * @param  string $password
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setRawPassword($password)
    {
        $this->_raw_password = $password;

        return $this;
    }

    /**
     * Sets the user's password.
     *
     * @param  string $password
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    /**
     * Sets the user's firstname.
     *
     * @param  string $firstname
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;

        return $this;
    }

    /**
     * Sets the user's lastname.
     *
     * @param  string $lastname
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;

        return $this;
    }

    /**
     * Returns the user's identifier.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returnss the user's login.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLogin()
    {
        return $this->_login;
    }

    /**
     * Returns the user's email.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * Returns the user's password.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getRawPassword()
    {
        return $this->_raw_password;
    }

    /**
     * Returns the user's password.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Returns the user's firstname.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFirstname()
    {
        return $this->_firstname;
    }

    /**
     * Returns the user's lastname.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLastname()
    {
        return $this->_lastname;
    }

    /**
     * Returns the user's revisions.
     *
     * @return ArrayCollection
     * @codeCoverageIgnore
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Returns the user's groups.
     *
     * @return ArrayCollection
     * @codeCoverageIgnore
     */
    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * Sets the user's groups.
     *
     * @param  ArrayCollection
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setGroups(ArrayCollection $groups)
    {
        $this->_groups = $groups;

        return $this;
    }

    /**
     * Adds user to a new group.
     *
     * @param  Group $group
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function addGroup(Group $group)
    {
        $this->_groups->add($group);

        return $this;
    }

    /**
     * Returns the user's role (empty array).
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getRoles()
    {
        return [];
    }

    /**
     * Returns th user's salt.
     *
     * @return null
     * @codeCoverageIgnore
     */
    public function getSalt()
    {
        return;
    }

    /**
     * Returns the user's username.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUsername()
    {
        return $this->getLogin();
    }

    /**
     * Is the user activated?
     *
     * @return boolean
     * @codeCoverageIgnore
     */
    public function isActivated()
    {
        return true === $this->_activated;
    }

    /**
     * @codeCoverageIgnore
     */
    public function eraseCredentials()
    {
    }

    /**
     * Returns the user's API key.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getApiKeyPublic()
    {
        return $this->_api_key_public;
    }

    /**
     * Sets the user's API key.
     *
     * @param  string $apiKeyPublic
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setApiKeyPublic($apiKeyPublic)
    {
        $this->_api_key_public = $apiKeyPublic;

        return $this;
    }

    /**
     * Returns the user's API private key.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getApiKeyPrivate()
    {
        return $this->_api_key_private;
    }

    /**
     * Sets the user's API private key.
     *
     * @param  string $apiKeyPrivate
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setApiKeyPrivate($apiKeyPrivate)
    {
        $this->_api_key_private = $apiKeyPrivate;

        return $this;
    }

    /**
     * Is user's key enabled?
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function getApiKeyEnabled()
    {
        return $this->_api_key_enabled;
    }

    /**
     * Sets the user's key enabled.
     *
     * @param  bool $api_key_enabled
     *
     * @return User
     */
    public function setApiKeyEnabled($apiKeyEnabled)
    {
        $this->_api_key_enabled = (bool) $apiKeyEnabled;

        return $this->generateKeysOnNeed();
    }

    /**
     * Returns the user's status.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Sets the user's status.
     *
     * @param  bool $state
     *
     * @return User
     * @codeCoverageIgnore
     */
    public function setState($state)
    {
        $this->_state = (int) $state;

        return $this;
    }

    /**
     * Generate an REST api public key based on the private key.
     *
     * @return string Rest api public key
     */
    private function generateApiPublicKey()
    {
        if (null === $this->_api_key_private) {
            return $this->generateRandomApiKey()->getApiKeyPublic();
        }

        return sha1($this->_created->format(\DateTime::ATOM) . $this->_api_key_private);
    }

    /**
     * Generate a random Api pulbic and private key.
     *
     * @return User
     */
    public function generateRandomApiKey()
    {
        $this->_api_key_private = md5($this->_id . uniqid());

        $this->_api_key_public = $this->generateApiPublicKey();

        return $this;
    }

    /**
     * Check if the public api key is correct.
     *
     * @param  string $public_key The public key to check.
     *
     * @return boolean            The result of the check.
     */
    public function checkPublicApiKey($public_key)
    {
        return ($public_key === $this->generateApiPublicKey());
    }

    /**
     * Generate API keys on apiKeyEnabled change.
     *
     * @return User
     */
    protected function generateKeysOnNeed()
    {
        if ($this->getApiKeyEnabled() && null === $this->getApiKeyPrivate()) {
            $this->generateRandomApiKey();
        }

        return $this;
    }

    /**
     * Return the creation date of this user.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Return the last modification date of this user.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Call after the user has been deserialized.
     *
     * @Serializer\PostDeserialize
     * @codeCoverageIgnore
     */
    protected function postDeserialize()
    {
        $this->generateKeysOnNeed();
    }

    /**
     * Update last modification date on pre-update.
     *
     * @ORM\PreUpdate
     */
    public function updateModified()
    {
        $this->_modified = new \DateTime();
    }
}
