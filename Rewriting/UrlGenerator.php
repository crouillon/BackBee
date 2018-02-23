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

namespace BackBee\Rewriting;

use Symfony\Component\DependencyInjection\ContainerInterface;

use BackBee\BBApplication;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\AbstractContent;
use BackBee\NestedNode\Page;
use BackBee\Rewriting\Exception\RewritingException;
use BackBee\Utils\StringUtils;

/**
 * Utility class to generate page URL according config rules.
 *
 * Available options are:
 *    * preserve-online  : if true, avoid the URL updating for online page
 *    * preserve-unicity : if true check for unique computed URL (-%d will be add as discriminator)
 *
 * Available rules are:
 *    * _root_      : scheme for root node
 *    * _default_   : default scheme
 *    * _content_   : array of schemes indexed by content classname
 *
 * Available params are:
 *    * $parent     : the page parent url
 *    * $uid        : the page uid
 *    * $title      : the urlized form of the title
 *    * $date       : the creation date formated to YYYYMMDD
 *    * $datetime   : the creation date formated to YYYYMMDDHHII
 *    * $time       : the creation date formated to HHIISS
 *    * $content->x : the urlized form of the 'x' property of content
 *    * $ancestor[x]: the url of the ancestor at level x
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class UrlGenerator implements UrlGeneratorInterface
{

    /**
     * An dependency container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * if true, forbid the URL updating for online page.
     *
     * @var boolean
     */
    private $preserveOnline = true;

    /**
     * if true, check for unique computed URL.
     *
     * @var boolean
     */
    private $preserveUnicity = true;

    /**
     * Available rewriting schemes.
     *
     * @var array
     */
    private $schemes = [];

    /**
     * Array of class content used by one of the schemes.
     *
     * @var array
     */
    private $descriminators;

    /**
     * Stores uids of the pages already computed.
     *
     * @var string[]
     */
    private $alreadyDone = [];

    /**
     * Class constructor.
     *
     * @param BBApplication|null $application
     */
    public function __construct(BBApplication $application = null)
    {
        if (null !== $application) {
            @trigger_error('The '.__CLASS__.'(BBApplication) definition is deprecated since version 1.4, to be '
               . 'removed in 1.5. Use '.__CLASS__.'::setContainer(ContainerInterface) instead.', E_USER_DEPRECATED);

            $this->setContainer($application->getContainer());
        }
    }

    /**
     * Returns a service if exists.
     *
     * @param  string $serviceId The service id to be returned.
     *
     * @return mixed|null        The service if found, null elsewhere.
     *
     * @throws \RuntimeException
     */
    protected function getService($serviceId)
    {
        if (null === $this->container) {
            throw new \RuntimeException('A container has to be set.');
        }

        if (!$this->container->has($serviceId)) {
            return null;
        }

        return $this->container->get($serviceId);
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        if (null !== $this->container
            && (null !== $config = $this->getService('config'))
            && (null !== $rewriting = $config->getRewritingConfig())
        ) {
            $this
                ->setSchemes(isset($rewriting['scheme']) ? $rewriting['scheme'] : [])
                ->setPreserveOnline(isset($rewriting['preserve-online']) && true === $rewriting['preserve-online'])
                ->setPreserveUnicity(isset($rewriting['preserve-unicity']) && true === $rewriting['preserve-unicity']);
        }
    }

    /**
     * Returns the current schemes.
     *
     * @return array
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * Sets the genrator schemes.
     *
     * @param  array $schemes
     *
     * @return UrlGenerator
     */
    public function setSchemes($schemes)
    {
        $this->schemes = (array) $schemes;

        return $this;
    }

    /**
     * Returns true if UrlGenerator is configured to preserve url for pages with online state, else false.
     *
     * @return boolean
     */
    public function isPreserveOnline()
    {
        return $this->preserveOnline;
    }

    /**
     * Setter for UrlGenerator's preserve online option.
     *
     * @param  boolean $preserveOnline
     *
     * @return UrlGenerator
     */
    public function setPreserveOnline($preserveOnline)
    {
        $this->preserveOnline = (boolean) $preserveOnline;

        return $this;
    }

    /**
     * Returns true if UrlGenerator is configured to preserve url for pages with online state, else false.
     *
     * @return boolean
     */
    public function isPreserveUnicity()
    {
        return $this->preserveUnicity;
    }

    /**
     * Setter for UrlGenerator's preserve online option.
     *
     * @param  boolean $preserveUnicity
     *
     * @return UrlGenerator
     */
    public function setPreserveUnicity($preserveUnicity)
    {
        $this->preserveUnicity = (boolean) $preserveUnicity;
        return $this;
    }

    /**
     * Returns the list of class content names used by one of schemes
     * Dynamically add a listener on descrimator.onflush event to RewritingListener.
     *
     * @return array
     */
    public function getDiscriminators()
    {
        if (null === $this->descriminators) {
            $this->descriminators = [];

            $keys = array_keys(isset($this->schemes['_content_']) ? (array) $this->schemes['_content_'] : []);
            foreach ($keys as $descriminator) {
                $this->descriminators[] = AbstractClassContent::CLASSCONTENT_BASE_NAMESPACE . $descriminator;
            }
        }

        return $this->descriminators;
    }

    /**
     * Generates and returns url for the provided page.
     *
     * @param Page                 $page    The page to generate its url
     * @param AbstractClassContent $content The optional main content of the page
     * @return string
     */
    public function generate(
        Page $page,
        AbstractClassContent $content = null,
        $force = false,
        $exceptionOnMissingScheme = true
    ) {
        $pageUrl =  $page->getUrl(false);

        if (null !== $content) {
            $shortClassname = str_replace(AbstractContent::CLASSCONTENT_BASE_NAMESPACE, '', get_class($content));
        }

        if (null !== $pageUrl
            && $page->getState() & Page::STATE_ONLINE
            && (true !== $force && $this->preserveOnline)
        ) {
            return $pageUrl;
        } elseif ($page->isRoot()
            && isset($this->schemes['_root_'])
        ) {
            return $this->doGenerate($this->schemes['_root_'], $page, $content);
        } elseif (isset($this->schemes['_layout_'])
            && is_array($this->schemes['_layout_'])
            && isset($this->schemes['_layout_'][$page->getLayout()->getUid()])
        ) {
            return $this->doGenerate($this->schemes['_layout_'][$page->getLayout()->getUid()], $page);
        } elseif (null !== $content
            && isset($this->schemes['_content_'])
            && is_array($this->schemes['_content_'])
            && isset($this->schemes['_content_'][$shortClassname])
        ) {
            return $this->doGenerate($this->schemes['_content_'][$shortClassname], $page, $content);
        } elseif (isset($this->schemes['_default_'])) {
            return $this->doGenerate($this->schemes['_default_'], $page, $content);
        } elseif (!empty($pageUrl)) {
            return $pageUrl;
        } elseif (true === $exceptionOnMissingScheme) {
            throw new RewritingException(
                sprintf('No rewriting scheme found for Page (#%s)', $page->getUid()),
                RewritingException::MISSING_SCHEME
            );
        }

        return '/'.$page->getUid();
    }

    /**
     * Checks for the uniqueness of the URL and postfixe it if need.
     *
     * @param Page   $page The page
     * @param string &$url The reference of the generated URL
     */
    public function getUniqueness(Page $page, $url)
    {
        if (!$this->preserveUnicity || null === $entityMng = $this->getService('em')) {
            return $url;
        }

        $pageRepository = $entityMng->getRepository(Page::class);
        if (null === $pageRepository->findOneBy([
            '_url' => $url,
            '_root' => $page->getRoot(),
            '_state' => $page->getUndeletedStates()])
        ) {
            return $url;
        }

        $patternSql = '/' === substr($url, -1)
            ? substr($url, 0, -1) . '-%/'
            : $url . '-%';

        $patternPcre = sprintf('#%s$#', str_replace('%', '([0-9]+)', $patternSql));
        $patternPrint = str_replace('%', '%d', $patternSql);

        $existings = $pageRepository->createQueryBuilder('p')
            ->andRootIs($page->getRoot())
            ->andIsNotDeleted()
            ->andWhere('p._uid != :uid')
            ->andWhere('p._url LIKE :url')
            ->setParameter('uid', $page->getUid())
            ->setParameter('url', $patternSql)
            ->getQuery()
            ->getResult();

        $max = 0;
        foreach ($existings as $existing) {
            $matches = [];
            if (preg_match($patternPcre, $existing->getUrl(false), $matches)) {
                $max = max([$max, $matches[1]]);
            }
        }

        return sprintf($patternPrint, $max + 1);
    }

    /**
     * Call on page entity flush. Generates a new URL according to the generator.
     *
     * @param  Page $page
     */
    public function onPageFlush(Page $page)
    {
        if (in_array($page->getUid(), $this->alreadyDone)) {
            return;
        }

        $url = $page->getUrl(false);

        if (null !== $entityMng = $this->getService('em')) {
            $unitOfWork = $entityMng->getUnitOfWork();
            $changeSet = $unitOfWork->getEntityChangeSet($page);

            if (isset($changeSet['_url']) && !empty($url)) {
                $url = $this->getUniqueness($page, $page->getUrl());
            } else {
                $force = isset($changeSet['_state']) && !($changeSet['_state'][0] & Page::STATE_ONLINE);
                $url = $this->generate($page, $this->getMaincontent($page), $force);
            }
        }

        if ($page->getUrl(false) !== $url) {
            $page->setUrl($url);

            $classMetadata = $entityMng->getClassMetadata(Page::class);
            if ($unitOfWork->isScheduledForInsert($page)
                || $unitOfWork->isScheduledForUpdate($page)
            ) {
                $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $page);
            } elseif (!$unitOfWork->isScheduledForDelete($page)) {
                $unitOfWork->computeChangeSet($classMetadata, $page);
            }

            $descendants = $entityMng->getRepository(Page::class)->getDescendants($page, 1);
            foreach ($descendants as $descendant) {
                $this->onPageFlush($descendant);
            }
        }

        $this->alreadyDone[] = $page->getUid();
    }

    /**
     * Looks for a main classcontent entity for $page.
     *
     * @param  Page $page
     *
     * @return AbstractClassContent|null
     */
    private function getMaincontent(Page $page)
    {
        $maincontent = null;
        $entityMng = $this->getService('em');
        $unitOfWork = $entityMng->getUnitOfWork();

        if ($unitOfWork->isScheduledForInsert($page)) {
            foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
                if ($entity instanceof AbstractClassContent && $entity->getMainNode() === $page) {
                    $maincontent = $entity;
                    break;
                }
            }
        } else {
            $maincontent = $entityMng
                ->getRepository(AbstractClassContent::class)
                ->getLastByMainnode($page, $this->getDiscriminators());
        }

        return $maincontent;
    }

    /**
     * Computes the URL of a page according to a scheme.
     *
     * @param array         $scheme  The scheme to apply
     * @param Page          $page    The page
     * @param  AbstractClassContent $content The optionnal main content of the page
     * @return string        The generated URL
     */
    private function doGenerate($scheme, Page $page, AbstractClassContent $content = null)
    {
        $replacement = [
            '$parent'   => $page->isRoot() ? '' : $page->getParent()->getUrl(false),
            '$title'    => StringUtils::urlize($page->getTitle()),
            '$datetime' => $page->getCreated()->format('YmdHis'),
            '$date'     => $page->getCreated()->format('Ymd'),
            '$time'     => $page->getCreated()->format('His'),
            '$uid'      => $page->getUid()
        ];

        $matches = [];
        if (preg_match_all('/(\$content->[a-z]+)/i', $scheme, $matches)) {
            foreach ($matches[1] as $pattern) {
                $property = explode('->', $pattern);
                $property = array_pop($property);

                try {
                    $replacement[$pattern] = StringUtils::urlize($content->$property);
                } catch (\Exception $e) {
                    $replacement[$pattern] = '';
                }
            }
        }

        $matches = [];
        $entityMng = $this->getService('em');
        if (null !== $entityMng && preg_match_all('/(\$ancestor\[([0-9]+)\])/i', $scheme, $matches)) {
            foreach ($matches[2] as $level) {
                $ancestor = $entityMng
                    ->getRepository('BackBee\NestedNode\Page')
                    ->getAncestor($page, $level);

                if (null !== $ancestor && $page->getLevel() > $level) {
                    $replacement['$ancestor['.$level.']'] = $ancestor->getUrl(false);
                } else {
                    $replacement['$ancestor['.$level.']'] = '';
                }
            }
        }

        $url = preg_replace('/\/+/', '/', str_replace(array_keys($replacement), array_values($replacement), $scheme));

        return $this->getUniqueness($page, $url);
    }
}
