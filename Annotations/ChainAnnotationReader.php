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

namespace BackBee\Annotations;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\Reader;

/**
 * Chain annotation reader.
 *
 * @author Kenneth Golovin
 */
final class ChainAnnotationReader implements Reader
{

    /**
     * @var Reader[]
     */
    private $delegates = [];

    /**
     * Constructor.
     *
     * @param Reader[] $readers
     */
    public function __construct(array $readers)
    {
        $this->delegates = $readers;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        $annotations = [];

        foreach ($this->delegates as $delegateReader) {
            try {
                $annot = $delegateReader->getClassAnnotations($class);
                $annotations = array_merge_recursive($annotations, $annot);
            } catch (AnnotationException $e) {
                continue;
            }
        }

        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $annotations = [];

        foreach ($this->delegates as $delegateReader) {
            try {
                $annot = $delegateReader->getPropertyAnnotations($property);
                $annotations = array_merge_recursive($annotations, $annot);
            } catch (AnnotationException $e) {
                continue;
            }
        }

        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        $annotations = [];

        foreach ($this->delegates as $delegateReader) {
            try {
                $annot = $delegateReader->getMethodAnnotations($method);
                $annotations = array_merge_recursive($annotations, $annot);
            } catch (AnnotationException $e) {
                continue;
            }
        }

        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return;
    }
}
