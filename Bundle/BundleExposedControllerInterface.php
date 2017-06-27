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

namespace BackBee\Bundle;

use Symfony\Component\HttpFoundation\Response;

/**
 * This interface ensure that an exposed controller has an entry point (indexAction),
 * a label getter and a description getter.
 *
 * @author e.chau <eric.chau@lp-digital.fr>
 */
interface BundleExposedControllerInterface
{
    /**
     * This exposed controller entry point.
     *
     * @return Response The response object to send.
     */
    public function indexAction();

    /**
     * This exposed controller entry point label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * This exposed controller description.
     *
     * @return string
     */
    public function getDescription();
}
