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

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Yaml\Exception\ParseException;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Repository\ClassContentRepository;

/**
 * Base abstract class for class content stream wrapper.
 *
 * BackBee defines bb.class protocol to include its class definition
 * Several wrappers could be defined for several storing formats:
 *  - yaml files
 *  - xml files
 *  - yaml stream stored in DB
 *  - ...
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AbstractWrapper implements WrapperInterface
{

    const PROTOCOL = 'bb.class';
    const TEMPLATE = '<?php
namespace <namespace>;

/**
<docblock>
 *
 * @\Doctrine\ORM\Mapping\Entity(repositoryClass="<repository>")
 * @\Doctrine\ORM\Mapping\Table(name="content")
 * @\Doctrine\ORM\Mapping\HasLifecycleCallbacks
 */
class <classname> extends <extends> <interfaces>
{
    <traits>

    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);
        $this->initData();
    }

    protected function initData()
    {
        <properties>
        <elements>
        <parameters>
        parent::initData();
    }
}';

    /**
     * The current context, or NULL if no context was passed to the caller function.
     *
     * @var resource
     */
    public $context;

    /**
     * Default options for the current context.
     *
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Options for the current context.
     *
     * @var array
     */
    private $options;

    /**
     * The data streamed.
     *
     * @var string
     */
    protected $data;

    /**
     * The internal position in the stream.
     *
     * @var int
     */
    protected $position;

    /**
     * The namespace of the class content loaded.
     *
     * @var string
     */
    protected $namespace = AbstractClassContent::CLASSCONTENT_BASE_NAMESPACE;

    /**
     * The doctrine repository associated to the class content loaded.
     *
     * @var string
     */
    protected $repository = ClassContentRepository::class;

    /**
     * the class content name to load.
     *
     * @var string
     */
    protected $classname = '';

    /**
     * The class to be extended by the class content loaded.
     *
     * @var string
     */
    protected $extends = NAMESPACE_SEPARATOR . AbstractClassContent::class;

    /**
     * Interface(s) used by the class content.
     *
     * @var string[]
     */
    protected $interfaces = [];

    /**
     * Trait(s) used by the class content.
     *
     * @var string[]
     */
    protected $traits = [];

    /**
     * the properties of the class content.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * The elements of the class content.
     *
     * @var array
     */
    protected $elements = [];

    /**
     * the user parameters of the class content.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Returns the namespace of the class content.
     *
     * @return string
     */
    protected function getNamespace()
    {
        return trim($this->namespace, NAMESPACE_SEPARATOR);
    }

    /**
     * Returns the documentation block for the content class.
     *
     * @return string
     */
    protected function getDocBlock()
    {
        $docBloc = isset($this->properties['name'])
            ? sprintf(' * Generated class for content %s.', $this->properties['name'])
            : '';
        $docBloc .= isset($this->properties['description'])
            ? PHP_EOL . sprintf(' * %s', $this->properties['description']) . PHP_EOL
            : '';

        foreach ($this->elements as $key => $element) {
            if (!isset($element['options'])) {
                continue;
            }

            $docBloc .= PHP_EOL . sprintf(
                ' * @property %s $%s %s',
                isset($element['type']) ? $element['type'] : 'type',
                $key,
                isset($element['options']['label']) ? $element['options']['label'] : ''
            );
        }

        return $docBloc;
    }

    /**
     * Returns the doctrine repository for the content entity.
     *
     * @return string
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns the class name of the content (without namespace).
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function getClassname()
    {
        return $this->classname;
    }

    /**
     * Returns the extends class of the content.
     *
     * @return string
     */
    protected function getExtends()
    {
        return $this->extends;
    }

    /**
     * Returns an array of interface implemented by the class content.
     *
     * @return string[]
     *
     * @codeCoverageIgnore
     */
    protected function getInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * Returns a formatted call to trait.
     *
     * @param string $trait
     */
    protected function formatTrait(&$trait)
    {
        $trait = sprintf('Use %s;', $trait);
    }

    /**
     * Returns an array of formatted calls to traits in the class content.
     *
     * @return string[]
     */
    protected function getTraits()
    {
        $traits = $this->traits;
        array_walk($traits, [$this, 'formatTrait']);

        return $traits;
    }

    /**
     * Returns a formatted definition of a property.
     *
     * @param string $value
     * @param string $name
     */
    protected function formatProperty(&$value, $name)
    {
        $value = sprintf(
            '$this->defineProperty("%s", %s);',
            $name,
            var_export($value, true)
        );
    }

    /**
     * Returns an array of formatted definitions of the properties in the class content.
     *
     * @return string[]
     */
    protected function getProperties()
    {
        $properties = $this->properties;
        array_walk($properties, [$this, 'formatProperty']);

        return $properties;
    }

    /**
     * Returns a formatted definition of an element.
     *
     * @param string $value
     * @param string $name
     */
    protected function formatElement(&$value, $name)
    {
        $type = 'scalar';
        $options = [];

        if (is_array($value)) {
            $type = 'array';
            $options['default'] = $value;
            if (isset($value['type'])) {
                $type = $value['type'];
                $options = $value;
                unset($options['type']);
            }
        } elseif ('!!' === substr($value, 0, 2)) {
            $value = explode(' ', trim($value, ' !'), 2);
            $type = $value[0];
            $options['default'] = isset($value[1]) ? $value[1] : '';
        }

        $value = sprintf(
            '$this->defineData("%s", "%s", %s);',
            $name,
            $type,
            var_export($options, true)
        );
    }

    /**
     * Returns an array formatted definitions of the elements in the class content.
     *
     * @return string[]
     */
    protected function getElements()
    {
        $elements = $this->elements;
        array_walk($elements, [$this, 'formatElement']);

        return $elements;
    }

    /**
     * Returns a formatted definition of a parameter.
     *
     * @param string $value
     * @param string $name
     */
    protected function formatParameter(&$value, $name)
    {
        $value = sprintf(
            '$this->defineParam("%s", %s);',
            $name,
            var_export($value, true)
        );
    }

    /**
     * Returns an array of formatted definitions of the parameters in the class content.
     *
     * @return string
     */
    protected function getParameters()
    {
        $parameters = $this->parameters;
        array_walk($parameters, [$this, 'formatParameter']);

        return $parameters;
    }

    /**
     * Sets the extends value.
     *
     * @param string $extends
     */
    protected function setExtends($extends)
    {
        if (!empty($extends)) {
            $this->extends = $extends;
            if (NAMESPACE_SEPARATOR !== substr($this->extends, 0, 1)) {
                $this->extends = $this->getNamespace() . NAMESPACE_SEPARATOR . $this->extends;
            }
        }
    }

    /**
     * Sets an array of interface implementations.
     *
     * @param  string|array $interfaces
     *
     * @throws ParseException if interface cannot be resolved.
     */
    protected function setInterface($interfaces)
    {
        foreach ((array) $interfaces as $interface) {
            $this->addFirstNamespaceSlash($interface);
            if (!interface_exists($interface)) {
                throw new ParseException(sprintf('Unknown interface %s.', $interface));
            }

            $this->interfaces[] = $interface;
        }
    }

    /**
     * Sets the doctrine entity repository.
     *
     * @param  string $repository
     * @throws ParseException if $repository if not a valid EntityRepository
     */
    protected function setRepository($repository)
    {
        $this->addFirstNamespaceSlash($repository);
        if (!class_exists($repository)) {
            throw new ParseException(sprintf('Unknown repository %s.', $repository));
        }

        if (!is_a($repository, EntityRepository::class, true)) {
            throw new ParseException(sprintf('%s must extends %s class.', $repository, EntityRepository::class));
        }

        $this->repository = $repository;
    }

    /**
     * Sets an array of traits to be used.
     *
     * @param  string|array $traits
     *
     * @throws ParseException if trait cannot be resolved.
     */
    protected function setTraits($traits)
    {
        foreach ((array) $traits as $trait) {
            $this->addFirstNamespaceSlash($trait);
            if (!trait_exists($trait)) {
                throw new ParseException(sprintf('Unknown trait %s.', $trait));
            }

            $this->traits[] = $trait;
        }
    }

    /**
     * Sets the set of properties.
     *
     * @param string|array $properties
     */
    protected function setProperties($properties)
    {
        foreach ((array) $properties as $var => $value) {
            $this->properties[strtolower($var)] = $value;
        }
    }

    /**
     * Sets the set of elements.
     *
     * @param string|array $elements
     */
    protected function setElements($elements)
    {
        foreach ((array) $elements as $var => $value) {
            $this->elements[strtolower($var)] = $value;
        }
    }

    /**
     * Sets the set of parameters.
     *
     * @param string|array $parameters
     */
    protected function setParameters($parameters)
    {
        foreach ((array) $parameters as $var => $value) {
            $this->parameters[strtolower($var)] = $value;
        }
    }

    /**
     * Build the php code corresponding to the loading class.
     *
     * @return string The generated php code
     */
    protected function buildClass()
    {
        $search = [
            '<namespace>',
            '<docblock>',
            '<repository>',
            '<classname>',
            '<extends>',
            '<interfaces>',
            '<traits>',
            '<properties>',
            '<elements>',
            '<parameters>',
        ];

        $replace = [
            $this->getNamespace(),
            $this->getDocBlock(),
            $this->getRepository(),
            $this->getClassname(),
            $this->getExtends(),
            count($this->getInterfaces()) ? 'implements ' . implode(', ', $this->getInterfaces()) : '',
            implode(PHP_EOL, $this->getTraits()),
            implode(PHP_EOL, $this->getProperties()),
            implode(PHP_EOL, $this->getElements()),
            implode(PHP_EOL, $this->getParameters()),
        ];

        return str_replace($search, $replace, self::TEMPLATE);
    }

    /**
     * This method is called in response to fclose().
     */
    public function stream_close()
    {
        $this->namespace = AbstractClassContent::CLASSCONTENT_BASE_NAMESPACE;
        $this->repository = ClassContentRepository::class;
        $this->classname = '';
        $this->extends = AbstractClassContent::class;
        $this->context = $this->options = $this->data = $this->position = null;
        $this->interfaces = $this->traits = $this->properties = $this->elements = $this->parameters = [];
    }

    /**
     * This method is called in response to feof().
     *
     * @return bool TRUE if the read/write position is at the end of the stream and
     *              if no more data is available to be read, or FALSE otherwise.
     */
    public function stream_eof()
    {
        return $this->position >= strlen($this->data);
    }

    /**
     * This method is called in response to fread() and fgets().
     *
     * @param  int $count How many bytes of data from the current position should be returned.
     *
     * @return string     If there are less than count bytes available, return as many as are available.
     *                    If no more data is available, return either FALSE or an empty string.
     */
    public function stream_read($count)
    {
        $read = substr($this->data, $this->position, $count);
        $this->position += strlen($read);

        return $read;
    }

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
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        $length = strlen($this->data);
        switch ($whence) {
            case SEEK_SET:
                $newPosition = $offset;
                break;
            case SEEK_CUR:
                $newPosition = $this->position + $offset;
                break;
            case SEEK_END:
                $newPosition = $length + $offset;
                break;
            default:
                return false;
        }

        if (true === $return = ($newPosition >=0 && $newPosition <= $length)) {
            $this->position = $newPosition;
        }

        return $return;
    }

    /**
     * This method is called in response to fseek() to determine the current position.
     *
     * @return int Return the current position of the stream.
     */
    public function stream_tell()
    {
        return $this->position;
    }

    /**
     * Returns the options for the current context.
     *
     * @return array
     */
    protected function getOptions()
    {
        if (null === $this->options) {
            $options = stream_context_get_options(stream_context_get_default());

            if (null !== $this->context) {
                $options = array_merge(
                    $options,
                    stream_context_get_options($this->context)
                );
            }

            $this->options = (array) $this->defaultOptions;
            if (isset($options[self::PROTOCOL])) {
                $this->options = array_merge(
                    $this->options,
                    $options[self::PROTOCOL]
                );
            }
        }

        return $this->options;
    }

    /**
     * Returns the option value if found, null otherwise.
     *
     * @param  string $name
     *
     * @return mixed
     */
    protected function getOption($name)
    {
        $options = $this->getOptions();
        if (isset($options[$name])) {
            return $options[$name];
        }

        return null;
    }

    /**
     * Adds the first namespace slash if need.
     *
     * @param string $classname
     */
    protected function addFirstNamespaceSlash(&$classname)
    {
        if (NAMESPACE_SEPARATOR !== substr($classname, 0, 1)) {
            $classname = NAMESPACE_SEPARATOR . $classname;
        }
    }
}
