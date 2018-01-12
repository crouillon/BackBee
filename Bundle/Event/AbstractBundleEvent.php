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

namespace BackBee\Bundle\Event;

use BackBee\Bundle\BundleInterface;
use BackBee\Event\Event;

/**
 * An abstract bundle event.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AbstractBundleEvent extends Event
{

    /**
     * The targeted bundle.
     *
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * Event constructor.
     *
     * @param  BundleInterface           $target
     * @param  mixed                     $eventArgs
     *
     * @throws \InvalidArgumentException if the provided target does not implement BundleInterface
     */
    public function __construct($target, $eventArgs = null)
    {
        if (!($target instanceof BundleInterface)) {
            throw new \InvalidArgumentException(sprintf(
                'Target of bundle update or action event must be instance of %s',
                BundleInterface::class
            ));
        }

        parent::__construct($target, $eventArgs);

        $this->bundle = $target;
    }

    /**
     * Returns the targeted bundle.
     *
     * @return BundleInterface
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
