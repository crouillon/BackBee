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

namespace BackBee\Util\Sequence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sequence Entity.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @ORM\Entity(repositoryClass="BackBee\Util\Sequence\Sequencer")
 * @ORM\Table(name="sequence")
 */
class Sequence
{
    /**
     * Name of the sequence.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * Sequence.
     *
     * @var string
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    private $value;

    /**
     * Returns the sequence value.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getValue()
    {
        return $this->value;
    }
}
