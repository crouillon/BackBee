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

namespace BackBee\Util\Tests\Doctrine;

use Doctrine\DBAL\Driver\Mysqli\Driver as Mysqli;
use Doctrine\DBAL\Driver\PDOMySql\Driver as PDOMySql;
use Doctrine\DBAL\Driver\PDOPgSql\Driver as PDOPgSql;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqlite;

use BackBee\Util\Doctrine\DriverFeatures;

/**
 * Tests suite for class DriverFeatures
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Doctrine\DriverFeatures
 */
class DriverFeaturesTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::replaceSupported()
     * @covers ::multiValuesSupported()
     */
    public function test()
    {
        $mysql = new PDOMySql();
        $mysqli =new Mysqli();
        $sqlite = new PDOSqlite();
        $postgre = new PDOPgSql();

        $this->assertTrue(DriverFeatures::replaceSupported($mysql));
        $this->assertTrue(DriverFeatures::replaceSupported($mysqli));
        $this->assertTrue(DriverFeatures::replaceSupported($sqlite));
        $this->assertFalse(DriverFeatures::replaceSupported($postgre));

        $this->assertTrue(DriverFeatures::multiValuesSupported($mysql));
        $this->assertTrue(DriverFeatures::multiValuesSupported($mysqli));
        $this->assertFalse(DriverFeatures::multiValuesSupported($sqlite));
        $this->assertFalse(DriverFeatures::multiValuesSupported($postgre));
    }
}
