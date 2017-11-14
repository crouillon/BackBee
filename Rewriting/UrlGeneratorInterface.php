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

namespace BackBee\Rewriting;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\NestedNode\Page;

/**
 * Interface for the rewriting url generation.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
interface UrlGeneratorInterface
{

    /**
     * Returns the list of class content names used by one of schemes
     * Dynamically add a listener on descrimator.onflush event to RewritingListener.
     *
     * @return array
     */
    public function getDiscriminators();

    /**
     * Generates and returns url for the provided page.
     *
     * @param Page                 $page    The page to generate its url
     * @param AbstractClassContent $content The optional main content of the page
     *
     * @return string
     */
    public function generate(Page $page, AbstractClassContent $content = null, $exceptionOnMissingScheme = true);

    /**
     * Call on page entity flush.
     * Generates a new URL according to the generator.
     *
     * @param Page $page
     */
    public function onPageFlush(Page $page);
}
