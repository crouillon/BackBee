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

namespace BackBee\Translation\Tests\Mock;

use BackBee\Translation\Translator;

/**
 * A mock object for Translator instance.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class TranslatorMock extends Translator
{

    /**
     * @var array
     */
    private $resources = [];

    /**
     * Adds a Resource.
     *
     * @param string $format   The name of the loader (@see addLoader())
     * @param mixed  $resource The resource name
     * @param string $locale   The locale
     * @param string $domain   The domain
     */
    public function addResource($format, $resource, $locale, $domain = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        $this->assertValidLocale($locale);

        $this->resources[$locale][] = [$format, $resource, $domain];
    }

    /**
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }
}
