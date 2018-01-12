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

namespace BackBee\Bundle;

@trigger_error(
    'The '.__NAMESPACE__.'\Registry class is deprecated since version 1.4 ' .
    'and will be removed in 1.5. Use BackBee\Util\Registry\Registry instead.',
    E_USER_DEPRECATED
);

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *   name="registry",
 *   indexes={
 *     @ORM\Index(name="IDX_TYPE", columns={"type"}),
 *     @ORM\Index(name="IDX_SCOPE", columns={"scope"}),
 *     @ORM\Index(name="IDX_KEY", columns={"key"}),
 *   }
 * )
 * @ORM\Entity(repositoryClass="BackBee\Bundle\Registry\Repository")
 *
 * @deprecated since version 1.4, to be removed in 1.5.
 * @codeCoverageIgnore
 */
class Registry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="`type`", type="string", length=255, nullable=true)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="`key`", type="string", length=255, nullable=true)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="`value`", type="text", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="`scope`", type="string", length=255, nullable=true)
     */
    protected $scope;

    /**
     * Gets the value of id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of key.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the value of key.
     *
     * @param string $key the key
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the value of key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets the value of key.
     *
     * @param string $key the key
     *
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Gets the value of value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value of value.
     *
     * @param string $value the value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value of scope.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Sets the value of scope.
     *
     * @param string $scope the scope
     *
     * @return self
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
