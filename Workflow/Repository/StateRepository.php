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

namespace BackBee\Workflow\Repository;

use Doctrine\ORM\EntityRepository;

use BackBee\Site\Layout;

/**
 * Workflow state repository.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class StateRepository extends EntityRepository
{

    /**
     * Returns an array of available workflow states for the provided layout.
     *
     * @param  Layout $layout
     *
     * @return array
     */
    public function getWorkflowStatesForLayout(Layout $layout)
    {
        $states = [];
        foreach ($this->findBy(['_layout' => null]) as $state) {
            $states[$state->getCode()] = $state;
        }

        foreach ($this->findBy(['_layout' => $layout]) as $state) {
            $states[$state->getCode()] = $state;
        }

        ksort($states);

        return $states;
    }

    /**
     * Returns an array of available workflow states associated layout.
     *
     * @return array
     */
    public function getWorkflowStatesWithLayout()
    {
        $query = $this->createQueryBuilder('w')
                      ->andWhere('w._layout IS NOT NULL');

        return $query->getQuery()->getResult();
    }
}
