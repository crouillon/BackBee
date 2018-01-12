<?php

/*
 * Copyright (c) 2018 Lp digital system
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
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\ClassContent\Traits\Element;

use BackBee\ClassContent\Element\File;
use BackBee\ClassContent\MediaInterface;

/**
 * Trait FileMedia
 *
 * @category    BackBee
 * @copyright   ©2017 - Lp digital system
 * @author      Djoudi Bensid <djoudi.bensid@lp-digital.fr>
 */
trait FileMediaTrait
{
    /**
     * @see MediaInterface::mimeTypeSupported()
     */
    public function mimeTypeSupported($mimeType)
    {
        if ($this instanceof File) {
            $supported = (array) $this->getProperty('mime-types-supported');
            return ($supported ? in_array($mimeType, $supported) : false);
        }

        return false;
    }
}
