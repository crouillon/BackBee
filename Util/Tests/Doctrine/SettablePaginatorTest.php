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

use Doctrine\ORM\QueryBuilder;

use BackBee\Util\Doctrine\SettablePaginator;

/**
 * Tests suite for class SettablePaginator.
 *
 * @author Charles Rouillon <charls.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass BackBee\Util\Doctrine\SettablePaginator
 */
class SettablePaginatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @cover ::setCount()
     * @cover ::setResult()
     * @cover ::count()
     * @cover ::getIterator()
     */
    public function test()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuery'])
            ->getMock();

        $paginator = new SettablePaginator($queryBuilder);
        $this->assertEquals(
            $paginator,
            $paginator->setCount(1)->setResult(['result'])
        );

        $this->assertEquals(1, $paginator->count());
        $this->assertInstanceOf(\ArrayIterator::class, $paginator->getIterator());
    }
}
