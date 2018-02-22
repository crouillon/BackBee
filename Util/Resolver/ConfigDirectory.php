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

/**
 * This config directory resolver allows to get every folders in which we can find bootstrap.yml
 * file. It's ordered by the most specific (context + envionment) to the most global.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class ConfigDirectory
{

    /**
     * Returns ordered directory (from specific to global) which can contains the bootstrap.yml file
     * according to context and environment.
     *
     * @return array which contains every directory (string) where we can find the bootstrap.yml
     */
    public static function getDirectories($bbDir, $baseDir, $context, $environment)
    {
        $directories = BootstrapDirectory::getDirectories($baseDir, $context, $environment);
        if (null !== $bbDir) {
            array_push($directories, $bbDir . DIRECTORY_SEPARATOR . 'Config');
        }

        $directories = array_reverse($directories);

        return $directories;
    }
}
