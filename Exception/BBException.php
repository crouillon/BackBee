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

namespace BackBee\Exception;

/**
 * BackBee parent class exception.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class BBException extends \Exception
{

    /**
     * Unknown error.
     */
    const UNKNOWN_ERROR = 1000;

    /**
     * Invalid argument provided.
     */
    const INVALID_ARGUMENT = 1001;

    /**
     * None BackBee application available.
     */
    const MISSING_APPLICATION = 1002;

    /**
     * Invalid database connection.
     */
    const INVALID_DB_CONNECTION = 1003;

    /**
     * Unknown context provided.
     */
    const UNKNOWN_CONTEXT = 1004;

    /**
     * The initial message of the exception.
     *
     * @var string
     */
    private $initialMessage;

    /**
     * The last source file before the exception thrown.
     *
     * @var string
     */
    private $source;

    /**
     * The line of the source file where the exception thrown.
     *
     * @var int
     */
    private $seek;

    /**
     * Class constructor.
     *
     * @param string          $message  The error message
     * @param int             $code     The error code
     * @param \Exception|null $previous Optional, the previous exception generated
     * @param string|null     $source   Optional, the last source file before the exception thrown
     * @param int|null        $seek     Optional, the line of the source file where the exception trown
     */
    public function __construct(
        $message = "",
        $code = 0,
        \Exception $previous = null,
        $source = null,
        $seek = null
    ) {
        if (property_exists($this, '_code')) {
            @trigger_error('The protected property ' . get_class($this) . '::_code is deprecated '
                            . 'since 1.4 and will be removed in 1.5', E_USER_DEPRECATED);
            $this->code = $this->_code;
        }

        $this->initialMessage = $message;

        if (empty($this->code)) {
            $this->code = self::UNKNOWN_ERROR;
        }

        if (!empty($code)) {
            $this->code = $code;
        }

        parent::__construct($message, $this->code, $previous);

        $this
            ->setSource($source)
            ->setSeek($seek)
        ;
    }

    /**
     * Returns the last source file before the exception thrown.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns the line of the source file where the exception thrown.
     *
     * @return int
     */
    public function getSeek()
    {
        return $this->seek;
    }

    /**
     * Sets the last source file before the exception thrown.
     *
     * @param  string $source
     *
     * @return BBException
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this->updateMessage();
    }

    /**
     * Sets the line of the source file where the exception thrown.
     *
     * @param  integer $seek
     *
     * @return BBException
     */
    public function setSeek($seek)
    {
        $this->seek = intval($seek);

        return $this->updateMessage();
    }

    /**
     * Updates the error message according to the source and seek provided.
     *
     * @return BBException
     */
    private function updateMessage()
    {
        $this->message = $this->initialMessage;
        if (null !== $this->getSource()) {
            $this->message .= sprintf(' in %s at %d.', $this->getSource(), $this->getSeek());
        }

        return $this;
    }
}
