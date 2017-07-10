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

namespace BackBee\Cache\Tests\Mock;

use BackBee\Cache\IdentifierAppender\IdentifierAppenderInterface;
use BackBee\Cache\Validator\ValidatorInterface;
use BackBee\Renderer\RendererInterface;

/**
 * Mock object for IdentifierAppenderInterface
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MockAppenderValidator implements IdentifierAppenderInterface, ValidatorInterface
{

    /**
     * @{inheritdoc}
     */
    public function computeIdentifier($identifier, RendererInterface $renderer = null)
    {
        return 'computed-' . $identifier;
    }

    /**
     * @{inheritdoc}
     */
    public function getGroups()
    {
        return ['group1', 'group2'];
    }

    /**
     * @{inheritdoc}
     */
    public function isValid($object = null)
    {
        return true === $object;
    }
}
