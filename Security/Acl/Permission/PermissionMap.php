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

namespace BackBee\Security\Acl\Permission;

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

/**
 * This is basic permission map complements the masks which have been defined
 * on the standard implementation of the MaskBuilder.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class PermissionMap extends BasicPermissionMap
{

    const PERMISSION_COMMIT = 'COMMIT';
    const PERMISSION_PUBLISH = 'PUBLISH';

    /**
     * Map constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->map[self::PERMISSION_COMMIT] = [
            MaskBuilder::MASK_COMMIT,
            MaskBuilder::MASK_OPERATOR,
            MaskBuilder::MASK_MASTER,
            MaskBuilder::MASK_OWNER,
        ];

        $this->map[self::PERMISSION_PUBLISH] = [
            MaskBuilder::MASK_PUBLISH,
            MaskBuilder::MASK_MASTER,
        ];
    }
}
