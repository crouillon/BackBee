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

namespace BackBee\Security\Acl;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use BackBee\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Security\Acl\Domain\ObjectIdentifiableInterface;
use BackBee\Security\Acl\Permission\InvalidPermissionException;
use BackBee\Security\Acl\Permission\MaskBuilder;

/**
 * Class AclManager
 *
 * @package BackBee\Security\Acl
 */
class AclManager
{

    /**
     * A mutable ACL provider.
     *
     * @var MutableAclProviderInterface
     */
    protected $aclProvider;

    /**
     * A permission map.
     *
     * @var PermissionMapInterface
     */
    protected $permissionMap;

    /**
     * Manager constructor.
     *
     * @param  MutableAclProviderInterface $aclProvider   A mutable ACL provider.
     * @param  PermissionMapInterface      $permissionMap A permission map.
     */
    public function __construct($aclProvider, PermissionMapInterface $permissionMap)
    {
        if ($aclProvider instanceof SecurityContext) {
            @trigger_error('The AclManager definition  __construct(SecurityContextInterface $securityContext, '
                . 'PermissionMapInterface $permissionMap) is deprecated since 1.4 and will be removed in 1.5, '
                . 'use __construct(MutableAclProviderInterface $aclProvider, PermissionMapInterface $permissionMap) '
                . 'instead', E_USER_DEPRECATED);

            $aclProvider = $aclProvider->getAclProvider();
        }

        if (!($aclProvider instanceof MutableAclProviderInterface)) {
            throw new \InvalidArgumentException(
                'The first parameter of AclManager::__construct() should be a MutableAclInterface instance.'
            );
        }

        $this->aclProvider = $aclProvider;
        $this->permissionMap = $permissionMap;
    }

    /**
     * Gets ACL for the given domain object.
     *
     * @param  ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     *
     * @return Acl|null
     */
    public function getAcl($objectIdentity)
    {
        $this->enforceObjectIdentity($objectIdentity);

        try {
            $acl = $this->aclProvider->createAcl($objectIdentity);
        } catch (AclAlreadyExistsException $e) {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        }

        return $acl;
    }

    /**
     * Updates an existing object ACE.
     *
     * @param ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param DomainObjectInterface|SecurityIdentityInterface    $sid
     * @param int                                                $mask
     * @param string|null                                        $strategy
     *
     * @return AclManager
     */
    public function updateObjectAce($objectIdentity, $sid, $mask, $strategy = null)
    {
        return $this->insertOrUpdateObjectAce($objectIdentity, $sid, $mask, $strategy, false);
    }

    /**
     * Updates an existing object ACE.
     *
     * @param ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param DomainObjectInterface|SecurityIdentityInterface    $sid
     * @param int                                                $mask
     * @param string|null                                        $strategy
     *
     * @return AclManager
     */
    public function updateClassAce($objectIdentity, $sid, $mask, $strategy = null)
    {
        return $this->insertOrUpdateClassAce($objectIdentity, $sid, $mask, $strategy, false);
    }

    /**
     * Updates an existing object ACE, inserts if it doesnt exist.
     *
     * @param  ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param  DomainObjectInterface|SecurityIdentityInterface    $sid
     * @param  int                                                $mask
     * @param  string|null                                        $strategy
     * @param  boolean                                            $insertIfMissing
     *
     * @return AclManager
     */
    public function insertOrUpdateObjectAce($objectIdentity, $sid, $mask, $strategy = null, $insertIfMissing = true)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $validMask = $this->resolveMask($mask, $objectIdentity);
        $acl = $this->getAcl($objectIdentity);

