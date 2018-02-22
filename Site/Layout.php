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

namespace BackBee\Site;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

use BackBee\ClassContent\AbstractContent;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Utils\Numeric;
use BackBee\Workflow\State;

/**
 * A website layout.
 *
 * If the layout is not associated to a website, it is proposed as layout template
 * to webmasters
 *
 * The stored data is a serialized standard object. The object must have the
 * following structure :
 *
 * layout: {
 *   templateLayouts: [      // Array of final droppable zones
 *     zone1: {
 *       id:                 // unique identifier of the zone
 *       ...
 *     },
 *     ...
 *   ]
 * }
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\Site\Repository\LayoutRepository")
 * @ORM\Table(name="layout",indexes={@ORM\Index(name="IDX_SITE", columns={"site_uid"})})
 * @ORM\HasLifecycleCallbacks
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Layout extends AbstractObjectIdentifiable
{
    /**
     * The unique identifier.
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=32, name="uid")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The label of this layout.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="label", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_label;

    /**
     * The file name of the layout.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="path", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_path;

    /**
     * The seralized data.
     *
     * @var string
     *
     * @ORM\Column(type="text", name="data", nullable=false)
     */
    protected $_data;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_modified;

    /**
     * The optional path to the layout icon.
     *
     * @var string
     *
     * @ORM\Column(type="string", name="picpath", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_picpath;

    /**
     * Optional owner site.
     *
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="BackBee\Site\Site", inversedBy="_layouts", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * Store pages using this layout.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\NestedNode\Page", mappedBy="_layout", fetch="EXTRA_LAZY")
     */
    protected $_pages;

    /**
     * Layout states.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\Workflow\State", fetch="EXTRA_LAZY", mappedBy="_layout")
     */
    protected $_states;

    /**
     * The layout's parameters.
     *
     * @var array
     *
     * @ORM\Column(type="array", name="parameters", nullable = true)
     *
     * @Serializer\Expose
     * @Serializer\Type("array")
     */
    protected $_parameters = [];

    /**
     * The DOM document corresponding to the data.
     *
     * @var \DOMDocument
     */
    protected $_domdocument;

    /**
     * Is the layout datas are valid ?
     *
     * @var Boolean
     */
    protected $_isValid;

    /**
     * The final DOM zones on layout.
     *
     * @var array
     */
    protected $_zones;

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the layout
     * @param array  $options Initial options for the layout:
     *                        - label      the default label
     *                        - path       the path to the template file
     */
    public function __construct($uid = null, $options = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_pages = new ArrayCollection();
        $this->_states = new ArrayCollection();

        if (is_array($options)) {
            if (isset($options['label'])) {
                $this->setLabel($options['label']);
            }
            if (isset($options['path'])) {
                $this->setPath($options['path']);
            }
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
     * Returns the file name of the layout.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Returns the serialized data of the layout.
     *
     * @return string
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Returns the unserialzed object for the layout.
     *
     * @return \stdClass
     */
    public function getDataObject()
    {
        return json_decode($this->getData());
    }

    /**
     * Returns the path to the layout icon if defined, NULL otherwise.
     *
     * @return string|NULL
     */
    public function getPicPath()
    {
        return $this->_picpath;
    }

    /**
     * Returns the owner site if defined, NULL otherwise.
     *
     * @return Site|null
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Return the final zones (ie with contentset) for the layout.
     *
     * @return array|null Returns an array of zones or NULL is the layout datas
     *                    are invalid.
     */
    public function getZones()
    {
        if (null === $this->_zones && $this->isValid()) {
            $this->_zones = [];

            $zones = $this->getDataObject()->templateLayouts;
            foreach ($zones as $zone) {
                if (!property_exists($zone, 'mainZone')) {
                    $zone->mainZone = false;
                }

                if (!property_exists($zone, 'defaultClassContent')) {
                    $zone->defaultClassContent = null;
                }

                $zone->options = $this->getZoneOptions($zone);

                array_push($this->_zones, $zone);
            }
        }

        return $this->_zones;
    }

    /**
     * Returns defined parameters.
     *
     * @param  string $var The parameter to be return, if NULL, all parameters are returned
     *
     * @return mixed the parameter value or NULL if unfound
     */
    public function getParam($var = null)
    {
        if (null === $var) {
            return $this->_parameters;
        }

        return isset($this->_parameters[$var]) ? $this->_parameters[$var] : null;
    }

    /**
     * Returns the zone at the index $index.
     *
     * @param  int $index
     *
     * @return \stdClass|null
     *
     * @throws InvalidArgumentException if index is not an integer.
     */
    public function getZone($index)
    {
        if (false === Numeric::isPositiveInteger($index, false)) {
            throw new InvalidArgumentException('Invalid integer value.');
        }

        if (null !== $zones = $this->getZones()) {
            if ($index < count($zones)) {
                return $zones[$index];
            }
        }

        return null;
    }

    /**
     * Generates and returns a DOM document according to the unserialized data object.
     *
     * @return \DOMDocument|NULL Returns a DOM document or NULL is the layout datas
     *                           are invalid.
     *
     * @deprecated since version 1.4, will be removed in 1.5
     * @codeCoverageIgnore
     */
    public function getDomDocument()
    {
        if (null === $this->_domdocument) {
            if (true === $this->isValid()) {
                $mainLayoutRow = new \DOMDocument('1.0', 'UTF-8');
                $mainNode = $mainLayoutRow->createElement('div');
                $mainNode->setAttribute('class', 'row');

                $clearNode = $mainLayoutRow->createElement('div');
                $clearNode->setAttribute('class', 'clear');

                $mainId = '';
                $zones = array();
                foreach ($this->getDataObject()->templateLayouts as $zone) {
                    $mainId = $zone->defaultContainer;
                    $class = $zone->gridClassPrefix.$zone->gridSize;

                    if (true === property_exists($zone, 'alphaClass')) {
                        $class .= ' '.$zone->alphaClass;
                    }

                    if (true === property_exists($zone, 'omegaClass')) {
                        $class .= ' '.$zone->omegaClass;
                    }

                    if (true === property_exists($zone, 'typeClass')) {
                        $class .= ' '.$zone->typeClass;
                    }

                    $zoneNode = $mainLayoutRow->createElement('div');
                    $zoneNode->setAttribute('class', trim($class));
                    $zones['#'.$zone->id] = $zoneNode;

                    $parentNode = isset($zones[$zone->target]) ? $zones[$zone->target] : $mainNode;
                    $parentNode->appendChild($zoneNode);
                    if (true === property_exists($zone, 'clearAfter')
                            && 1 == $zone->clearAfter) {
                        $parentNode->appendChild(clone $clearNode);
                    }
                }

                $mainNode->setAttribute('id', substr($mainId, 1));
                $mainLayoutRow->appendChild($mainNode);

                $this->_domdocument = $mainLayoutRow;
            }
        }

        return $this->_domdocument;
    }

    /**
     * Checks for a valid structure of the unserialized data object.
     *
     * @return Boolean Returns TRUE if the data object is valid, FALSE otherwise
     */
    public function isValid()
    {
        if (null === $this->_isValid) {
            $this->_isValid = (
                (null !== $data_object = $this->getDataObject())
                && property_exists($data_object, 'templateLayouts')
                && is_array($data_object->templateLayouts)
                && 0 < count($data_object->templateLayouts)
            );
        }

        return $this->_isValid;
    }

    /**
     * Sets the label.
     *
     * @param  string $label
     *
     * @return Layout
     */
    public function setLabel($label)
    {
        $this->_label = $label;

        return $this;
    }

    /**
     * Set the filename of the layout.
     *
     * @param  string $path
     *
     * @return Layout
     */
    public function setPath($path)
    {
        $this->_path = $path;

        return $this;
    }

    /**
     * Sets the data associated to the layout.
     * No validation checks are performed at this step.
     *
     * @param  mixed $data
     *
     * @return Layout
     */
    public function setData($data)
    {
        if (true === is_object($data)) {
            return $this->setDataObject($data);
        }

        $this->_picpath = null;
        $this->_isValid = null;
        $this->_domdocument = null;
        $this->_zones = null;

        $this->_data = $data;

        return $this;
    }

    /**
     * Sets the data associated to the layout.
     * None validity checks are performed at this step.
     *
     * @param  mixed $data
     *
     * @return Layout
     */
    public function setDataObject($data)
    {
        if (true === is_object($data)) {
            $data = json_encode($data);
        }

        return $this->setData($data);
    }

    /**
     * Sets the path to the layout icon.
     *
     * @param  string $picpath
     *
     * @return Layout
     */
    public function setPicPath($picpath)
    {
        $this->_picpath = $picpath;

        return $this;
    }

    /**
     * Associates this layout to a website.
     *
     * @param  Site $site
     *
     * @return Layout
     */
    public function setSite(Site $site)
    {
        $this->_site = $site;

        return $this;
    }

    /**
     * Sets one or all parameters.
     *
     * @param  string $var    the parameter name to set, if NULL all the parameters array wil be set
     * @param  mixed  $values the parameter value or all the parameters if $var is NULL
     *
     * @return Layout
     */
    public function setParam($var = null, $values = null)
    {
        if (null === $var) {
            $this->_parameters = $values;
        } else {
            $this->_parameters[$var] = $values;
        }

        return $this;
    }

    /**
     * Returns a contentset options according to the layout zone.
     *
     * @param  \stdClass $zone
     *
     * @return array
     */
    private function getZoneOptions(\stdClass $zone)
    {
        $options = [];

        if (property_exists($zone, 'accept')
            && is_array($zone->accept)
            && 0 < count($zone->accept)
            && $zone->accept[0] != ''
        ) {
            $options['accept'] = $zone->accept;

            $func = function (&$item) {
                $item = ('' == $item) ? null : AbstractContent::CLASSCONTENT_BASE_NAMESPACE . $item;
            };

            array_walk($options['accept'], $func);
        }

        if (property_exists($zone, 'maxentry') && 0 < $zone->maxentry) {
            $options['maxentry'] = $zone->maxentry;
        }

        return $options;
    }

    /**
     * Returns the Site uid.
     *
     * @return string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_uid")
     */
    public function getSiteUid()
    {
        return null !== $this->getSite() ? $this->getSite()->getUid() : null;
    }

    /**
     * Returns the Site label.
     *
     * @return string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_label")
     */
    public function getSiteLabel()
    {
        return null !== $this->getSite() ? $this->getSite()->getLabel() : null;
    }

    /**
     * Returns the layout data.
     *
     * @return array
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("data")
     */
    public function virtualGetData()
    {
        return json_decode($this->getData(), true);
    }

    /**
     * Add a workflow state.
     *
     * @param  State $state
     *
     * @return Layout
     */
    public function addState(State $state)
    {
        $this->_states[] = $state;

        return $this;
    }

    /**
     * Remove a workfow state.
     *
     * @param State $state
     */
    public function removeState(State $state)
    {
        $this->_states->removeElement($state);
    }

    /**
     * Get workflow states.
     *
     * @return ArrayCollection
     */
    public function getStates()
    {
        return $this->_states;
    }

    /**
     * Returns a serialization of the worflow states.
     *
     * @return array
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("workflow_states")
     */
    public function getWorkflowStates()
    {
        $workflowStates = [
            ['0' => ['label' => 'Hors ligne', 'code' => '0']],
            'online'  => [],
            'offline' => [],
        ];

        foreach ($this->getStates() as $state) {
            if (0 < $code = $state->getCode()) {
                $workflowStates['online'][$code] = [
                    'label' => $state->getLabel(),
                    'code'  => '1_'.$code,
                ];
            } else {
                $workflowStates['offline'][$code] = [
                    'label' => $state->getLabel(),
                    'code'  => '0_'.$code,
                ];
            }
        }

        $workflowStates = array_merge(
            ['0' => ['label' => 'Hors ligne', 'code' => '0']],
            $workflowStates['offline'],
            ['1' => ['label' => 'En ligne', 'code' => '1']],
            $workflowStates['online']
        );

        return $workflowStates;
    }

    /**
     * Returns a serialization of the worflow states.
     *
     * @return array
     * @deprecated since version 1.4
     * @codeCoverageIgnore
     */
    public function getWokflowStates()
    {
        return $this->getWorkflowStates();
    }

    /**
     * Is the layout final? (ie can't accept sub pages)
     *
     * @return bool
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_final")
     */
    public function isFinal()
    {
        return (bool) $this->getParam('is_final');
    }
}
