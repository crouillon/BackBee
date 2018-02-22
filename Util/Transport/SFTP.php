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

@trigger_error(
    'The '.__NAMESPACE__.'\SFTP class is deprecated ' .
    'since version 1.4 and will be removed in 1.5.',
    E_USER_DEPRECATED
);

/**
 * @deprecated since version 1.4, to be removed in 1.5.
 * @codeCoverageIgnore
 */
class SFTP extends AbstractTransport
{

    /**
     * The default port number.
     *
     * @var int
     */
    protected $port = 22;

    /**
     * The SSH resource.
     *
     * @var resource
     */
    private $ssh_resource = null;

    /**
     * The SFTP resource.
     *
     * @var resource
     */
    private $sftp_resource = null;

    /**
     * Class constructor, config can overwrite following options:
     * * host
     * * port
     * * username
     * * password
     * * remotepath.
     *
     * @param array $config
     *
     * @throws TransportException if extensions OpenSSL or libssh2 are unavailable
     */
    public function __construct(array $config = array())
    {
        parent::__construct($config);

        if (!extension_loaded('openssl')) {
            throw new TransportException('The SFTP transport requires openssl extension.');
        }

        if (!function_exists('ssh2_connect')) {
            throw new TransportException('The SFTP transport requires libssh2 extension.');
        }
    }

    /**
     * Class destructor.
     *
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Establish a SSH connection.
     *
     * @param  string $host
     * @param  int    $port
     *
     * @return SFTP
     *
     * @throws TransportException if connection failed.
     */
    public function connect($host = null, $port = null)
    {
        $this->host = null !== $host ? $host : $this->host;
        $this->port = null !== $port ? $port : $this->port;

        if (false === $this->ssh_resource = ssh2_connect($this->host, $this->port)) {
            throw new TransportException(sprintf(
                'Enable to connect to %s:%i.',
                $this->host,
                $this->port
            ));
        }

        return $this;
    }

