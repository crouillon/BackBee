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

namespace BackBee\Stream;

/**
 * Interface for class content stream wrapper.
 * Only read methods should be available for the wrapper.
 *
 * @see http://php.net/manual/en/class.streamwrapper.php
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
interface WrapperInterface
{

    /**
     * This method is called in response to fclose().
     */
    public function stream_close();

    /**
     * This method is called in response to feof().
     *
     * @return bool Should return TRUE if the read/write position is at the end of the
     *              stream and if no more data is available to be read, or FALSE otherwise.
     */
    public function stream_eof();

    /**
     * This method is called immediately after the wrapper is initialized
     * (f.e. by fopen() and file_get_contents()).
     *
     * @param  string $path       Specifies the URL that was passed to the original function.
     * @param  string $mode       The mode used to open the file, as detailed for fopen().
     * @param  int    $options    Holds additional flags set by the streams API.
     * @param  string $openedPath If the path is opened successfully, and STREAM_USE_PATH is set
     *                            in options, opened_path should be set to the full path of the
     *                            file/resource that was actually opened.
     *
     * @return bool               Returns TRUE on success or FALSE on failure.
     */
    public function stream_open($path, $mode, $options, &$openedPath);

    /**
     * This method is called in response to fread() and fgets().
     *
     * @param  int $count How many bytes of data from the current position should be returned.
     *
     * @return string     If there are less than count bytes available, return as many as are available.
     *                    If no more data is available, return either FALSE or an empty string.
     */
    public function stream_read($count);

    /**
     * This method is called in response to fseek().
     * The read/write position of the stream should be updated according to the offset and whence.
     *
     * @param  int $offset The stream offset to seek to.
     * @param  int $whence Possible values:
     *                      * SEEK_SET - Set position equal to offset bytes.
     *                      * SEEK_CUR - Set position to current location plus offset.
     *                      * SEEK_END - Set position to end-of-file plus offset.
     *
     * @return bool        Return TRUE if the position was updated, FALSE otherwise.
     */
    public function stream_seek($offset, $whence = SEEK_SET);

    /**
     * This method is called in response to fstat().
     *
     * @return array Should return as many elements as stat() does.
     */
    public function stream_stat();

    /**
     * This method is called in response to fseek() to determine the current position.
     *
     * @return int Should return the current position of the stream.
     */
    public function stream_tell();

    /**
     * This method is called in response to all stat() related functions
     *
     * @param  string $path  The file path or URL to stat.
     * @param  int    $flags Holds additional flags set by the streams API.
     *
     * @return array         Should return as many elements as stat() does.
     */
    public function url_stat($path, $flags);
}
