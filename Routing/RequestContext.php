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

namespace BackBee\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext as sfRequestContext;

/**
 * Holds information about the current request.
 * This class implements a fluent interface.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class RequestContext extends sfRequestContext
{

    /**
     * @var Request
     */
    private $request;

    /**
     * Updates the RequestContext information based on a HttpFoundation Request.
     *
     * @param  Request $request A Request instance
     *
     * @return RequestContext
     */
    public function fromRequest(Request $request)
    {
        $this->request = $request;

        return parent::fromRequest($request);
    }

    /**
     * Returns the request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
