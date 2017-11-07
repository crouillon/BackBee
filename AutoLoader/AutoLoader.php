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

namespace BackBee\AutoLoader;

use BackBee\BBApplication;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBee\Event\Dispatcher;
use BackBee\Stream\AbstractWrapper;

if (false === defined('NAMESPACE_SEPARATOR')) {
    define('NAMESPACE_SEPARATOR', '\\');
}

/**
 * AutoLoader implements an autoloader for BackBee5.
 *
 * It allows to load classes that:
 *
 * * use the standards for namespaces and class names
 * * throw defined wrappers returning php code
 *
 * Classes from namespace part can be looked for in a list
 * of wrappers and/or a list of locations.
 *
 * Beware of the auloloader begins to look for class throw the
 * defined wrappers then in the provided locations
 *
 * Example usage:
 *
 *     $autoloader = new \BackBee\AutoLoader\AutoLoader();
 *
 *     // register classes throw wrappers
 *     $autoloader->registerStreamWrapper('BackBee\ClassContent',
 *                                        'bb.class',
 *                                        '\BackBee\Stream\YamlWrapper');
 *
 *     // register classes by namespaces
 *     $autoloader->registerNamespace('BackBee', __DIR__)
 *                ->registerNamespace('Symfony', __DIR__.DIRECTORY_SEPARATOR.'vendor');
 *
 *     // activate the auloloader
 *     $autoloader->register();
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class AutoLoader implements DumpableServiceInterface, DumpableServiceProxyInterface
{

    /**
     * Availables wrappers to resolve class loading.
     *
     * @var array
     */
    private $availableWrappers;

    /**
     * Extensions to include searching file.
     *
     * @var string[]
     */
    private $includeExtensions = ['.php'];

    /**
     * Handled namespace locations.
     *
     * @var string[]
     */
    private $namespaces;

    /**
     * Namespaces wrappers.
     *
     * @var array
     */
    private $streamWrappers;

    /**
     * Is the namespace registered?
     *
     * @var Boolean
     */
    private $registeredNamespace;

    /**
     * An event disptacher to use.
     *
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * Is the autolader already restored by container?
     *
     * @var boolean
     */
    private $isRestored;

    /**
     * Class constructor.
     *
     * @param Dispatcher|null $dispatcher Optionnal, an events dispatcher.
     */
    public function __construct($dispatcher = null, $arg = null)
    {
        $oldSignature = ($dispatcher instanceof BBApplication)
            && (is_null($arg) || $arg instanceof Dispatcher);
        $newSignature = is_null($dispatcher) || $dispatcher instanceof Dispatcher;

        // confirm possible signatures
        if (!$oldSignature && !$newSignature) {
            throw new \BadMethodCallException(
                'Unable to construct AutoLoader, please provide the correct arguments'
            );
        }

        if ($oldSignature) {
            @trigger_error('The '.__CLASS__.'(BBApplication, Dispatcher) is deprecated since v1.4 '
                . 'and will be removed in v1.5. Use '.__CLASS__.'(Dispatcher) instead.', E_USER_DEPRECATED);

            // renamed for clarity
            $dispatcher =  $arg;
        }

        $this->setEventDispatcher($dispatcher);

        $this->availableWrappers = stream_get_wrappers();
        $this->isRestored = false;
    }

    /**
     * Sets the BackBee Application.
     *
     * @param  BBApplication|null $application Optionnal, a BackBee Application.
     *
     * @return AutoLoader                      The current autoloader instance.
     *
     * @deprecated since version 1.4 will be removed in 1.5
     * @codeCoverageIgnore
     */
    public function setApplication(BBApplication $application = null)
    {
        return $this;
    }

    /**
     * Returns the current BackBee application if defined, NULL otherwise.
     *
     * @return BBApplication|null
     *
     * @deprecated since version 1.4 will be removed in 1.5
     * @codeCoverageIgnore
     */
    public function getApplication()
    {
        return null;
    }

    /**
     * Sets the events dispatcher.
     *
     * @param  Dispatcher|null $dispatcher Optionnal, an events dispatcher.
     *
     * @return AutoLoader                  The current autoloader instance.
     */
    public function setEventDispatcher(Dispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Returns the events dispatcher if defined, NULL otherwise.
     *
     * @return Dispatcher|null
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Includes a file.
     *
     * @param  string  $filename     The filename to include.
     *
     * @return boolean               True if the class is found false elsewhere
     *
     * @throws SyntaxErrorException  if the generated PHP code is not valid.
     */
    private function includeClass($filename)
    {
        try {
            @include $filename;

            return true;
        } catch (Exception\ClassNotFoundException $e) {
            // Nothing to do
        } catch (\Exception $e) {
            // The include php file is not valid
            throw new Exception\SyntaxErrorException($e->getMessage(), null, $e->getPrevious());
        }

        return false;
    }

    /**
     * Scans an array of path to look for a class file.
     *
     * @param  string[] $pathfiles  An array of paths.
     * @param  string   $path       The looking for file.
     * @param  string   $registered A registered directory.
     *
     * @return boolean              True if the class is found false elsewhere
     */
    private function scanPaths(array $pathfiles, $path, $registered)
    {
        $dir = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $registered);
        foreach ($pathfiles as $pathfile) {
            $filename = $path . DIRECTORY_SEPARATOR . $pathfile;

            if (!file_exists($filename)) {
                $filename = $path .DIRECTORY_SEPARATOR . str_replace($dir, '', $pathfile);
            }

            if (is_readable($filename) && $this->includeClass($filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Looks for class definition throw declared wrappers according to the namespace.
     *
     * @param  string  $namespace The namespace's class
     * @param  string  $classname The class name looked for
     *
     * @return boolean            True if the class is found false elsewhere
     */
    private function autoloadThrowWrappers($namespace, $classname)
    {
        foreach ((array) $this->streamWrappers as $registered => $wrappers) {
            if (0 !== strpos($namespace, $registered)) {
                continue;
            }

            $this->registeredNamespace = true;

            $classpath = str_replace([$registered, NAMESPACE_SEPARATOR], ['', DIRECTORY_SEPARATOR], $namespace);
            if (DIRECTORY_SEPARATOR == substr($classpath, 0, 1)) {
                $classpath = substr($classpath, 1);
            }

            foreach ($wrappers as $wrapper) {
                $filename = sprintf('%s://%s/%s', $wrapper['protocol'], $classpath, $classname);
                if ($this->includeClass($filename)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Looks for class definition using the PHP 5.3 standards to the namespace.
     *
     * @param  string  $namespace The namespace's class
     * @param  string  $classname The class name looked for
     *
     * @return boolean            True if the class is found false elsewhere
     */
    private function autoloadThrowFilesystem($namespace, $classname)
    {
        $pathfiles = [];
        $dir = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace);
        foreach ((array) $this->includeExtensions as $ext) {
            $pathfiles[] = $dir . DIRECTORY_SEPARATOR . $classname . $ext;
        }

        foreach ((array) $this->namespaces as $registered => $paths) {
            if (0 !== strpos($namespace, $registered)) {
                continue;
            }

            $this->registeredNamespace = true;

            foreach ($paths as $path) {
                if ($this->scanPaths($pathfiles, $path, $registered)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the namespace and the classname from  class path.
     *
     * @param  string $classpath The absolute classname
     *
     * @return array             [$namespace, $classname]
     *
     * @throws Exception\InvalidNamespaceException if the namespace is not valid.
     * @throws Exception\InvalidClassnameException if the class name is not valid.
     */
    private function normalizeClassname($classpath)
    {
        if (NAMESPACE_SEPARATOR === substr($classpath, 0, 1)) {
            $classpath = substr($classpath, 1);
        }

        $namespace = '';
        $classname = $classpath;
        if (false !== ($pos = strrpos($classpath, NAMESPACE_SEPARATOR))) {
            $namespace = substr($classpath, 0, $pos);
            $classname = substr($classpath, $pos + 1);
        }

        $pattern = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
        $namespacePattern = sprintf('/^(%s\\\)*%s$/', $pattern, $pattern);
        $classnamePattern = sprintf('/^%s$/', $pattern);

        if (!empty($namespace) && !preg_match($namespacePattern, $namespace)) {
            throw new Exception\InvalidNamespaceException(sprintf('Invalid namespace provided: %s.', $namespace));
        }

        if (!preg_match($classnamePattern, $classname)) {
            throw new Exception\InvalidClassnameException(sprintf('Invalid class name provided: %s.', $classname));
        }

        return [$namespace, $classname];
    }

    /**
     * Registers pre-defined stream wrappers.
     */
    private function registerStreams()
    {
        foreach ($this->streamWrappers as $wrappers) {
            foreach ($wrappers as $wrapper) {
                if (!in_array($wrapper['protocol'], $this->availableWrappers)) {
                    stream_wrapper_register($wrapper['protocol'], $wrapper['classname']);
                    $this->availableWrappers = stream_get_wrappers();
                }
            }
        }
    }

    /**
     * Looks for the class name, call back function for spl_autolad_register()
     * First using the defined wrappers then throw filesystem.
     *
     * @param  string $classpath
     *
     * @throws Exception\ClassNotFoundException if  the class can not be found.
     */
    public function autoload($classpath)
    {
        $this->registeredNamespace = false;

        list($namespace, $classname) = $this->normalizeClassname($classpath);
        $classpath = $namespace . NAMESPACE_SEPARATOR . $classname;

        if (!$this->autoloadThrowWrappers($namespace, $classname)
            && !$this->autoloadThrowFilesystem($namespace, $classname)
            && true === $this->registeredNamespace
        ) {
            throw new Exception\ClassNotFoundException(sprintf(
                'Class %s%s%s not found.',
                $namespace,
                NAMESPACE_SEPARATOR,
                $classname
            ));
        }

        if (null !== $this->getEventDispatcher()
            && is_subclass_of($classpath, AbstractClassContent::class)
        ) {
            $this->getEventDispatcher()->triggerEvent('include', new $classpath());
        }
    }

    /**
     * Returns the wrappers registered for provided namespaces and protocols.
     *
     * @param string|array $namespace The namespaces to look for
     * @param string|array $protocol  The protocols to use
     *
     * @return array An array of wrappers registered for these namespaces and protocols
     */
    public function getStreamWrapperClassname($namespace, $protocol)
    {
        $namespaces = (array) $namespace;
        $protocols = (array) $protocol;

        $result = [];
        foreach ((array) $this->streamWrappers as $ns => $wrappers) {
            if (!in_array($ns, $namespaces)) {
                continue;
            }

            foreach ($wrappers as $wrapper) {
                if (in_array($wrapper['protocol'], $protocols)) {
                    $result[] = $wrapper['classname'];
                }
            }
        }

        return $result;
    }

    /**
     * Returns AClassContent whom classname matches the provided pattern.
     *
     * @param  string     $pattern  The pattern to test (ex: Media/*)
     * @param  string     $protocol Optional, he stream protocol (default: bb.class)
     *
     * @return array|false          An array of classnames matching the pattern
     *                              or FALSE if none found
     */
    public function glob($pattern, $protocol = AbstractWrapper::PROTOCOL)
    {
        $classnames = [];
        $wrappers = $this->getStreamWrapperClassname('BackBee\ClassContent', $protocol);
        foreach ((array) $wrappers as $classname) {
            $wrapper = new $classname();
            if (false !== $matchingclass = $wrapper->glob($pattern)) {
                $classnames = array_merge($classnames, $matchingclass);
            }
        }

        if (0 == count($classnames)) {
            return false;
        }

        array_walk($classnames, function (&$item) {
            $item = str_replace('/', NAMESPACE_SEPARATOR, $item);
        });

        return array_unique($classnames);
    }

    /**
     * Registers this auloloader.
     *
     * @param  boolean $throw   Optional, this parameter specifies whether
     *                          spl_autoload_register should throw exceptions
     *                          when the autoload_function cannot be registered.
     * @param  boolean $prepend Optional, if TRUE, spl_autoload_register will
     *                          prepend the autoloader on the autoload stack
     *                          instead of appending it.
     *
     * @return AutoLoader       The current instance of the autoloader.
     */
    public function register($throw = true, $prepend = false)
    {
        spl_autoload_register([$this, 'autoload'], $throw, $prepend);

        return $this;
    }

    /**
     * Registers namespace parts to look for class name.
     *
     * @param  string       $namespace The namespace
     * @param  string|array $paths     One or an array of associated locations
     *
     * @return AutoLoader              The current instance of the autoloader.
     */
    public function registerNamespace($namespace, $paths)
    {
        $namespace = trim($namespace, NAMESPACE_SEPARATOR);

        if (!isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = [];
        }

        $this->namespaces[$namespace] = array_merge($this->namespaces[$namespace], (array) $paths);

        return $this;
    }

    /**
     * Registers listeners namespace parts to look for class name.
     *
     * @param  string     $path A directory path.
     *
     * @return AutoLoader       The current instance of the autoloader.
     */
    public function registerListenerNamespace($path)
    {
        if (false === isset($this->namespaces['BackBee\Event\Listener'])) {
            $this->namespaces['BackBee\Event\Listener'] = [];
        }

        array_unshift($this->namespaces['BackBee\Event\Listener'], $path);

        return $this;
    }

    /**
     * Registers stream wrappers.
     *
     * @param  string     $namespace The namespace.
     * @param  string     $protocol  The wrapper's protocol.
     * @param  string     $classname The class name implementing the wrapper.
     *
     * @return AutoLoader            The current instance of the autoloader.
     */
    public function registerStreamWrapper($namespace, $protocol, $classname)
    {
        $namespace = trim($namespace, NAMESPACE_SEPARATOR);

        if (!isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = [];
        }

        $this->streamWrappers[$namespace][] = [
            'protocol' => $protocol,
            'classname' => $classname
        ];

        ksort($this->streamWrappers);

        $this->registerStreams();

        return $this;
    }

    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required.
     *
     * @return string|null The namespace of the class proxy to use on restore
     *                     or null if no proxy required.
     */
    public function getClassProxy()
    {
        return null;
    }

    /**
     * Dumps current service state so we can restore it later by calling
     * DumpableServiceInterface::restore() with the dump array produced
     * by this method.
     *
     * @return array Contains every datas required by this service to be
     *               restored at the same state.
     */
    public function dump(array $options = [])
    {
        return [
            'namespaces_locations' => $this->namespaces,
            'wrappers_namespaces'  => $this->streamWrappers,
            'has_event_dispatcher' => (null !== $this->dispatcher),
        ];
    }

    /**
     * Restore current service to the dump's state.
     *
     * @param array $dump The dump provided by DumpableServiceInterface::dump()
     *                    from where we can restore current service.
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        if (true === $dump['has_event_dispatcher']) {
            $this->setEventDispatcher($container->get('event.dispatcher'));
        }

        $this->register();

        $this->namespaces = $dump['namespaces_locations'];
        $this->streamWrappers = $dump['wrappers_namespaces'];

        if (0 < count($dump['wrappers_namespaces'])) {
            $this->registerStreams();
        }

        $this->isRestored = true;
    }

    /**
     * @return boolean True if current service is already restored, otherwise false.
     */
    public function isRestored()
    {
        return $this->isRestored;
    }
}
