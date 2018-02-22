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

namespace BackBee\Stream\Adapter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as YamlParser;

use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\Cache\CacheInterface;
use BackBee\ClassContent\AbstractContent;
use BackBee\Event\Event;
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
     * * dispatcher:  an optional event dispatcher
     *
     * @var array
     */
    protected $defaultOptions = [
        'pathinclude' => [],
        'extensions' => ['.yml', '.yaml'],
        'cache' => null,
        'dispatcher' => null
    ];

    /**
     * The yaml file path.
     *
     * @var string
     */
    private $filename;

    /**
     * A cache adapter.
     *
     * @var CacheInterface
     */
    private $cache;

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
     *
     * @throws \RuntimeException if the mode is not `r`.
     * @throws ClassNotFoundException if a corresponding file cannot be found.
     * @throws ParseException is the founded file cannot be parsed.
     */
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        if (!in_array($mode, ['r', 'rb'])) {
            throw new \RuntimeException(sprintf('Invalid mode for opening %s, only `r` and `rb` are allowed.', $path));
        }

        if (false === $this->filename = $this->resolveFilePath($path)) {
            throw new ClassNotFoundException(sprintf('Cannot open %s, file not found.', $path));
        }

        if (!$this->readFromCache()) {
            $this->setNamespace($path);
            $this->parseFile();

            if ($options & STREAM_USE_PATH) {
                $openedPath = $this->filename;
            }

            $this->position = 0;
            $this->data = $this->buildClass();
            $this->saveToCache();
        }

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
            if (!($flags & STREAM_URL_STAT_QUIET)) {
                trigger_error(sprintf('Cannot stat %s, file not found.', $path), E_USER_WARNING);
            }

            return false;
        }

        if ($flags & STREAM_URL_STAT_LINK) {
            $stats = @lstat($filename);
        } else {
            $stats = @stat($filename);
        }

        if (false === $stats && !($flags & STREAM_URL_STAT_QUIET)) {
            trigger_error(error_get_last()['message'], E_USER_WARNING);
        }

        return $stats;
    }

    /**
     * This method is called in response to fclose().
     */
    public function stream_close()
    {
        parent::stream_close();

        $this->filename = $this->cache = $this->dispatcher = null;
    }

    /**
     * Finds classnames matching a pattern.
     *
     * @param  string $pattern The pattern.
     *
     * @return string[]|false  Returns an array containing the matched files/directories,
     *                         an empty array if no file matched or FALSE on error.
     */
    public function glob($pattern)
    {
        $classnames = [];
        foreach ((array) $this->getOption('pathinclude') as $path) {
            foreach ((array) $this->getOption('extensions') as $extension) {
                $filepath = $path . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pattern) . $extension;
                if (false !== $files = @glob($filepath)) {
                    array_walk($files, function (&$file) use ($path) {
                        $file = AbstractContent::CLASSCONTENT_BASE_NAMESPACE .
                            trim(
                                str_replace(
                                    [$path, DIRECTORY_SEPARATOR, '/'],
                                    ['', NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR],
                                    File::removeExtension($file)
                                ),
                                NAMESPACE_SEPARATOR
                            );
                    });
                    $classnames = array_merge($classnames, $files);
                }
            }
        }

        return array_unique($classnames);
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

        foreach ($this->dispatchEvents($data) as $definition) {
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
            ['', '/'],
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

    /**
     * Returns a cache adapter if defined in option, false elsewhere.
     *
     * @return CacheInterface|false
     */
    private function getCacheAdapter()
    {
        if (null === $this->cache) {
            $this->cache = false;
            $cache = $this->getOption('cache');
            if ($cache instanceof CacheInterface) {
                $this->cache = $cache;
            }
        }

        return $this->cache;
    }

    /**
     * Reads the class content from cache if it exists and is valid.
     *
     * @return boolean
     */
    private function readFromCache()
    {
        if (false !== $this->getCacheAdapter()) {
            $expire = new \DateTime(sprintf('@%s', $this->stream_stat()['mtime']));
            if (false !== $data = $this->getCacheAdapter()->load(md5($this->filename), false, $expire)) {
                $this->data = $data;

                return true;
            }
        }

        return false;
    }

    /**
     * Saves the generated content class to cache if adapter is defined.
     */
    private function saveToCache()
    {
        if (false !== $this->getCacheAdapter()) {
            $this->getCacheAdapter()->save(md5($this->filename), $this->data, null, null);
        }
    }

    /**
     * Dispatches `streamparsing` events allowing listeners to change the definition.
     *
     * @param  array $data
     *
     * @return array
     */
    private function dispatchEvents(array $data)
    {
        $dispatcher = $this->getOption('dispatcher');
        if ($dispatcher instanceof EventDispatcherInterface) {
            $classname = $this->getNamespace() . NAMESPACE_SEPARATOR . $this->getClassname();
            $event = new Event($classname, ['data' => $data]);

            $dispatcher->dispatch(
                sprintf(
                    '%s.streamparsing',
                    strtolower(str_replace(
                        [AbstractContent::CLASSCONTENT_BASE_NAMESPACE, NAMESPACE_SEPARATOR],
                        ['', '.'],
                        $classname
                    ))
                ),
                $event
            );

            $dispatcher->dispatch(
                'classcontent.streamparsing',
                $event
            );

            if ($event->hasArgument('data')) {
                $data = $event->getArgument('data');
            }
        }

        return $data;
    }
}
