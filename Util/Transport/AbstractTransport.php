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

namespace BackBee\Util\Transport;

use BackBee\Util\Transport\Exception\ConnectionException;

/**
 * Abstract class for transport.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AbstractTransport
{
    /**
     * The protocol used by transport.
     *
     * @var string
     */
    protected $protocol;

    /**
     * The remose host.
     *
     * @var string
     */
    protected $host;

    /**
     * The protocol port to be uses.
     *
     * @var int
     */
    protected $port;

    /**
     * The login indetifier.
     *
     * @var string
     */
    protected $username;

    /**
     * The passaword.
     *
     * @var string
     */
    protected $password;

    /**
     * The default remote path.
     *
     * @var string
     */
    protected $remotepath = '/';

    /**
     * The starting path e.g. the 'home' of the connection.
     *
     * @var string
     */
    protected $startingpath;

    /**
     * The SSH public key.
     *
     * @var publickey
     */
    protected $ssh_key_pub;

    /**
     * The SSH private key.
     *
     * @var privatekey
     */
    protected $ssh_key_priv;

    /**
     * The SSH resource.
     *
     * @var passkey
     */
    protected $ssh_key_pass;

    /**
     * Class constructor, config might overwrite following options:
     * * protocol
     * * host
     * * port
     * * username
     * * password
     * * remotepath
     * * ssh_key_pub
     * * ssh_key_priv
     * * ssh_key_pass
     *
     * Should throw a \BackBee\Util\Transport\Exception\MisconfigurationException
     * on failure
     *
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        if (null !== $config) {
            foreach ($config as $key => $value) {
                if (true === property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        $this->startingpath = $this->remotepath;
    }

    /**
     * Magic getter on old properties syntax.
     *
     * @param  string $property
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException is $property doesn't exist.
     */
    public function __get($property)
    {
        if ('_' === substr($property, 0, 1)
            && property_exists($this, substr($property, 1))
        ) {
            $property = substr($property, 1);
            @trigger_error(
                sprintf(
                    'The property $_%s is deprecated since version 1.4 and ' .
                    'will be removed in 1.5. Use $%s instead.',
                    $property,
                    $property
                ),
                E_USER_DEPRECATED
            );

            return  $this->$property;
        }

        throw new \InvalidArgumentException('Unknown property ' . $property);
    }

    /**
     * Returns the absolute remote path of a file.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getAbsoluteRemotePath($path = null)
    {
        if (null === $path) {
            return $this->startingpath;
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        if (false === $parse_url = @parse_url($path)) {
            $parse_url = array('path' => $path);
        }

        return ('/' === substr($parse_url['path'], 0, 1) ? $path : $this->remotepath.'/'.$path);
    }

    /**
     * Trigger en PHP warning.
     *
     * @param string $message
     *
     * @return false
     */
    protected function triggerError($message)
    {
        trigger_error($message, E_USER_WARNING);

        return false;
    }

    /**
     * Establish a new connection to the remose server.
     *
     * @param string $host
     * @param string $port
     *
     * @return AbstractTransport
     *
     * @throws ConnectionException if connection failed
     */
    abstract public function connect($host = null, $port = null);

    /**
     * Authenticate on remote server.
     *
     * @param string $username
     * @param string $password
     *
      * @return AbstractTransport
     *
     * @throws ConnectionException if authentication failed
     */
    abstract public function login($username = null, $password = null);

    /**
     * Disconnect from the remote server and unset resources.
     *
     * @return AbstractTransport
     */
    abstract public function disconnect();

    /**
     * Change remote directory
     * Should trigger an error on failure.
     *
     * @param string $dir
     *
     * @return boolean TRUE on success, FALSE on failure
     */
    abstract public function cd($dir = null);

    /**
     * List remote files.
     *
     * @param string $dir
     *
     * @return array|FALSE An array of files
     */
    abstract public function ls($dir = null);

    /**
     * Returns the current remote path.
     *
     * @return string
     */
    abstract public function pwd();

    /**
     * Copy a local file to the remote server
     * Should trigger an error on failure.
     *
     * @param string  $local_file
     * @param string  $remote_file
     * @param boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function send($local_file, $remote_file, $overwrite = false);

    /**
     * Copy recursively a local file and subfiles to the remote server
     * Should trigger an error on failure.
     *
     * @param string  $local_file
     * @param string  $remote_file
     * @param boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function sendRecursive($local_path, $remote_path, $overwrite = false);

    /**
     * Receive a remote file on local filesystem
     * Should trigger an error on failure.
     *
     * @param string  $local_file
     * @param string  $remote_file
     * @param boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function get($local_file, $remote_file, $overwrite = false);

    /**
     * Receive recursively a remote file and subfiles on local filesystem
     * Should trigger an error on failure.
     *
     * @param string  $local_file
     * @param string  $remote_file
     * @param boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function getRecursive($local_path, $remote_path, $overwrite = false);

    /**
     * Creates a new remote directory
     * Should trigger an error on failure.
     *
     * @param string  $dir
     * @param boolean $recursive
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function mkdir($dir, $recursive = false);

    /**
     * Deletes a remote file
     * Should trigger an error on failure.
     *
     * @param string  $remote_path
     * @param boolean $recursive
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function delete($remote_path, $recursive = false);

    /**
     * Renames a remote file
     * Should trigger an error on failure.
     *
     * @param string $old_name
     * @param string $new_name
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    abstract public function rename($old_name, $new_name);

    /**
     * @deprecated since 1.4, will be removed in 1.5
     * @codeCoverageIgnore
     */
    protected function _getAbsoluteRemotePath($path = null)
    {
        @trigger_error(
            sprintf(
                'The method %s::_%s() is deprecated since version 1.4 and ' .
                'will be removed in 1.5. Use %s::%s() instead.',
                __CLASS__,
                'getAbsoluteRemotePath',
                __CLASS__,
                'getAbsoluteRemotePath'
            ),
            E_USER_DEPRECATED
        );

        return $this->getAbsoluteRemotePath($path);
    }

    /**
     * @deprecated since 1.4, will be removed in 1.5
     * @codeCoverageIgnore
     */
    protected function _trigger_error($message)
    {
        @trigger_error(
            sprintf(
                'The method %s::_%s() is deprecated since version 1.4 and ' .
                'will be removed in 1.5. Use %s::%s() instead.',
                __CLASS__,
                'trigger_error',
                __CLASS__,
                'triggerError'
            ),
            E_USER_DEPRECATED
        );

        return $this->triggerError($message);
    }
}
