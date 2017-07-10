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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;

use BackBee\Cache\AbstractExtendedCache;
use BackBee\Cache\Exception\CacheException;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Util\Doctrine\EntityManagerCreator;

/**
 * Database cache adapter.
 * It supports tag and expire features
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Cache extends AbstractExtendedCache
{

    /**
     * The cache entity class name.
     *
     * @var string
     */
    const ENTITY_CLASSNAME = Entity::class;

    /**
     * The Doctrine entity manager to use.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityMngr;

    /**
     * The entity repository.
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repository;

    /**
     * An entity for a store cache.
     *
     * @var \BackBee\Cache\DAO\Entity
     */
    private $entity;

    /**
     * The prefix key for cache items.
     *
     * @var type
     */
    private $prefixKey = '';

    /**
     * Cache adapter options.
     *
     * @var array
     */
    protected $instanceOptions = [
        'em' => null,
        'dbal' => [],
    ];

    /**
     * Class constructor.
     *
     * @param  array                $options Initial options for the cache adapter:
     *                                          - em   EntityManager  Optional, an already defined EntityManager
     *                                                               (simply returns it)
     *                                          - dbal array         Optional, an array of Doctrine connection
     *                                                               options among:
     *                                             - connection  Connection  Optional, an already initialized
     *                                                                       database connection
     *                                             - proxy_dir   string      The proxy directory
     *                                             - proxy_ns    string      The namespace for Doctrine proxy
     *                                             - charset     string      Optional, the charset to use
     *                                             - collation   string      Optional, the collation to use
     *                                             - ...         mixed       All the required parameter to open a
     *                                                                       new connection
     * @param  string|null          $context An optional cache context
     * @param  LoggerInterface|null $logger  An optional logger
     */
    public function __construct(array $options = array(), $context = null, LoggerInterface $logger = null)
    {
        parent::__construct($options, $context, $logger);

        $this->setEntityManager();
        $this->setEntityRepository();
        $this->setPrefixKey();
    }

    /**
     * Returns the available cache for the given id if found returns false else.
     *
     * @param string    $id          Cache id
     * @param boolean   $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire      Optionnal, the expiration time (now by default)
     *
     * @return string|false
     */
    public function load($id, $bypassCheck = false, \DateTime $expire = null)
    {
        if (null === $this->getCacheEntity($id)) {
            return false;
        }

        if (null === $expire) {
            $expire = new \DateTime();
        }

        $lastTimestamp = $this->test($id);
        if (true === $bypassCheck
            || 0 === $lastTimestamp
            || $expire->getTimestamp() <= $lastTimestamp
        ) {
            return $this->getCacheEntity($id)->getData();
        }

        return false;
    }

    /**
     * Tests if a cache is available or not (for the given id).
     *
     * @param string $id Cache id
     *
     * @return int|false the last modified timestamp of the available cache record
     */
    public function test($id)
    {
        if (null === $this->getCacheEntity($id)) {
            return false;
        }

        if (null !== $this->getCacheEntity($id)->getExpire()) {
            $t = $this->getCacheEntity($id)
                    ->getExpire()
                    ->getTimestamp();

            return (time() > $t) ? false : $t;
        }

        return 0;
    }

    /**
     * Save some string datas into a cache record.
     *
     * @param string $id       Cache id
     * @param string $data     Datas to cache
     * @param int    $lifetime Optional, the specific lifetime for this record
     *                         (by default null, infinite lifetime)
     * @param string $tag      Optional, an associated tag to the data stored
     *
     * @return boolean TRUE if cache is stored FALSE otherwise
     */
    public function save($id, $data, $lifetime = null, $tag = null, $bypassControl = false)
    {
        try {
            $params = [
                'uid' => $this->getContextualId($id),
                'tag' => $tag ? $this->getContextualId($tag) : null,
                'data' => $data,
                'expire' => $this->getExpireTime($lifetime, $bypassControl),
                'created' => new \DateTime(),
            ];

            $types = [
                'string',
                'string',
                'string',
                'datetime',
                'datetime',
            ];

            return $this->persistEntity($id, $params, $types);
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Unable to load cache for id %s : %s', $id, $e->getMessage()));
        }

        return false;
    }

    /**
     * Persists or updates a stored cache row.
     *
     * @param  string $id
     * @param  array  $params
     * @param  array  $types
     *
     * @return boolean Always true
     */
    private function persistEntity($id, array $params, array $types)
    {
        if (null === $this->getCacheEntity($id)) {
            $this->entityMngr
                ->getConnection()
                ->insert('cache', $params, $types);
        } else {
            $identifier = ['uid' => array_shift($params)];
            $type = array_shift($types);
            $types[] = $type;

            $this->entityMngr
                ->getConnection()
                ->update('cache', $params, $identifier, $types);
        }

        $this->resetCacheEntity();

        return true;
    }

    /**
     * Removes a cache record.
     *
     * @param string $id Cache id
     *
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function remove($id)
    {
        try {
            $this->repository
                    ->createQueryBuilder('c')
                    ->delete()
                    ->where('c.uid = :uid')
                    ->setParameters(array('uid' => $this->getContextualId($id)))
                    ->getQuery()
                    ->execute();
            $this->resetCacheEntity();
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to remove cache for id %s : %s', $id, $e->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * Removes all cache records associated to one of the tags.
     *
     * @param string|array $tag
     *
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function removeByTag($tag)
    {
        $tags = (array) $tag;

        if (0 == count($tags)) {
            return false;
        }

        try {
            $this->repository
                    ->createQueryBuilder('c')
                    ->delete()
                    ->where('c.tag IN (:tags)')
                    ->setParameters(array('tags' => $this->getContextualTags($tags)))
                    ->getQuery()
                    ->execute();
            $this->resetCacheEntity();
        } catch (\Exception $e) {
            $this->log(
                'warning',
                sprintf(
                    'Enable to remove cache for tags (%s) : %s',
                    implode(',', $tags),
                    $e->getMessage()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Updates the expire date time for all cache records
     * associated to one of the provided tags.
     *
     * @param string|array $tag
     * @param int          $lifetime Optional, the specific lifetime for this record
     *                               (by default null, infinite lifetime)
     *
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function updateExpireByTag($tag, $lifetime = null, $bypassControl = false)
    {
        $tags = (array) $tag;

        if (0 == count($tags)) {
            return false;
        }

        $expire = $this->getExpireTime($lifetime, $bypassControl);

        try {
            $this->repository
                    ->createQueryBuilder('c')
                    ->update()
                    ->set('c.expire', ':expire')
                    ->where('c.tag IN (:tags)')
                    ->setParameters(array(
                        'expire' => $expire,
                        'tags' => $this->getContextualTags($tags),))
                    ->getQuery()
                    ->execute();
            $this->resetCacheEntity();
        } catch (\Exception $e) {
            $this->log('warning', sprintf(
                'Enable to update cache for tags (%s) : %s',
                implode(',', $tags),
                $e->getMessage()
            ));

            return false;
        }

        return true;
    }

    /**
     * Returns the minimum expire date time for all cache records
     * associated to one of the provided tags.
     *
     * @param string|array $tag
     * @param int          $lifetime Optional, the specific lifetime for this record
     *                               (by default 0, infinite lifetime)
     *
     * @return int
     */
    public function getMinExpireByTag($tag, $lifetime = 0)
    {
        $tags = (array) $tag;

        if (0 == count($tags)) {
            return $lifetime;
        }

        $now = new \DateTime();
        $expire = $this->getExpireTime($lifetime);

        try {
            $min = $this->repository
                    ->createQueryBuilder('c')
                    ->select('MIN(c.expire)')
                    ->where('c.tag IN (:tags)')
                    ->andWhere('c.expire IS NOT NULL')
                    ->setParameters(['tags' => $this->getContextualTags($tags)])
                    ->getQuery()
                    ->execute(null, Query::HYDRATE_SINGLE_SCALAR);

            if (null !== $min) {
                $min = new \DateTime($min);
                $lifetime = null === $expire ? $min->getTimestamp() : min([$expire->getTimestamp(), $min->getTimestamp()]);
                $lifetime -= $now->getTimestamp();
            }
        } catch (\Exception $e) {
            $this->log('warning', sprintf(
                'Enable to get expire time for tags (%s) : %s',
                implode(',', $tags),
                $e->getMessage()
            ));
        }

        return $lifetime;
    }

    /**
     * Clears all cache records.
     *
     * @return boolean TRUE if cache is cleared FALSE otherwise
     */
    public function clear()
    {
        try {
            $this->repository
                    ->createQueryBuilder('c')
                    ->delete()
                    ->where('1 = 1')
                    ->getQuery()
                    ->execute();
            $this->resetCacheEntity();
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to clear cache : %s', $e->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * Return the contextual id, according to the defined prefix key.
     *
     * @param  string $id
     *
     * @return string
     */
    private function getContextualId($id)
    {
        return ($this->prefixKey) ? md5($this->prefixKey . $id) : $id;
    }

    /**
     * Return an array of contextual tags, according to the defined prefix key.
     *
     * @param  string[] $tags
     *
     * @return string[]
     */
    private function getContextualTags(array $tags)
    {
        foreach ($tags as &$tag) {
            $tag = $this->getContextualId($tag);
        }
        unset($tag);

        return $tags;
    }

    /**
     * Returns the store entity for provided cache id.
     *
     * @param  string      $id The cache id
     *
     * @return Entity|null     The cached entity if found.
     */
    private function getCacheEntity($id)
    {
        $contextualId = $this->getContextualId($id);

        if (null === $this->entity || $this->entity->getId() !== $contextualId) {
            $this->entity = $this->repository->find($contextualId);
        }

        return $this->entity;
    }

    /**
     * Resets the last stored entity.
     */
    private function resetCacheEntity()
    {
        if (null !== $this->entity) {
            $this->entityMngr->detach($this->entity);
            $this->entity = null;
        }
    }

    /**
     * Returns the expiration timestamp.
     *
     * @param int $lifetime
     *
     * @return \DateTime|null
     */
    public function getExpireTime($lifetime = null, $bypassControl = false)
    {
        $expire = parent::getExpireTime($lifetime, $bypassControl);

        return (0 === $expire) ? null : \DateTime::createFromFormat('U', $expire);
    }

    /**
     * Sets the cache prefix key according to the context.
     *
     * @return Cache
     */
    private function setPrefixKey()
    {
        if (null !== $this->getContext()) {
            $this->prefixKey = md5($this->getContext());
        }

        return $this;
    }

    /**
     * Sets the entity repository.
     *
     * @return Cache
     */
    private function setEntityRepository()
    {
        $this->repository = $this->entityMngr->getRepository(self::ENTITY_CLASSNAME);

        return $this;
    }

    /**
     * Sets the entity manager.
     *
     * @return Cache
     *
     * @throws CacheException if if enable to create a database connection.
     */
    private function setEntityManager()
    {
        try {
            if ($this->getOption('em') instanceof EntityManager) {
                $this->entityMngr = $this->getOption('em');
            } else {
                $this->entityMngr = EntityManagerCreator::create($this->getOption('dbal'), $this->getLogger());
            }
        } catch (InvalidArgumentException $e) {
            throw new CacheException(
                'DAO cache: unable to create a database connection',
                CacheException::INVALID_DB_CONNECTION,
                $e
            );
        }

        return $this;
    }
}
