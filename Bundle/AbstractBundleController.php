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

namespace BackBee\Bundle;

use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Translation\Translator;

use BackBee\BBApplication;
use BackBee\Controller\Controller;
use BackBee\Routing\RouteCollection;

/**
 * Abstract class for Bundle controller.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AbstractBundleController extends Controller
{

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RouteCollection
     */
    protected $routing;

    /**
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * Bundle controller constructor.
     *
     * @param BBApplication $app
     */
    public function __construct(BBApplication $app)
    {
        $this->logger = $app->getLogging();
        $this->routing = $app->getRouting();

        parent::__construct($app);
    }

    /**
     * Get the current bundle
     *
     * @param BundleInterface|null $bundle if bundle was not set, return null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set the current bundle
     *
     * @param BundleInterface $bundle
     */
    public function setBundle(BundleInterface $bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Magic method to call action methods.
     *
     * @param  string $method
     * @param  mixed  $arguments
     *
     * @return Response
     */
    public function __call($method, $arguments)
    {
        $method = $method.'Action';

        if (true !== $methodExist = $this->checkMethodExist($method)) {
            return $methodExist;
        }

        $result = $this->invockeAction($method, $arguments);

        return $this->decorateResponse($result, $method);
    }

    /**
     * Renders provided template with parameters and returns the generated string.
     *
     * @param  string     $template   the template relative path
     * @param  array|null $parameters
     *
     * @return string
     */
    public function render($template, array $parameters = null, Response $response = null)
    {
        $params = array_merge([
            'request'              => $this->getRequest(),
            'routing'              => $this->routing,
            'flash_bag'            => $this->application->getSession()->getFlashBag(),
        ], $parameters ?: []);

        return $this->application->getRenderer()->partial($template, $params);
    }

    /**
     * Decorate response to be sure to get Response Object
     *
     * @param  String|Response  $response
     * @param  String           $method   method called
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    protected function decorateResponse($response, $method)
    {
        if (is_string($response)) {
            $response = $this->createResponse($response);
        }

        if (!($response instanceof Response)) {
            throw new \InvalidArgumentException(sprintf(
                '%s must returns a string or an object instance of %s, %s given.',
                get_class($this).'::'.$method,
                Response::class,
                gettype($response)
            ));
        }

        return $response;
    }

    /**
     * Execute the controller method and return his response.
     *
     * @param  String $method    method to call
     * @param  Array  $arguments method arguments
     *
     * @return mixed
     */
    protected function invockeAction($method, $arguments)
    {
        try {
            $result = call_user_func_array([$this, $method], $arguments);
        } catch (\Exception $e) {
            $result = $this->createResponse(
                sprintf('%s::%s - %s:%s', get_class($this), $method, get_class($e), $e->getMessage()),
                500
            );
        }
        return $result;
    }

    /**
     * Check if the method exist.
     *
     * @param  String   $method     method name
     *
     * @return true|Response
     *
     * @throws \LogicException
     */
    protected function checkMethodExist($method)
    {
        if (!method_exists($this, $method)) {
            if ($this->application->isDebugMode()) {
                return $this->createResponse(
                    sprintf('Called undefined method: %s.', get_class($this).'::'.$method),
                    500
                );
            } else {
                throw new \LogicException(sprintf('Called undefined method: %s.', get_class($this).'::'.$method));
            }
        }

        return true;
    }

    /**
     * Creates a Response object and returns it.
     *
     * @param  string  $content    the response body content (must be string)
     * @param  integer $statusCode the response status code (default: 200)
     *
     * @return Response
     */
    protected function createResponse($content, $statusCode = 200, $contentType = 'text/html')
    {
        return new Response($content, $statusCode, ['Content-Type' => $contentType]);
    }

    /**
     * Creates and returns an instance of RedirectResponse with provided url.
     *
     * @param  string $url The url to redirect the user to
     * @param  int $statusCode The HTTP status code to return
     *
     * @return RedirectResponse
     */
    protected function redirect($url, $statusCode = 302)
    {
        return new RedirectResponse($url, $statusCode);
    }

    /**
     * Returns the current session flash bag.
     *
     * @return FlashBag
     */
    protected function getFlashBag()
    {
        return $this->application->getSession()->getFlashBag();
    }

    /**
     * Adds a success message to the session flashbag.
     *
     * @param string $message
     *
     * @return AbstractBundleController
     */
    protected function addFlashSuccess($message)
    {
        $this->application->getSession()->getFlashBag()->add('success', $message);

        return $this;
    }

    /**
     * Adds a error message to the session flashbag.
     *
     * @param string $message
     *
     * @return AbstractBundleController
     */
    protected function addFlashError($message)
    {
        $this->application->getSession()->getFlashBag()->add('error', $message);

        return $this;
    }

    /**
     * Returns translator.
     *
     * @return Translator
     *
     * @codeCoverageIgnore
     */
    protected function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }

    /**
     * Returns an entity repository
     *
     * @return EntityRepository
     */
    public function getRepository($entity)
    {
        return $this->getEntityManager()->getRepository($entity);
    }

    /**
     * Tries to get entity with provided identifier and throws exception if not found.
     *
     * Note that you should not provide namespace prefix (=BackBee\Bundle\AdminBundle\Entity).
     *
     * @param  string $entityName The entity namespace.
     * @param  string $id         The identifier to find.
     *
     * @return object
     *
     * @throws \InvalidArgumentException if cannot find entity with provided identifier.
     */
    protected function throwsExceptionIfEntityNotFound($entityName, $id)
    {
        if (null === $entity = $this->getRepository($entityName)->find($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot find `%s` with id `%s`.', $entityName, $id));
        }

        return $entity;
    }
}
