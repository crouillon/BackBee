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

namespace BackBee\Security\Acl\Loader;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use BackBee\Bundle\AbstractBundle;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Security\Acl\Permission\PermissionMap;
use BackBee\Security\Group;
use BackBee\Utils\Collection\Collection;

/**
 * Yml Loader.
 * Loads yml acl data into the DB
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class YmlLoader
{
    use ContainerAwareTrait;

    /**
     * ACL provider object.
     *
     * @var MutableAclProvider
     */
    private $aclProvider;

    /**
     * Entity manager instance.
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Returns the service instance if exists.
     *
     * @param  string $serviceId
     *
     * @return object|null
     */
    private function getService($serviceId)
    {
        if ($this->container->has($serviceId)) {
            return $this->container->get($serviceId);
        }

        return null;
    }

    /**
     * Initializes the lloader.
     *
     * @throws \RuntimeException if something wrong.
     */
    private function initialize()
    {
        if (null === $this->container) {
            throw new \RuntimeException('Services container missing');
        }

        if (null === $this->entityManager = $this->getService('em')) {
            throw new \RuntimeException('Entity manager missing');
        }

        if (null === $securityContext = $this->getService('security.context')) {
            throw new \RuntimeException('Security context missing');
        }

        if (null === $this->aclProvider = $securityContext->getACLProvider()) {
            throw new \RuntimeException('ACL provider missing');
        }
    }

    /**
     * Loads groups rights from a YAML content.
     *
     * @param  string $aclData
     *
     * @throws \RuntimeException if something wrong.
     * @throws ParseException if YAML content cannot be parsed.
     */
    public function load($aclData)
    {
        $this->initialize();

        $config = Yaml::parse($aclData, true);
        $groupRepo = $this->entityManager->getRepository(Group::class);

        foreach ($config as $identifier => $rights) {
            $group = $groupRepo->findOneBy(['_name' => $identifier]);
            if (null === $group) {
                $group = new Group();
                $group->setName($identifier)
                    ->setDescription(Collection::get($rights, 'description', $identifier));
                $this->entityManager->persist($group);
                $this->entityManager->flush($group);
            }

            $this->updateRights($group, $rights);
        }
    }

    /**
     * Loads groups rights from a YAML file.
     *
     * @param  string $filename
     *
     * @throws \RuntimeException if file cannot be readden
     */
    public function loadFromFile($filename)
    {
        if (!is_readable($filename)) {
            throw new \RuntimeException(sprintf('Cannot read file %s', $filename));
        }

        $this->load(file_get_contents($filename));
    }

    /**
     * Updates rights associated to a group
     *
     * @param Group $group
     * @param array $config
     */
    private function updateRights(Group $group, array $config)
    {
        $securityIdentity = new UserSecurityIdentity($group->getObjectIdentifier(), Group::class);

        foreach ($config as $object => $rights) {
            $methodName = sprintf('add%sRights', ucfirst($object));
            if (method_exists($this, $methodName)) {
                call_user_func_array([$this, $methodName], [$rights, $securityIdentity]);
            }
        }
    }

    /**
     * Adding rights on generic objects.
     *
     * @param string               $classname
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     * @param string               $labelField
     */
    private function addObjectRights(
        $classname,
        array $config,
        UserSecurityIdentity $securityIdentity,
        $labelField = '_label'
    ) {
        $rights = $this->getActionsOnResources($config);
        foreach ((array) $rights as $resource => $actions) {
            if ('all' === $resource) {
                $this->addClassAcl($classname, $securityIdentity, $actions);
                continue;
            }

            $object = $this->entityManager->getRepository($classname)->findOneBy([$labelField => $resource]);
            if (null === $object) {
                $object = $this->entityManager->find($classname, $resource);
            }

            if (null === $object) {
                continue;
            }

            $this->addObjectAcl($object, $securityIdentity, $actions);
        }
    }

    /**
     * Adding rights on Site objects.
     *
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     */
    private function addSitesRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $this->addObjectRights(Site::class, $config, $securityIdentity);
    }

    /**
     * Adding rights on Layout objects.
     *
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     */
    private function addLayoutsRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $this->addObjectRights(Layout::class, $config, $securityIdentity);
    }

    /**
     * Adding rights on Page objects.
     *
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     */
    private function addPagesRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $this->addObjectRights(Page::class, $config, $securityIdentity, '_url');
    }

    /**
     * Adding rights on MediaFolder objects.
     *
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     */
    private function addMediafoldersRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $this->addObjectRights(MediaFolder::class, $config, $securityIdentity, '_url');
    }

    /**
     * Adding rights on Group objects.
     *
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     */
    private function addGroupsRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $this->addObjectRights(Group::class, $config, $securityIdentity, '_name');
    }

    /**
     * Adding rights on User objects.
     *
     * @param array                $config
     * @param UserSecurityIdentity $securityIdentity
     */
    private function addUsersRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $this->addObjectRights(User::class, $config, $securityIdentity, '_username');
    }

    /**
     * Adding rights on AbstractBundle objects.
     *
     * @param  array $config
     * @param  UserSecurityIdentity $securityIdentity
     */
    private function addBundlesRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $bundles = $this->getLoadedBundles();
        $rights = $this->getActionsOnResources($config);
        foreach ((array) $rights as $resource => $actions) {
            if ('all' === $resource) {
                foreach ($bundles as $bundle) {
                    $this->addObjectAcl($bundle, $securityIdentity, $actions);
                }
                continue;
            }

            if (!isset($bundles['bundle.' . $resource])) {
                continue;
            }

            $this->addObjectAcl($bundles['bundle.' . $resource], $securityIdentity, $actions);
        }
    }

    /**
     * Adding rights on AbstractClassContent objects.
     *
     * @param  array $config
     * @param  UserSecurityIdentity $securityIdentity
     */
    private function addContentsRights(array $config, UserSecurityIdentity $securityIdentity)
    {
        $rights = $this->getActionsOnResources($config);
        foreach ((array) $rights as $resource => $actions) {
            if ('all' === $resource) {
                $this->addClassAcl(AbstractClassContent::class, $securityIdentity, $actions);
                continue;
            }

            try {
                $classname = AbstractClassContent::getFullClassname($resource);
                $this->addClassAcl($classname, $securityIdentity, $actions);
            } catch (\Exception $ex) {
                continue;
            }
        }
    }

    /**
     * Returns an indexed array of loaded bundles.
     *
     * @return AbstractBundle[]
     */
    private function getLoadedBundles()
    {
        $bundles = [];
        $loadedBundles = array_keys($this->container->findTaggedServiceIds('bundle'));
        foreach ($loadedBundles as $serviceId) {
            $bundles[$serviceId] = $this->container->get($serviceId);
        }

        return $bundles;
    }

    /**
     * Format actions on resources.
     *
     * @param  array $config
     *
     * @return array
     */
    private function getActionsOnResources($config)
    {
        $result = [];

        $resources = (array) Collection::get($config, 'resources', []);
        $actions = (array) Collection::get($config, 'actions', []);
        if (!isset($actions[0]) || !is_array($actions[0])) {
            $actions = [$actions];
        }

        $index = 0;
        foreach ($resources as $resource) {
            if ('!' === substr($resource, 0, 1)) {
                $resource = substr($resource, 1);
                $result[$resource] = [];
                continue;
            }

            $action = isset($actions[$index]) ? $actions[$index] : $actions[0];
            $result[$resource] = $this->getActions($action);
            $index++;
        }

        return $result;
    }

    /**
     * Filters the actions.
     *
     * @param  mixed $def
     *
     * @return array
     */
    private function getActions($def)
    {
        $all = ['view', 'create', 'edit', 'delete', 'publish'];
        if (['all'] === $def) {
            return $all;
        }

        return array_intersect($all, $def);
    }

    /**
     * Add rights to security group on whole instances of class.
     *
     * @param string               $className
     * @param UserSecurityIdentity $securityIdentity
     * @param array                $rights
     */
    private function addClassAcl($className, UserSecurityIdentity $securityIdentity, array $rights)
    {
        $objectIdentity = new ObjectIdentity('all', $className);
        $this->addAcl($objectIdentity, $securityIdentity, $rights);
    }

    /**
     * Add rights to security group on one instance of object.
     *
     * @param object               $object
     * @param UserSecurityIdentity $securityIdentity
     * @param array                $rights
     */
    private function addObjectAcl($object, UserSecurityIdentity $securityIdentity, array $rights)
    {
        $objectIdentity = ObjectIdentity::fromDomainObject($object);
        $this->addAcl($objectIdentity, $securityIdentity, $rights);
    }

    /**
     * Add rights to security group on object.
     *
     * @param ObjectIdentity       $objectIdentity
     * @param UserSecurityIdentity $securityIdentity
     * @param array                $rights
     */
    private function addAcl(ObjectIdentity $objectIdentity, UserSecurityIdentity $securityIdentity, array $rights)
    {
        // Getting ACL for this object identity
        try {
            $acl = $this->aclProvider->createAcl($objectIdentity);
        } catch (\Exception $e) {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        }

        // Calculating mask
        $builder = new MaskBuilder();
        foreach ($rights as $right) {
            $builder->add($right);
        }
        $mask = $builder->get();

        // first revoke existing access for this security identity
        foreach ($acl->getObjectAces() as $i => $ace) {
            if ($securityIdentity->equals($ace->getSecurityIdentity())) {
                $acl->updateObjectAce($i, $ace->getMask() & ~$mask);
            }
        }

        // then grant
        if ('all' === $objectIdentity->getIdentifier()) {
            $acl->insertClassAce($securityIdentity, $mask);
        } else {
            $acl->insertObjectAce($securityIdentity, $mask);
        }
        $this->aclProvider->updateAcl($acl);
    }
}
