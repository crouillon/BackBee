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

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use phpmock\phpunit\PHPMock;

use BackBee\DependencyInjection\Container;
use BackBee\Logging\Logger;
use BackBee\Tests\Traits\InvokeMethodTrait;
use BackBee\Util\Doctrine\EntityManagerCreator;

/**
 * Tests suite for class EntityManagerCreator
 *
 * @author Charles Rouillon <charles.rouillon@lp-digitl.fr>
 *
 * @coversDefaultClass BackBee\Util\Doctrine\EntityManagerCreator
 */
class EntityManagerCreatorTest extends \PHPUnit_Framework_TestCase
{
    use InvokeMethodTrait;
    use PHPMock;

    /**
     * @covers ::create()
     */
    public function testCreate()
    {
        $options = [
            'driver' => 'pdo_sqlite',
            'memory' => 'true',
            'proxy_ns' => 'Proxies',
            'proxy_dir' => 'proxies'
        ];

        $this->assertInstanceOf(
            EntityManager::class,
            EntityManagerCreator::create($options)
        );
    }

    /**
     * @covers                   ::create()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid EntityManager provided
     */
    public function testCreateWithInvalidEntityManager()
    {
        $options = ['entity_manager' => new \stdClass()];

        EntityManagerCreator::create($options);
    }

    /**
     * @covers                   ::create()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid Connection provided
     */
    public function testCreateWithInvalidConnection()
    {
        $options = ['connection' => new \stdClass()];

        EntityManagerCreator::create($options);
    }

    /**
     * @covers ::getORMConfiguration()
     */
    public function testGetORMConfiguration()
    {
        $options = [
            'proxy_ns' => 'overrided proxy_namespace',
            'proxy_dir' => 'overrided proxy_dir',
            'orm' => [
                'proxy_namespace' => 'proxy_namespace',
                'proxy_dir' => 'proxy_dir',
                'auto_generate_proxy_classes' => false,
                'metadata_cache_driver' => [
                    'type' => 'service',
                    'id' => '@cache_id'
                ],
                'query_cache_driver' => [
                    'type' => 'service',
                    'id' => '@cache_id'
                ]
            ]
        ];

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cache = $this->getMockForAbstractClass(Cache::class);
        $container = new Container();
        $container->set('cache_id', $cache);

        $config = $this->invokeMethod(
            new EntityManagerCreator(),
            'getORMConfiguration',
            [$options, $logger, $container]
        );

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals('proxy_namespace', $config->getProxyNamespace());
        $this->assertEquals('proxy_dir', $config->getProxyDir());
        $this->assertFalse($config->getAutoGenerateProxyClasses());
        $this->assertEquals($cache, $config->getMetadataCacheImpl());
        $this->assertEquals($cache, $config->getQueryCacheImpl());
        $this->assertEquals($logger, $config->getSQLLogger());
    }

    /**
     * @covers ::addCustomFunctions()
     */
    public function testAddCustomFunctions()
    {
        $config = $this->getMockBuilder(Configuration::class)
            ->setMethods(['addCustomStringFunction', 'addCustomNumericFunction', 'addCustomDatetimeFunction'])
            ->getMock();

        $config->expects($this->once())->method('addCustomStringFunction');
        $config->expects($this->once())->method('addCustomNumericFunction');
        $config->expects($this->once())->method('addCustomDatetimeFunction');

        $options = [
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'dql' => [
                            'string_functions' => ['string' => 'stdClass'],
                            'numeric_functions' => ['numeric' => 'stdClass'],
                            'datetime_functions' => ['datetime' => 'stdClass']
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            $config,
            $this->invokeMethod(
                new EntityManagerCreator(),
                'addCustomFunctions',
                [$config, $options]
            )
        );
    }

    /**
     * @covers ::createEntityManagerWithConnection()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unable to create new EntityManager with provided Connection
     */
    public function testCreateEntityManagerWithInvalidConnection()
    {
        $connection = $this->getMockedMySqlConnection();
        $config = new Configuration();
        $evm = new EventManager();

        $this->invokeMethod(
            new EntityManagerCreator(),
            'createEntityManagerWithConnection',
            [$connection, $config, $evm]
        );
    }

    /**
     * @covers                   ::createEntityManagerWithParameters()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unable to create new EntityManager with provided parameters
     */
    public function testCreateEntityManagerWithInvalidParameters()
    {
        $options = [];
        $config = new Configuration();
        $evm = new EventManager();

        $this->invokeMethod(
            new EntityManagerCreator(),
            'createEntityManagerWithParameters',
            [$options, $config, $evm]
        );
    }

    /**
     * @covers ::randomizeServerPoolConnection()
     */
    public function testRandomizeServerPoolConnection()
    {
        $options = ['host' => ['host1', 'host2']];
        $shuffle = $this->getFunctionMock('BackBee\Util\Doctrine', 'shuffle');
        $shuffle->expects($this->once());

        $result = $this->invokeMethod(
            new EntityManagerCreator(),
            'randomizeServerPoolConnection',
            [$options]
        );

        $this->assertFalse(is_array($result['host']));
    }

    /**
     * @covers ::setConnectionCharset()
     */
    public function testSetConnectionCharset()
    {
        $connection = $this->getMockedMySqlConnection();
        $connection->expects($this->at(0))
            ->method('executeQuery')
            ->with('SET SESSION character_set_client = "utf-8";');
        $connection->expects($this->at(1))
            ->method('executeQuery')
            ->with('SET SESSION character_set_connection = "utf-8";');
        $connection->expects($this->at(2))
            ->method('executeQuery')
            ->with('SET SESSION character_set_results = "utf-8";');

        $this->invokeMethod(new EntityManagerCreator(), 'setConnectionCharset', [$connection, []]);
        $this->invokeMethod(
            new EntityManagerCreator(),
            'setConnectionCharset',
            [$connection, ['charset' => 'utf-8']]
        );
    }

    /**
     * @covers                   ::setConnectionCharset()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database character set `utf8`
     */
    public function testSetInvalidConnectionCharset()
    {
        $connection = $this->getMockedMySqlConnection();
        $connection->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Exception());

        $this->invokeMethod(
            new EntityManagerCreator(),
            'setConnectionCharset',
            [$connection, ['charset' => 'utf8']]
        );
    }

    /**
     * @covers ::setConnectionCollation()
     */
    public function testSetConnectionCollation()
    {
        $connection = $this->getMockedMySqlConnection();
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with('SET SESSION collation_connection = "utf-8";');

        $this->invokeMethod(new EntityManagerCreator(), 'setConnectionCollation', [$connection, []]);
        $this->invokeMethod(
            new EntityManagerCreator(),
            'setConnectionCollation',
            [$connection, ['collation' => 'utf-8']]
        );
    }

    /**
     * @covers                   ::setConnectionCollation()
     * @expectedException        BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database collation `utf8`
     */
    public function testSetInvalidConnectionCollation()
    {
        $connection = $this->getMockedMySqlConnection();
        $connection->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Exception());

        $this->invokeMethod(
            new EntityManagerCreator(),
            'setConnectionCollation',
            [$connection, ['collation' => 'utf8']]
        );
    }

    /**
     * @return Connection
     */
    private function getMockedMySqlConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([[], new Driver()])
            ->setMethods(['executeQuery'])
            ->getMock();
    }
}
