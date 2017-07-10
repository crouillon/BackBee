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

namespace BackBee\Cache\IdentifierAppender;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Util\ClassUtils;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Indexes\IdxContentContent;
use BackBee\Renderer\RendererInterface;

/**
 * This appender add request query parameters to cache identifier; you can specify the strategy to use.
 * There is 3 strategies:
 *     - NO_PARAMS_STRATEGY (=0): no request query parameters will be append to cache identifier
 *     - ALL_PARAMS_STRATEGY (=1): every request query parameters will be append to cache identifier
 *     - CLASSCONTENT_PARAMS_STRATEGY (=2): every request query parameters declared in classcontent yaml file will
 *     be append to cache identifier.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class QueryParameterAppender implements IdentifierAppenderInterface
{

    /**
     * Constants which define cache query params strategy.
     */
    const NO_PARAMS_STRATEGY = 0;
    const ALL_PARAMS_STRATEGY = 1;
    const CLASSCONTENT_PARAMS_STRATEGY = 2;

    /**
     * Request we will use to find query parameters.
     *
     * @var Request
     */
    private $request;

    /**
     * Application main entity manager.
     *
     * @var EntityManager
     */
    private $em;

    /**
     * The strategy to use.
     *
     * @var integer
     */
    private $strategy;

    /**
     * list of group name this validator belong to.
     *
     * @var array
     */
    private $groups;

    /**
     * constructor.
     *
     * @param Request       $request  request in which we will looking for query parameter
     * @param EntityManager $em       application main entity manager
     * @param integer       $strategy the strategy to apply when we need to compute identifier
     * @param array         $groups   list of groups this appender belongs to
     */
    public function __construct(Request $request, EntityManager $em, $strategy = self::NO_PARAMS_STRATEGY, $groups = [])
    {
        $this->request = $request;
        $this->em = $em;
        $this->strategy = (int) $strategy;
        $this->groups = (array) $groups;
    }

    /**
     * @see IdentifierAppenderInterface::computeIdentifier
     */
    public function computeIdentifier($identifier, RendererInterface $renderer = null)
    {
        if (self::NO_PARAMS_STRATEGY === $this->strategy) {
            return $identifier;
        }

        switch ($this->strategy) {
            case self::ALL_PARAMS_STRATEGY:
                foreach ($this->request->query->all() as $name => $value) {
                    if (true === is_scalar($value)) {
                        $identifier .= "-$name=$value";
                    }
                }
                break;
            case self::CLASSCONTENT_PARAMS_STRATEGY:
                if (null !== $renderer && true === ($renderer->getObject() instanceof AbstractClassContent)) {
                    $object = $renderer->getObject();
                    foreach ($this->getClassContentCacheQueryParameters($object) as $query) {
                        $query = str_replace('#uid#', $object->getUid(), $query);
                        if (null !== $value = $this->request->query->get($query)) {
                            $identifier .= "-$query=$value";
                        }
                    }
                }
                break;
            default:
                break;
        }

        return $identifier;
    }

    /**
     * @see IdentifierAppenderInterface::getGroups
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Returns content cache query parameters by exploring class content yaml files.
     *
     * @param  AbstractClassContent $content the content we want to get its cache query parameters
     *
     * @return array                         Contains every query parameters, can be empty if there
     *                                       is no cache query parameter found
     */
    private function getClassContentCacheQueryParameters(AbstractClassContent $content)
    {
        $classnames = [ClassUtils::getRealClass($content)];

        $content_uids = $this->em
            ->getRepository(IdxContentContent::class)
            ->getDescendantsContentUids($content);

        if (0 < count($content_uids)) {
            $classnames = array_merge(
                $classnames,
                $this->em->getRepository(AbstractClassContent::class)->getClassnames($content_uids)
            );
        }

        $query_parameters = [];
        foreach ($classnames as $classname) {
            if (!class_exists($classname)) {
                continue;
            }

            $object = new $classname();
            if ((null !== $parameters = $object->getProperty('cache-param'))
                && is_array($parameters)
                && isset($parameters['query'])
            ) {
                $query_parameters = array_merge($query_parameters, (array) $parameters['query']);
            }
        }

        return array_unique($query_parameters);
    }
}
