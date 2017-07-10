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

namespace BackBee\Cache\DAO;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for DAO stored cache data.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @ORM\Entity
 * @ORM\Table(name="cache",indexes={
 *     @ORM\Index(name="IDX_EXPIRE", columns={"expire"}),
 *     @ORM\Index(name="IDX_TAG", columns={"tag"})
 * })
 */
class Entity
{
    /**
     * The cache id.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid")
     */
    protected $uid;

    /**
     * A tag associated to the cache.
     *
     * @var string
     * @ORM\Column(type="string", name="tag", nullable=true)
     */
    protected $tag;

    /**
     * The data stored.
     *
     * @var string
     * @ORM\Column(type="text", name="data")
     */
    protected $data;

    /**
     * The expire date time for the stored data.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="expire", nullable=true)
     */
    protected $expire;

    /**
     * The creation date time.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created")
     */
    protected $created;

    /**
     * Class constructor.
     *
     * @param string|null $uid Optional, the cache id
     */
    public function __construct($uid = null)
    {
        $this->uid = $uid;
        $this->created = new \DateTime();
    }

    /**
     * Sets the cache id.
     *
     * @param  string $uid
     *
     * @return Entity
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Sets the data to store.
     *
     * @param  string $data
     *
     * @return Entity
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets the expire date time.
     *
     * @param  \DateTime|null $expire
     *
     * @return Entity
     */
    public function setExpire(\DateTime $expire = null)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Set the associated tag.
     *
     * @param  string|null $tag
     *
     * @return Entity
     */
    public function setTag($tag = null)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Returns the cache id.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->uid;
    }

    /**
     * Returns the stored data.
     *
     * @return string|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns the data time expiration.
     *
     * @return \DateTime|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Returns the associated tag.
     *
     * @return string|null
     */
    public function getTag()
    {
        return $this->tag;
    }
}
