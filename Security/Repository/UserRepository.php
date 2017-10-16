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

namespace BackBee\Security\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\ApiUserProviderInterface;
use BackBee\Security\User;

/**
 * A repository to serve User entities.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class UserRepository extends EntityRepository implements UserProviderInterface, UserCheckerInterface, ApiUserProviderInterface
{

    /**
     * Checks the user account before authentication.
     *
     * @param UserInterface $user a UserInterface instance
     *
     * @throws AccountStatusException
     * @codeCoverageIgnore
     */
    public function checkPreAuth(UserInterface $user)
    {
    }

    /**
     * Checks the user account after authentication.
     *
     * @param UserInterface $user a UserInterface instance
     *
     * @throws AccountStatusException
     * @codeCoverageIgnore
     */
    public function checkPostAuth(UserInterface $user)
    {
    }

    /**
     * Loads the user for the given public API key.
     *
     * @param  string $publicApiKey The username
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByPublicKey($publicApiKey)
    {
        if (null === $user = $this->findOneBy(['_api_key_public' => $publicApiKey])) {
            throw new UsernameNotFoundException(sprintf('Unknown public API key `%s`.', $publicApiKey));
        }

        return $this->checkActivatedStatus($user);
    }

    /**
     * Loads the user for the given username.
     *
     * @param  string $username The username
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        if (null === $user = $this->findOneBy(['_login' => $username])) {
            throw new UsernameNotFoundException(sprintf('Unknown username `%s`.', $username));
        }

        return $this->checkActivatedStatus($user);
    }

    /**
     * Checks that the user is activated
     *
     * @param  User $user The user
     *
     * @return User
     *
     * @throws DisabledException if the user is not activated
     */
    private function checkActivatedStatus(User $user)
    {
        if (!$user->isActivated()) {
            throw new DisabledException(sprintf('Account `%s`is disabled.', $user->getUsername()));
        }

        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * @param  UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (false === $this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Unsupported User class `%s`.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param  string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return (User::class === $class);
    }

    /**
     * Gets a collection of users matching params criteria.
     *
     * @param  array $params
     *
     * @return array
     */
    public function getCollection($params)
    {
        $qb = $this->createQueryBuilder('u');

        if (isset($params['name'])) {
            $this->addNameCriteria($qb, $params['name']);
            unset($params['name']);
        }

        $this->addCriteria($qb, $params);

        return $qb->getQuery()->getResult();
    }

    /**
     * Adds a search criteria n user's name.
     *
     * @param QueryBuilder $qb
     * @param string       $criteria
     */
    private function addNameCriteria(QueryBuilder $qb, $criteria)
    {
        $nameFilters = explode(' ', $criteria);

        foreach ($nameFilters as $key => $value) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('u._firstname', ':p' . $key),
                $qb->expr()->like('u._lastname', ':p' . $key)
            ));
            $qb->setParameter(':p' . $key, '%' . $value . '%');
        }
    }

    /**
     * Adds a set of search criteria.
     *
     * @param QueryBuilder $qb
     * @param array        $params
     */
    private function addCriteria(QueryBuilder $qb, array $params)
    {
        $likeParams = ['firstname', 'lastname', 'email', 'login'];
        foreach ($params as $key => $value) {
            if (!property_exists(User::class, '_' . $key)) {
                continue;
            }

            if (in_array($key, $likeParams)) {
                $qb->andWhere(
                    $qb->expr()->like('u._' . $key, ':' . $key)
                );
                $qb->setParameter(':' . $key, '%' . $value . '%');
            } else {
                $qb->andWhere(
                    $qb->expr()->eq('u._' . $key, ':' . $key)
                );
                $qb->setParameter(':' . $key, $value);
            }
        }
    }
}
