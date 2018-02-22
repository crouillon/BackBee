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

use BackBee\Util\Transport\Exception\AuthenticationException;
use BackBee\Util\Transport\Exception\MisconfigurationException;
use BackBee\Util\Transport\Exception\TransportException;

/**
 * A local filesystem transport.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class FileSystem extends AbstractTransport
{
    /**
     * Class constructor, config should overwrite following option:
     * * remotepath.
     *
     * @param  array $config
     *
     * @throws MisconfigurationException if the remote path can not be created
     */
    public function __construct(array $config = null)
    {
        parent::__construct($config);

        if (null !== $this->remotepath
            && false === file_exists($this->remotepath)
            && false === @mkdir($this->remotepath, 0755, true)
        ) {
            throw new MisconfigurationException(sprintf(
                'Cannot create remote path %s',
                $this->remotepath
            ));
        }
    }

    /**
     * Establish a new connection to the remote server.
     *
     * @param  string $host
     * @param  string $port
     *
     * @return FileSystem
     */
    public function connect($host = null, $port = null)
    {
        return $this;
    }

    /**
     * Tries to change dir to the defined remote path.
     * An error is triggered if failed.
     *
     * @param  string $username
     * @param  string $password
     *
     * @return FileSystem
     *
     * @throws AuthenticationException
     */
    public function login($username = null, $password = null)
    {
        if (false === @$this->cd()) {
            if (true === @$this->mkdir(null)) {
                @$this->cd();
            } else {
                throw new AuthenticationException(sprintf(
                    'Unable to change dir to %s',
                    $this->remotepath
                ));
            }
        }

        return $this;
    }

    /**
     * Disconnects.
     *
     * @return FileSystem
     */
    public function disconnect()
    {
        return $this;
    }

    /**
     * Change remote directory.
     *
     * @param  string $dir
     *
     * @return boolean TRUE on success
     */
    public function cd($dir = null)
    {
        $dir = $this->getAbsoluteRemotePath($dir);
        if (!is_dir($dir)) {
            return $this->triggerError(sprintf(
                'Unable to change remote directory to %s.',
                $dir
            ));
        }
        $this->remotepath = $dir;

        return true;
    }

    /**
     * List remote files on $dir.
     *
     * @param  string $dir
     *
     * @return array|false
     */
    public function ls($dir = null)
    {
        $dir = $this->getAbsoluteRemotePath(null === $dir ? $this->remotepath : $dir);
        if (false === $ls = @scandir($dir)) {
            return $this->triggerError(sprintf(
                'Unable to list files of remote directory %s.',
                $dir
            ));
        }

        return $ls;
    }

    /**
     * Returns the current remote path.
     *
     * @return string
     */
    public function pwd()
    {
        return $this->remotepath;
    }

    /**
     * Copy a local file to the remote server.
     *
     * @param  string  $local_file
     * @param  string  $remote_file
     * @param  boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function send($local_file, $remote_file, $overwrite = false)
    {
        if (false === file_exists($local_file)) {
            return $this->triggerError(sprintf('Could not open local file: %s.', $local_file));
        }

        $remote_file = $this->getAbsoluteRemotePath($remote_file);
        if (false === $overwrite && true === file_exists($remote_file)) {
            return $this->triggerError(sprintf('Remote file already exists: %s.', $remote_file));
        }

        if (false === file_exists(dirname($remote_file))) {
            @$this->mkdir(dirname($remote_file), true);
        }

        if (false === @copy($local_file, $remote_file)) {
            return $this->triggerError(sprintf(
                'Could not send data from file %s to file %s.',
                $local_file,
                $remote_file
            ));
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
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function sendRecursive($local_path, $remote_path, $overwrite = false)
    {
        if (!is_dir($local_path)) {
            return $this->send($local_path, $remote_path, $overwrite);
        }

        if (false === $lls = @scandir($local_path)) {
            return $this->triggerError(sprintf(
                'Unable to list files of local directory %s.',
                $local_path
            ));
        }

        $remote_path = $this->getAbsoluteRemotePath($remote_path);
        if (file_exists($remote_path)
            && !is_dir($remote_path)
        ) {
            return $this->triggerError(sprintf(
                'A file named %s already exists, can\'t create folder.',
                $remote_path
            ));
        } elseif (!file_exists($remote_path)
            && !$this->mkdir($remote_path, true)
        ) {
            return false;
        }

        $currentpwd = $this->pwd();
        $this->cd($remote_path);
        foreach ($lls as $file) {
            if ($file != "." && $file != "..") {
                $this->sendRecursive(
                    $local_path . DIRECTORY_SEPARATOR . $file,
                    $this->pwd() . DIRECTORY_SEPARATOR . $file,
                    $overwrite
                );
            }
        }
        $this->cd($currentpwd);

        return true;
    }

    /**
     * Copy a remote file on local filesystem.
     *
     * @param  string  $local_file
     * @param  string  $remote_file
     * @param  boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function get($local_file, $remote_file, $overwrite = false)
    {
        if (false === $overwrite && true === file_exists($local_file)) {
            return $this->triggerError(sprintf(
                'Local file already exists: %s.',
                $local_file
            ));
        }

        $remote_file = $this->getAbsoluteRemotePath($remote_file);
        if (false === file_exists($remote_file)) {
            return $this->triggerError(sprintf(
                'Could not open remote file: %s.',
                $remote_file
            ));
        }

        if (false === @copy($remote_file, $local_file)) {
            return $this->triggerError(sprintf(
                'Could not send data from file %s to file %s.',
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
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function getRecursive($local_path, $remote_path, $overwrite = false)
    {
        $remote_path = $this->getAbsoluteRemotePath($remote_path);
        if (false === is_dir($remote_path)) {
            return $this->get($local_path, $remote_path, $overwrite);
        }

        if (!file_exists($local_path)) {
            if (false === @mkdir($local_path, 0755, true)) {
                return $this->triggerError(sprintf(
                    'Unable to create local folder %s.',
                    $local_path
                ));
            }
        } elseif (!is_dir($local_path)) {
            return $this->triggerError(sprintf(
                'A file named %s already exist, can\'t create folder.',
                $local_path
            ));
        }

        $currentpwd = $this->pwd();
        $this->cd($remote_path);
        foreach ($this->ls() as $file) {
            if ($file != "." && $file != "..") {
                $this->getRecursive(
                    $local_path . DIRECTORY_SEPARATOR . $file,
                    $this->pwd() . DIRECTORY_SEPARATOR.$file,
                    $overwrite
                );
            }
        }
        $this->cd($currentpwd);

        return true;
    }

    /**
     * Creates a new remote directory.
     *
     * @param  string  $dir
     * @param  boolean $recursive
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function mkdir($dir, $recursive = false)
    {
        $dir = $this->getAbsoluteRemotePath($dir);
        if (false === @mkdir($dir, 0777, $recursive)) {
            return $this->triggerError(sprintf(
                'Unable to make directory: %s.',
                $dir
            ));
        }

        return true;
    }

    /**
     * Deletes a remote file.
     *
     * @param  string  $remote_path
     * @param  boolean $recursive
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function delete($remote_path, $recursive = false)
    {
        $remote_path = $this->getAbsoluteRemotePath($remote_path);
        if (true === @is_dir($remote_path)) {
            if (true === $recursive) {
                foreach ($this->ls($remote_path) as $file) {
                    if ('.' !== $file && '..' !== $file) {
                        $this->delete($remote_path.DIRECTORY_SEPARATOR.$file, $recursive);
                    }
                }
            }

            if (false === @rmdir($remote_path)) {
                return $this->triggerError(sprintf('Unable to delete directory %s', $remote_path));
            }
        } else {
            if (false === @unlink($remote_path)) {
                return $this->triggerError(sprintf('Unable to delete file %s', $remote_path));
            }
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
     */
    public function rename($old_name, $new_name)
    {
        $old_name = $this->getAbsoluteRemotePath($old_name);
        $new_name = $this->getAbsoluteRemotePath($new_name);

        if (false === file_exists($old_name)) {
            return $this->triggerError(sprintf('Could not open remote file: %s.', $old_name));
        }

        if (true === file_exists($new_name)) {
            return $this->triggerError(sprintf('Remote file already exists: %s.', $new_name));
        }

        if (false === @rename($old_name, $new_name)) {
            return $this->triggerError(sprintf('Unable to rename %s to %s', $old_name, $new_name));
        }

        return true;
    }
}
