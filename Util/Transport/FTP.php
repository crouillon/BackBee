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

use BackBee\Util\Transport\Exception\TransportException;

/**
 * A FTP transport.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class FTP extends AbstractTransport
{

    /**
     * The default port.
     *
     * @var integer
     */
    protected $port = 21;

    /**
     * The PASV mode.
     *
     * @var boolean
     */
    protected $passive = true;

    /**
     * The transfert mode.
     *
     * @var string
     */
    protected $mode = FTP_ASCII;

    /**
     * The FTP resource
     *
     * @var Resource
     */
    private $ftp_stream = null;

    /**
     * Transport constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        parent::__construct($config);

        if (null !== $config) {
            if (array_key_exists('passive', $config)) {
                $this->passive = true === $config['passive'];
            }
            if (array_key_exists('mode', $config)) {
                $this->mode = defined('FTP_'.strtoupper($config['mode']))
                    ? constant('FTP_'.strtoupper($config['mode']))
                    : $this->mode;
            }
        }
    }

    /**
     * Transport destructor.
     *
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Establish a new connection to the remote server.
     *
     * @param  string  $host
     * @param  integer $port
     *
     * @return FTP
     *
     * @throws TransportException is something went wrong.
     */
    public function connect($host = null, $port = null)
    {
        if (!is_string($host)) {
            throw new TransportException(sprintf(
                'Host expect to be string, %s given: "%s".',
                gettype($host),
                $host
            ));
        }

        if (!is_int($port)) {
            throw new TransportException(sprintf(
                'Port expect to be long, %s given: "%s".',
                gettype($port),
                $port
            ));
        }

        $this->host = null !== $host ? $host : $this->host;
        $this->port = null !== $port ? $port : $this->port;

        $this->ftp_stream = ftp_connect($this->host, $this->port);
        if (false === $this->ftp_stream) {
            throw new TransportException(sprintf(
                'Enable to connect to %s:%i.',
                $this->host,
                $this->port
            ));
        }

        return $this;
    }

    /**
     * Tries to change dir to the defined remote path.
     * An error is triggered if failed.
     *
     * @param  string $username
     * @param  string $password
     *
     * @return FTP
     *
     * @throws TransportException is something went wrong.
     */
    public function login($username = null, $password = null)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        $this->username = null !== $username ? $username : $this->username;
        $this->password = null !== $password ? $password : $this->password;

        if (false === ftp_login($this->ftp_stream, $this->username, $this->password)) {
            throw new TransportException(sprintf(
                'Enable to log with username %s.',
                $this->username
            ));
        }

        if (false === ftp_pasv($this->ftp_stream, $this->passive)) {
            throw new TransportException(sprintf(
                'Enable to change mode to passive=%b.',
                $this->passive
            ));
        }

        return $this;
    }

    /**
     * Change remote directory.
     *
     * @param  string $dir
     *
     * @return boolean TRUE on success
     *
     * @throws TransportException is something went wrong.
     */
    public function cd($dir = null)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        $dir = null !== $dir ? $dir : $this->remotepath;
        if (false === @ftp_chdir($this->ftp_stream, $dir)) {
            throw new TransportException(sprintf(
                'Enable to change remote directory to %s.',
                $dir
            ));
        }

        return true;
    }

    /**
     * List remote files on $dir.
     *
     * @param  string $dir
     *
     * @return array|false
     *
     * @throws TransportException is something went wrong.
     */
    public function ls($dir = null)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        $dir = null !== $dir ? $dir : $this->pwd();
        if (false === $ls = ftp_nlist($this->ftp_stream, $dir)) {
            $ls = [];
        }

        return $ls;
    }

    /**
     * Returns the current remote path.
     *
     * @return string
     *
     * @throws TransportException is something went wrong.
     */
    public function pwd()
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        if (false === $pwd = ftp_pwd($this->ftp_stream)) {
            throw new TransportException(sprintf('Enable to get remote directory.'));
        }

        return $pwd;
    }

    /**
     * Copy a local file to the remote server.
     *
     * @param  string  $local_file
     * @param  string  $remote_file
     * @param  boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException is something went wrong.
     */
    public function send($local_file, $remote_file, $overwrite = false)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        if (false === ftp_put($this->ftp_stream, $remote_file, $local_file, $this->mode)) {
            throw new TransportException(sprintf('Enable to put file.'));
        }

        return true;
    }

    /**
     * Copy recursively a local file and subfiles to the remote server.
     *
     * @param  string  $local_file
     * @param  string  $remote_file
     * @param  boolean $overwrite
     *
     * @return false   Not implemented
     */
    public function sendRecursive($local_path, $remote_path, $overwrite = false)
    {
        return false;
    }

    /**
     * Copy a remote file on local filesystem.
     *
     * @param  string  $local_file
     * @param  string  $remote_file
     * @param  boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException is something went wrong.
     */
    public function get($local_file, $remote_file, $overwrite = false)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        if (false === ftp_get($this->ftp_stream, $local_file, $remote_file, $this->mode)) {
            throw new TransportException(sprintf(
                'Enable to get remote file %s to local file %s.',
                $remote_file,
                $local_file
            ));
        }

        return true;
    }

    /**
     * Copy recursively a remote directory on local filesystem.
     *
     * @param  string  $local_path
     * @param  string  $remote_path
     * @param  boolean $overwrite
     *
     * @return false   Not implemented
     */
    public function getRecursive($local_path, $remote_path, $overwrite = false)
    {
        return false;
    }

    /**
     * Creates a new remote directory.
     *
     * @param  string  $dir
     * @param  boolean $recursive
     *
     * @return false   Not implemented
     */
    public function mkdir($dir, $recursive = false)
    {
        return false;
    }

    /**
     * Deletes a remote file.
     *
     * @param  string  $remote_path
     * @param  boolean $recursive
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException is something went wrong.
     */
    public function delete($remote_path, $recursive = false)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        if (false === ftp_delete($this->ftp_stream, $remote_path)) {
            throw new TransportException(sprintf(
                'Enable to remove file %s.',
                $remote_path
            ));
        }

        return true;
    }

    /**
     * Renames a remote file.
     *
     * @param string $old_name
     * @param string $new_name
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException is something went wrong.
     */
    public function rename($old_name, $new_name)
    {
        if (!$this->ftp_stream) {
            throw new TransportException(sprintf('None FTP connection available.'));
        }

        if (false === ftp_rename($this->ftp_stream, $old_name, $new_name)) {
            throw new TransportException(sprintf(
                'Enable to rename file %s to file %s.',
                $old_name,
                $new_name
            ));
        }

        return true;
    }

    /**
     * Disconnects.
     *
     * @return FTP
     */
    public function disconnect()
    {
        if (!$this->ftp_stream) {
            return $this;
        }

        @ftp_close($this->ftp_stream);

        return $this;
    }
}
