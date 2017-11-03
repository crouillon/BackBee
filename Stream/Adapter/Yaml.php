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

namespace BackBee\Stream\Adapter;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as YamlParser;

use BackBee\Stream\AbstractWrapper;
use BackBee\Utils\File\File;

/**
 * Stream wrapper to interprete yaml file as class content description
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Yaml extends AbstractWrapper
{

    /**
     * Default options for the current context.
     * * pathinclude: should be an array of folders to look for yaml files
     * * extensions:  extensions to look for
     * * cache:       an optional cache adapter
     *
     * @var array
     */
    protected $defaultOptions = [
        'pathinclude' => [],
        'extensions' => ['.yml', '.yaml'],
        'cache' => null
    ];

    /**
     * The yaml file path.
     *
     * @var string
     */
    private $filename;

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
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        if ('r' !== $mode) {
            return $this->triggerError(
                sprintf('Invalid mode for opening %s, only `r` is allowed.', $path),
                $options & STREAM_REPORT_ERRORS
            );
        }

        if (false === $this->filename = $this->resolveFilePath($path)) {
            return $this->triggerError(
                sprintf('Cannot open %s, file not found.', $path),
                $options & STREAM_REPORT_ERRORS
            );
        }

        try {
            $this->setNamespace($path);
            $this->parseFile();
        } catch (\Exception $exception) {
            return $this->triggerError(
                $exception->getMessage(),
                $options & STREAM_REPORT_ERRORS
            );
        }

        if ($options & STREAM_USE_PATH) {
            $openedPath = $this->filename;
        }

        $this->position = 0;
        $this->data = $this->buildClass();

        return true;
    }

    /**
     * This method is called in response to fstat().
     *
     * @return array Return as many elements as stat() does.
     */
    public function stream_stat()
    {
        if ($this->filename) {
            return @stat($this->filename);
        }

        return false;
    }

    /**
     * This method is called in response to all stat() related functions
     *
     * @param  string $path  The file path or URL to stat.
     * @param  int    $flags Holds additional flags set by the streams API.
     *
     * @return array|false   Return as many elements as stat() does if file found, false elsewhere.
     */
    public function url_stat($path, $flags = 0)
    {
        if (false === $filename = $this->resolveFilePath($path)) {
            return $this->triggerError(
                sprintf('Cannot stat %s, file not found.', $path),
                !($flags & STREAM_URL_STAT_QUIET)
            );
        }

        if ($flags & STREAM_URL_STAT_LINK) {
            $stats = @lstat($filename);
        } else {
            $stats = @stat($filename);
        }

        if (false === $stats && !($flags & STREAM_URL_STAT_QUIET)) {
            return $this->triggerError(
                error_get_last()['message'],
                !($flags & STREAM_URL_STAT_QUIET)
            );
        }

        return $stats;
    }

    /**
     * This method is called in response to fclose().
     */
    public function stream_close()
    {
        parent::stream_close();

        $this->filename = null;
    }

    /**
     * Returns the classname of the class content.
     *
     * @return string
     */
    protected function getClassname()
    {
        $this->classname = File::removeExtension(basename($this->filename));

        return parent::getClassname();
    }

    /**
     * Finds the namespace from the file looked for.
     *
     * @param string $filename
     */
    private function setNamespace($filename)
    {
        $dirname = dirname(str_replace(self::PROTOCOL . '://', '', $filename));
        if (1 < strlen($dirname)) {
            $this->namespace .= str_replace([DIRECTORY_SEPARATOR, '/'], NAMESPACE_SEPARATOR, $dirname);
        }
    }

    /**
     * Parses the yaml fle.
     *
     * @throws ParseException if smethng went wrong.
     */
    private function parseFile()
    {
        $data = (array) YamlParser::parse(@file_get_contents($this->filename));
        if (empty($data)) {
            throw new ParseException('No valid class content description found');
        }

        foreach ($data as $definition) {
            if (!is_array($definition)) {
                throw new ParseException('No valid class content description found');
            }

            $this->parseDefinition($definition);
        }
    }

    /**
     * Parses a class definition
     *
     * @param  array $definition
     *
     * @throws ParseException if there is an error.
     */
    private function parseDefinition(array $definition)
    {
        foreach ($definition as $type => $desc) {
            $methodName = sprintf('set%s', ucfirst($type));
            if (!method_exists($this, $methodName)) {
                throw new ParseException(sprintf('Unknown property type %s.', $type));
            }

            call_user_func([$this, $methodName], $desc);
        }
    }

    /**
     * Return the real yaml file path of the loading class.
     *
     * @param  string $path
     *
     * @return string|false The real path if found, false otherwise.
     */
    private function resolveFilePath($path)
    {
        $filepath = str_replace(
            [self::PROTOCOL . '://', '/'],
            ['', DIRECTORY_SEPARATOR],
            $path
        );

        foreach ((array) $this->getOption('extensions') as $ext) {
            $filename = $filepath . $ext;
            File::resolveFilepath(
                $filename,
                null,
                ['include_path' => (array) $this->getOption('pathinclude')]
            );

            if (is_file($filename)) {
                return $filename;
            }
        }

        return false;
    }
}