        $found = false;
        foreach ($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($index, $validMask, $strategy);
                $found = true;
                break;
            }
        }

        if (!$found) {
            if (!$insertIfMissing) {
                throw new \InvalidArgumentException(
                    'ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity'
                );
            }
            $acl->insertObjectAce($sid, $mask, 0, true, $strategy);
        }

        $this->aclProvider->updateAcl($acl);

        return $this;
    }

    /**
     * Updates an existing class ACE, inserts if it doesnt exist.
     *
     * @param  ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param  DomainObjectInterface|SecurityIdentityInterface    $sid
     * @param  int                                                $mask
     * @param  string|null                                        $strategy
     * @param  boolean                                            $insertIfMissing
     *
     * @return AclManager
     */
    public function insertOrUpdateClassAce($objectIdentity, $sid, $mask, $strategy = null, $insertIfMissing = true)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $validMask = $this->resolveMask($mask, $objectIdentity);
        $acl = $this->getAcl($objectIdentity);

        $found = false;
        foreach ($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateClassAce($index, $validMask, $strategy);
                $found = true;
                break;
            }
        }

        if (!$found) {
            if (!$insertIfMissing) {
                throw new \InvalidArgumentException(
                    'ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity'
                );
            }
            $acl->insertClassAce($sid, $mask, 0, true, $strategy);
        }

        $this->aclProvider->updateAcl($acl);

        return $this;
    }

    /**
     * Deletes a class-scope ACE.
     *
     * @param ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param DomainObjectInterface|SecurityIdentityInterface    $sid
     *
     * @return AclManager
     */
    public function deleteClassAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $acl = $this->getAcl($objectIdentity);

        $found = false;
        foreach ($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->deleteClassAce($index);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \InvalidArgumentException(
                'ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity'
            );
        }

        $this->aclProvider->updateAcl($acl);

        return $this;
    }

    /**
     * Deletes an object-scope ACE.
     *
     * @param ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param DomainObjectInterface|SecurityIdentityInterface    $sid
     *
     * @return AclManager
     */
    public function deleteObjectAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $acl = $this->getAcl($objectIdentity);

        $found = false;
        foreach ($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->deleteObjectAce($index);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \InvalidArgumentException(
                'ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity'
            );
        }

        $this->aclProvider->updateAcl($acl);

        return $this;
    }

    /**
     * Get a class-scope ACE.
     *
     * @param ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param DomainObjectInterface|SecurityIdentityInterface    $sid
     */
    public function getClassAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);

        $acl = $this->aclProvider->findAcl($objectIdentity);

        foreach ($acl->getClassAces() as $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                return $ace;
            }
        }

        throw new \InvalidArgumentException(
            'ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity'
        );
    }

    /**
     * Get an object-scope ACE.
     *
     * @param ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     * @param DomainObjectInterface|SecurityIdentityInterface    $sid
     */
    public function getObjectAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);

        $acl = $this->aclProvider->findAcl($objectIdentity);

        foreach ($acl->getObjectAces() as $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                return $ace;
            }
        }
        throw new \InvalidArgumentException(
            'ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity'
        );
    }

    /**
     * Calculate mask for a list of permissions.
     * ['view', 'edit'] => (int) 5
     *
     * @param  array $permissions
     *
     * @return int
     */
    public function getMask(array $permissions)
    {
        $maskBuilder = new MaskBuilder();

        foreach ($permissions as $permission) {
            try {
                $maskBuilder->add($permission);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidPermissionException(
                    'Invalid permission mask: '.$permission,
                    $permission,
                    $e
                );
            }
        }

        return $maskBuilder->get();
    }

    /**
     * Get a list of all available permission codes.
     *
     * @return array
     */
    public function getPermissionCodes()
    {
        $permissions = [];
        $reflection = new \ReflectionClass(MaskBuilder::class);
        foreach ($reflection->getConstants() as $name => $constant) {
            if ('MASK_' === substr($name, 0, 5)) {
                $permissions[strtolower(substr($name, 5))] = $constant;
            }
        }

        return $permissions;
    }

    /**
     * Ensure that $objectIdentity is an ObjectIdentityInterface instance.
     *
     * @param  ObjectIdentityInterface|AbstractObjectIdentifiable $objectIdentity
     *
     * @throws \InvalidArgumentException if $objectIdentity cannot be convert to
     *                                   an instance of ObjectIdentityInterface.
     */
    private function enforceObjectIdentity(&$objectIdentity)
    {
        if ($objectIdentity instanceof AbstractObjectIdentifiable) {
            $objectIdentity = new ObjectIdentity(
                $objectIdentity->getObjectIdentifier(),
                ClassUtils::getRealClass($objectIdentity)
            );
        }

        if (!($objectIdentity instanceof ObjectIdentityInterface)) {
            throw new \InvalidArgumentException('Object must implement ObjectIdentityInterface');
        }
    }

    /**
     * Ensure that $sid is an SecurityIdentityInterface instance.
     *
     * @param DomainObjectInterface|SecurityIdentityInterface $sid
     *
     * @throws \InvalidArgumentException if $sid cannot be convert to an instance
     *                                   of SecurityIdentityInterface.
     */
    private function enforceSecurityIdentity(&$sid)
    {
        if ($sid instanceof DomainObjectInterface) {
            $sid = new UserSecurityIdentity(
                $sid->getObjectIdentifier(),
                ClassUtils::getRealClass($sid)
            );
        }

        if (!($sid instanceof SecurityIdentityInterface)) {
            throw new \InvalidArgumentException('Object must implement SecurityIdentityInterface');
        }
    }

    /**
     * Resolves any variation of masks/permissions to an integer.
     *
     * @param  string|int|array $masks
     * @param  object           $object
     *
     * @return int
     */
    private function resolveMask($masks, $object)
    {
        $resolvedMask = 0;

        if (is_integer($masks)) {
            $resolvedMask = $masks;
        } elseif (is_string($masks)) {
            $permission = $this->permissionMap->getMasks($masks, $object);
            $resolvedMask = $this->resolveMask($permission, $object);
        } elseif (is_array($masks)) {
            foreach ($masks as $mask) {
                $resolvedMask += $this->resolveMask($mask, $object);
            }
        } else {
            throw new \RuntimeException('Not a valid mask type');
        }

        return $resolvedMask;
    }

    /**
     * Get permissions
     *
     * @param $objectIdentity
     * @param $sid
     * @return array
     */
    public function getPermissions($objectIdentity, $sid)
    {
        $objectIdentity = $this->getClassScopeObjectIdentity($objectIdentity);
        $exceptedClass = [
            'BackBee\NestedNode\MediaFolder'
        ];

        try{

            $ace = $this->getObjectAce($objectIdentity, $sid);
        }
        catch (\Exception $e){

            if(in_array($objectIdentity->getType(), $exceptedClass)) return [];

            try {

                $ace = $this->getClassAce($this->getClassScopeObjectIdentity($objectIdentity->getType()), $sid);
            }
            catch (\Exception $e){

                $parentClass = get_parent_class($objectIdentity->getType());

                if (false !== $parentClass) {

                    return $this->getPermissions($parentClass, $sid);
                }
                else{
                    return [];
                }
            }
        }

        return $this->getAccessGranted($ace);
    }

    /**
     * Get permissions by page
     *
     * @param $page Page
     * @param $sid
     * @return array
     */
    public function getPermissionsByPage($page, $sid)
    {
        $objectIdentity = $this->getClassScopeObjectIdentity($page);

        try{

            $ace = $this->getObjectAce($objectIdentity, $sid);
        }
        catch (\Exception $e){

            if (null !== $page->getParent()) {

                return $this->getPermissionsByPage($page->getParent(), $sid);
            }
            elseif ($page->isRoot()){

                try{
                    $ace = $this->getClassAce($this->getClassScopeObjectIdentity(ClassUtils::getRealClass($page)), $sid);
                }
                catch (\Exception $e){
                    return [];
                }
            }
            else{
                return [];
            }
        }

        return $this->getAccessGranted($ace);
    }

    /**
     * Returns the class-scope object identity for $object.
     *
     * @param $object
     * @return ObjectIdentity
     */
    public function getClassScopeObjectIdentity($object)
    {
        $className = ClassUtils::getRealClass($object);
        $identifier = 'all';

        if($object instanceof ObjectIdentifiableInterface) {
            $identifier = $object->getObjectIdentifier();
            $className = $object->getType();
        }

        return new ObjectIdentity($identifier, $className);
    }

    /**
     * Determines whether access is granted.
     *
     * @param $ace
     * @return array
     */
    private function getAccessGranted($ace)
    {
        $access = [
            'total' => $ace->getMask()
        ];

        foreach ($this->getPermissionCodes() as $permission => $code){
            $access[$permission] = (0 !== ($ace->getMask() & $code)) ? 1 : 0;
        }

        $access['none'] = (0 === $ace->getMask()) ? 1 : 0;
        $access['view'] = (1 === $access['edit'] && 0 === $access['view']) ? 1 : $access['view'];

        return $access;
    }
}
