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

namespace BackBee\Util\Sequence;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

use BackBee\Exception\InvalidArgumentException;
use BackBee\Utils\Numeric;

/**
 * Sequence repository
 * Utility class providing db stored sequences.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Sequencer extends EntityRepository
{
    /**
     * The table name.
     *
     * @var string
     */
    private $table;

    /**
     * The fieldname of name.
     *
     * @var string
     */
    private $name;

    /**
     * The fieldname of value.
     *
     * @var string
     */
    private $value;

    /**
     * Class constructor.
     *
     * @param type          $em
     * @param ClassMetadata $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->table = $this->getClassMetadata()->table['name'];
        $this->name = $this->getClassMetadata()->fieldMappings['name']['columnName'];
        $this->value = $this->getClassMetadata()->fieldMappings['value']['columnName'];
    }

    /**
     * Get the next sequence value.
     *
     * @param string $name
     * @param int    $default
     *
     * @return int
     */
    public function getValue($name, $default = 0)
    {
        $current = $this->read($name, $default);

        return $this->update($name, $current + 1);
    }

    /**
     * Initiate a new sequence with name $name.
     *
     * @param string $name
     * @param int    $first
     *
     * @return int
     *
     * @throws InvalidArgumentException if sequence $name already exists or $first is not a positive integer
     */
    private function init($name, $first = 0)
    {
        if (null !== $this->find($name)) {
            throw new InvalidArgumentException(sprintf('Sequence with name %s already exists', $name));
        }

        if (false === Numeric::isPositiveInteger($first, false)) {
            throw new InvalidArgumentException('Initial value of a sequence must be a positive integer');
        }

        $query = 'INSERT INTO sequence (name, value) VALUE(:name, :value)';
        $params = [
            'name'   => $name,
            'value'  => $first,
        ];

        $this->getEntityManager()
            ->getConnection()
            ->executeUpdate($query, $params)
        ;

        return $first;
    }

    /**
     * Update a sequence with name $name.
     *
     * @param string $name
     * @param int    $value
     *
     * @return int
     *
     * @throws InvalidArgumentException if sequence $name doesn't exist
     *                                  or $value is not a positive integer
     */
    private function update($name, $value = 0)
    {
        if (null === $this->find($name)) {
            throw new InvalidArgumentException(sprintf('Unknown sequence with name %s', $name));
        }

        if (false === Numeric::isPositiveInteger($value, false)) {
            throw new InvalidArgumentException('Initial value of a sequence must be a positive integer');
        }

        $query = 'UPDATE sequence SET value = :value WHERE name = :name';
        $params = [
            'name'   => $name,
            'value'  => $value,
        ];

        $this->getEntityManager()
            ->getConnection()
            ->executeUpdate($query, $params)
        ;

        return $value;
    }

    /**
     * Read a sequence with name $name, create it if doesn't exist.
     *
     * @param string $name
     * @param int    $default
     *
     * @return int
     */
    private function read($name, $default = 0)
    {
        if (null === $seq = $this->find($name)) {
            return $this->init($name, $default);
        }

        $this->getEntityManager()->refresh($seq);
        return $seq->getValue();
    }

    /**
     * Update a sequence to $value only if greater than its current value.
     *
     * @param string $name
     * @param int    $value
     *
     * @return int
     *
     * @throws InvalidArgumentException if $value is not a positive integer
     */
    public function increaseTo($name, $value)
    {
        if (false === Numeric::isPositiveInteger($value, false)) {
            throw new InvalidArgumentException('Value of a sequence must be a positive integer');
        }

        $current = $this->read($name);
        if ($value > $current) {
            return $this->update($name, $value);
        }

        return $current;
    }
}
