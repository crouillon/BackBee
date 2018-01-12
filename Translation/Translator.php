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

namespace BackBee\Translation;

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator as sfTranslator;

use BackBee\BBApplication;

/**
 * Extends Symfony\Component\Translation\Translator to allow lazy load of BackBee catalogs.
 *
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class Translator extends sfTranslator
{

    /**
     * Override Symfony\Component\Translation\Translator to lazy load every catalogs from:
     *     - BackBee\Resources\translations
     *     - PATH_TO_REPOSITORY\Resources\translations
     *     - PATH_TO_CONTEXT_REPOSITORY\Resources\translations.
     *
     * @param BBApplication $application
     * @param string        $locale
     */
    public function __construct(BBApplication $application, $locale)
    {
        parent::__construct($locale);

        // xliff is recommended by Symfony so we register its loader as default one
        $this->addLoader('xliff', new XliffFileLoader());

        if ($application->getContainer()->hasParameter('translator.fallback')) {
            // retrieve default fallback from container and set it
            $fallback = $application->getContainer()->getParameter('translator.fallback');
            $this->setFallbackLocales([$fallback]);
        }

        // define in which directory we should looking at to find xliff files
        $dirToLookingAt = [
            implode(DIRECTORY_SEPARATOR, [$application->getBBDir(), 'Resources', 'translations']),
            implode(DIRECTORY_SEPARATOR, [$application->getRepository(), 'Resources', 'translations']),
        ];

        if ($application->getRepository() !== $application->getBaseRepository()) {
            $dirToLookingAt[] = implode(
                DIRECTORY_SEPARATOR,
                [$application->getBaseRepository(), 'Resources', 'translations']
            );
        }

        // loop in every directory we should looking at and load catalog from file which match to the pattern
        foreach ($dirToLookingAt as $dirname) {
            $this->addResourcesDir($dirname);
        }
    }

    /**
     * @param string $dirname
     */
    private function addResourcesDir($dirname)
    {
        if (true === is_dir($dirname)) {
            foreach (scandir($dirname) as $filename) {
                $matches = [];
                if (preg_match('/(.+)\.(.+)\.xlf$/', $filename, $matches)) {
                    $this->addResource(
                        'xliff',
                        $dirname . DIRECTORY_SEPARATOR . $filename,
                        $matches[2],
                        $matches[1]
                    );
                }
            }
        }
    }
}
