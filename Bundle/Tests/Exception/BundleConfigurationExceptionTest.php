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

namespace BackBee\AutoLoader\Exception\Tests;

use BackBee\Bundle\Exception\BundleConfigurationException;

/**
 * Tests suite for class BundleConfigurationException.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Bundle\Exception\BundleConfigurationException
 */
class BundleConfigurationExceptionTest extends \PHPUnit_Framework_TestCase
{

    /**
     */
    public function testCode()
    {
        $exception = new BundleConfigurationException();
        $this->assertEquals(
            BundleConfigurationException::UNKNOWN_ERROR,
            $exception->getCode()
        );
    }
}