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

namespace BackBee\Security\Acl\Permission;

/**
 * Invalid permission exception.
 *
 * @author Kenneth Golovin
 */
class InvalidPermissionException extends \InvalidArgumentException
{

    /**
     * @var string
     */
    protected $permission;

    /**
     * Exception constructor.
     *
     * @param string     $message
     * @param string     $permission
     * @param \Throwable $previous
     */
    public function __construct($message, $permission, $previous = null)
    {
        $this->permission = $permission;

        parent::__construct($message, null, $previous);
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
