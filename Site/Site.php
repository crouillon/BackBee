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

namespace BackBee\Site;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;

/**
 * A BackBee website entity.
 *
 * A website should be associated to:
 *
 * * a collection of available layouts
 * * a collection of default metadata sets
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @ORM\Entity(repositoryClass="BackBee\Site\Repository\SiteRepository")
 * @ORM\Table(name="site", indexes={
 *     @ORM\Index(name="IDX_SERVERNAME", columns={"server_name"}),
 *     @ORM\Index(name="IDX_LABEL", columns={"label"})})
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Site extends AbstractObjectIdentifiable
{
    /**
     * The unique identifier of this website.
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=32, name="uid")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("id")
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The label of this website.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="label", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("label")
     * @Serializer\Type("string")
     *
     */
    protected $_label;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created", nullable=false)
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     */
    protected $_modified;

    /**
     * The optional server name.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="server_name", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("server_name")
     * @Serializer\Type("string")
     *
     */
    protected $_server_name;

    /**
     * The default extension used by the site.
     *
     * @var string
     */
    protected $_default_ext = '.html';

    /**
     * The collection of layouts available for this site.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\Site\Layout", mappedBy="_site", fetch="EXTRA_LAZY")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("layouts")
     * @Serializer\Type("ArrayCollection<string, BackBee\Site\Layout>")
     */
    protected $_layouts;

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the site.
     * @param array  $options Initial options for the content:
     *                        - label      the default label
     */
    public function __construct($uid = null, $options = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_layouts = new ArrayCollection();

        if (is_array($options) && isset($options['label'])) {
            $this->setLabel($options['label']);
        }
    }

    /**
     * Returns the unique identifier.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns the associated server name.
     *
     * @return string|null
     */
    public function getServerName()
    {
        return $this->_server_name;
    }

    /**
     * Return the default defined extension.
     *
     * @return string
     */
    public function getDefaultExtension()
    {
        return $this->_default_ext;
    }

    /**
     * Returns the collection of layouts available for this website.
     *
     * @return ArrayCollection
     */
    public function getLayouts()
    {
        return $this->_layouts;
    }

    /**
     * Adds a new layout to the site collection.
     *
     * @param  Layout $layout
     *
     * @return Site
     */
    public function addLayout(Layout $layout)
    {
        $this->_layouts[] = $layout;

        return $this;
    }

    /**
     * Sets the label of the website.
     *
     * @param  string $label
     *
     * @return Site
     */
    public function setLabel($label)
    {
        $this->_label = $label;

        return $this;
    }

    /**
     * Sets the server name.
     *
     * @param  string $serverName
     *
     * @return Site
     */
    public function setServerName($serverName)
    {
        $this->_server_name = $serverName;

        return $this;
    }
}
