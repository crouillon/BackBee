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

namespace BackBee\Tests\Traits;

/**
 * Trait allowing to read/write a private property of a class.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
trait InvokePropertyTrait
{

    /**
     * Returns or sets protected/private property of a class.
     *
     * @param object     &$object      Instantiated object that we will access property on.
     * @param string     $propertyName Property name.
     * @param mixed|null $value        If provided, set the property value.
     *
     * @return mixed Property value.
     */
    public function invokeProperty(&$object, $propertyName, $value = null)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        if (null !== $value) {
            $property->setValue($object, $value);
        }

        return $property->getValue($object);
    }
}