    /**
     * Authenticate on remote server.
     *
     * @param  string $username
     * @param  string $password
     *
     * @return SFTP
     *
     * @throws TransportException if authentication failed
     */
    public function login($username = null, $password = null)
    {
        $this->username = null !== $username ? $username : $this->username;
        $this->password = null !== $password ? $password : $this->password;

        if (null === $this->ssh_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        if (null === $this->ssh_key_pub) {
            if (false === ssh2_authpassword($this->ssh_resource, $this->username, $this->password)) {
                throw new TransportException(sprintf(
                    'Could not authenticate with username %s.',
                    $this->username
                ));
            }
        } else {
            if (false === ssh2_auth_pubkey_file(
                $this->ssh_resource,
                $this->username,
                $this->ssh_key_pub,
                $this->_ssh_key_priv,
                $this->_ssh_key_pass
            )) {
                throw new TransportException(sprintf(
                    'Could not authenticate with keyfile %s.',
                    $this->ssh_key_pub
                ));
            }
        }

        if (false === $this->sftp_resource = ssh2_sftp($this->ssh_resource)) {
            throw new TransportException("Could not initialize SFTP subsystem.");
        }

        return $this;
    }

    /**
     * Change remote directory.
     *
     * @param  string $dir
     *
     * @return SFTP
     *
     * @throws TransportException if SSH connection is invalid
     */
    public function cd($dir = null)
    {
        if (null === $this->sftp_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        $dir = $this->getAbsoluteRemotePath($dir);
        if (false === @ssh2_sftp_stat($this->sftp_resource, $this->remotepath)) {
            return $this->triggerError(sprintf('Unable to change remote directory to %s.', $this->remotepath));
        }

        $this->remotepath = $dir;

        return true;
    }

    /**
     * List remote files on $dir.
     *
     * @param  string $dir
     *
     * @return array|FALSE
     *
     * @throws TransportException if SSH connection is invalid
     */
    public function ls($dir = null)
    {
        if (null === $this->ssh_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        $dir = $this->getAbsoluteRemotePath($dir);
        if ('' === $data = $this->exec('ls '.$dir)) {
            return $this->triggerError(sprintf('Unable to list remote directory to %s.', $dir));
        }

        $files = [];
        foreach (explode("\n", $data) as $file) {
            $files[] = $dir.'/'.$file;
        }

        return $files;
    }

    /**
     * Returns the current remote path.
     *
     * @return string
     *
     * @throws TransportException if SSH connection is invalid
     */
    public function pwd()
    {
        if (null === $this->sftp_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

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
     *
     * @throws TransportException if SSH connection is invalid
     */
    public function send($local_file, $remote_file, $overwrite = false)
    {
        if (null === $this->sftp_resource
            || null === $this->ssh_resource
        ) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        if (!file_exists($local_file)) {
            return $this->triggerError(sprintf('Could not open local file: %s.', $local_file));
        }

        $remote_file = $this->getAbsoluteRemotePath($remote_file);
        @ssh2_sftp_mkdir($this->sftp_resource, dirname($remote_file), 0777, true);

        if (true === $overwrite
            || false === @ssh2_sftp_stat($this->sftp_resource, $remote_file)
        ) {
            if (false === @ssh2_scp_send($this->ssh_resource, $local_file, $remote_file)) {
                return $this->triggerError(sprintf(
                    'Could not send data from file %s to file %s.',
                    $local_file,
                    $remote_file
                ));
            }

            return true;
        }

        return $this->triggerError(sprintf('Remote file already exists: %s.', $remote_file));
    }

    /**
     * Copy recursively local files to the remote server.
     *
     * @param  string  $local_path
     * @param  string  $remote_path
     * @param  boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException if SSH connection is invalid
     */
    public function sendRecursive($local_path, $remote_path, $overwrite = false)
    {
        throw new TransportException(sprintf('Method not implemented yet.'));
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
     * @throws TransportException if SSH connection is invalid
     */
    public function get($local_file, $remote_file, $overwrite = false)
    {
        if (null === $this->sftp_resource
            || null === $this->ssh_resource
        ) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        $remote_file = $this->getAbsoluteRemotePath($remote_file);
        if (true === $overwrite
            || false === file_exists($local_file)
        ) {
            if (false === @ssh2_sftp_stat($this->sftp_resource, $remote_file)) {
                return $this->triggerError(sprintf('Could not open remote file: %s.', $remote_file));
            }

            if (false === @ssh2_scp_recv($this->ssh_resource, $remote_file, $local_file)) {
                return $this->triggerError(sprintf(
                    'Could not send data from file %s to file %s.',
                    $remote_file,
                    $local_file
                ));
            }

            return true;
        }

        return $this->triggerError(sprintf('Local file already exists: %s.', $local_file));
    }

    /**
     * Copy recursively remote files to the local filesystem.
     *
     * @param  string  $local_path
     * @param  string  $remote_path
     * @param  boolean $overwrite
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException if SSH connection is invalid
     */
    public function getRecursive($local_path, $remote_path, $overwrite = false)
    {
        throw new TransportException(sprintf('Method not implemented yet.'));
    }

    /**
     * Creates a new remote directory.
     *
     * @param  string  $dir
     * @param  boolean $recursive
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException Occures if SSH connection is invalid
     */
    public function mkdir($dir, $recursive = false)
    {
        if (null === $this->sftp_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        $dir = $this->getAbsoluteRemotePath($dir);
        if (false === @ssh2_sftp_mkdir($this->sftp_resource, $dir, 0777, $recursive)) {
            return $this->triggerError(sprintf('Unable to make directory: %s.', $dir));
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
     *
     * @throws TransportException Occures if SSH connection is invalid
     */
    public function delete($remote_path, $recursive = false)
    {
        if (null === $this->sftp_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        if (true === $recursive) {
            throw new TransportException(sprintf('REcursive option not implemented yet.'));
        }

        $remote_path = $this->getAbsoluteRemotePath($remote_path);
        if (false === @ssh2_sftp_stat($this->sftp_resource, $remote_path)) {
            return $this->triggerError(sprintf('Remote file to delete does not exist: %s.', $remote_path));
        }

        if (false === ssh2_sftp_unlink($this->sftp_resource, $remote_path)) {
            return ssh2_sftp_rmdir($this->sftp_resource, $remote_path);
        }

        return true;
    }

    /**
     * Renames a remote file.
     *
     * @param  string $old_name
     * @param  string $new_name
     *
     * @return boolean Returns TRUE on success or FALSE on error
     *
     * @throws TransportException Occures if SSH connection is invalid
     */
    public function rename($old_name, $new_name)
    {
        if (null === $this->sftp_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        $old_name = $this->getAbsoluteRemotePath($old_name);
        $new_name = $this->getAbsoluteRemotePath($new_name);

        if (false === @ssh2_sftp_stat($this->sftp_resource, $old_name)) {
            return $this->triggerError(sprintf('Could not open remote file: %s.', $old_name));
        }

        if (false !== @ssh2_sftp_stat($this->sftp_resource, $new_name)) {
            return $this->triggerError(sprintf('Remote file already exists: %s.', $new_name));
        }

        if (false === @ssh2_sftp_rename($this->sftp_resource, $old_name, $new_name)) {
            return $this->triggerError(sprintf('Unable to rename %s to %s', $old_name, $new_name));
        }

        return true;
    }

    /**
     * Disconnect from the remote server and unset resources.
     *
     * @return SFTP
     */
    public function disconnect()
    {
        if (null !== $this->ssh_resource) {
            $this->exec('echo "EXITING" && exit;');
            $this->ssh_resource = null;
            $this->sftp_resource = null;
        }

        return $this;
    }

    /**
     * Executes a command on remote server.
     *
     * @param  string $command
     *
     * @return string
     *
     * @throws TransportException Occures if SSH connection is invalid
     */
    private function exec($command)
    {
        if (null === $this->ssh_resource) {
            throw new TransportException(sprintf('None SSH connection available.'));
        }

        if (false === $stream = ssh2_exec($this->ssh_resource, $command)) {
            throw new TransportException(sprintf('SSH command `%s` failed.', $command));
        }

        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);

        return $data;
    }
}
