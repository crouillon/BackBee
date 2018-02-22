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

namespace BackBee\Util\Resolver;

use BackBee\ApplicationInterface;

@trigger_error(
    'The '.__NAMESPACE__.'\DirectoryInterface interface is ' .
    'deprecated since version 1.4 and will be removed in 1.5.',
    E_USER_DEPRECATED
);

/**
 * @deprecated since version 1.4, to be removed in 1.5.
 * @codeCoverageIgnore
 */
interface DirectoryInterface
{
    /**
     */
    public static function getDirectories(ApplicationInterface $application);
}
