<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
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
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Rest\Controller;

use BackBee\NestedNode\Media;
use BackBee\NestedNode\MediaFolder;
use BackBee\ClassContent\AbstractContent;
use BackBee\ClassContent\Exception\InvalidContentTypeException;
use BackBee\Rest\Controller\Annotations as Rest;

use Doctrine\ORM\Tools\Pagination\Paginator;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Description of MediaController
 *
 * @author h.baptiste <harris.baptiste@lp-digital.fr>
 */
class MediaController extends AbstractRestController
{
    /**
     * Creates an instance of MediaController.
     *
     * @param ContainerInterface $app
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        if ($this->getApplication()) {
            $mediaClasses = $this->getApplication()->getAutoloader()->glob('Media'.DIRECTORY_SEPARATOR.'*');
            foreach ($mediaClasses as $mediaClass) {
                class_exists($mediaClass);
            }
        }
    }

    /**
     * @param Request $request
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction(Request $request, $start, $count)
    {
        $mediafolder = null;
        $folderUid = $request->get('mediaFolder_uid', null);
        if (null === $folderUid) {
            $mediafolder = $this->getMediaFolderRepository()->getRoot();
        } else {
            $mediafolder = $this->getMediaFolderRepository()->find($folderUid);
        }

        if (null === $mediafolder) {
            throw new NotFoundHttpException('Cannot find a media folder');
        }

        $paginator = null;
        if ($request->query->has('content_uid')) {
            $paginator = $this->getCollectionByContent($request->query->get('content_uid'), $mediafolder);
        } else {
            $paginator = $this->getClassicCollection($request, $mediafolder);
        }

        $iterator = $paginator->getIterator();
        $results = [];
        while ($iterator->valid()) {
          $results [] = $iterator->current();
          $iterator->next();
        }

        $pager = $request->query->has('usePagination') ? $paginator : null;

        return $this->addRangeToContent(
            $this->createJsonResponse($this->mediaToJson($results)),
            $pager,
            $start,
            count($results)
        );
    }

    /**
     * @param  mixed $id
     * @return Response
     * @throws BadRequestHttpException
     */
    public function deleteAction($id)
    {
        if (null === $media = $this->getMediaRepository()->find($id)) {
            throw new NotFoundHttpException(sprintf('Cannot find media with id `%s`.', $id));
        }

        $em = $this->getEntityManager();

        try {
            $em->getRepository('BackBee\ClassContent\AbstractClassContent')->deleteContent($media->getContent(), true);
            $em->remove($media);
            $em->flush();
        } catch (\Exception $e) {
            throw new BadRequestHttpException(sprintf('Error while deleting media `%s`: %s', $id, $e->getMessage()));
        }

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Update media content's and folder
     */
    public function putAction($id, Request $request)
    {
        $mediaTitle = $request->get('title', 'Untitled media');
        $media = $this->getMediaRepository()->find($id);

        if (null === $media) {
            throw new BadRequestHttpException(sprintf('Cannot find media with id `%s`.', $id));
        }

        $media->setTitle($mediaTitle);

        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * @param  Request $request
     * @return Response
     * @throws BadRequestHttpException
     */
    public function postAction(Request $request)
    {
        $contentUid = $request->request->get('content_uid');
        $contentType = $request->request->get('content_type', null);
        $mediaFolderUid = $request->request->get('folder_uid', null);
        $mediaTitle = $request->request->get('title', 'Untitled media');

        $content = $this->getClassContentManager()->findOneByTypeAndUid($contentType, $contentUid);

        if (null === $mediaFolderUid) {
            $mediaFolder = $this->getMediaFolderRepository()->getRoot();
        } else {
            $mediaFolder = $this->getMediaFolderRepository()->find($mediaFolderUid);
        }

        if (null === $mediaFolder) {
            throw new NotFoundHttpException('Cannot find a media folder');
        }

        $media = new Media();
        $media->setContent($content);
        $media->setTitle($mediaTitle);
        $media->setMediaFolder($mediaFolder);

        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 201, [
            'BB-RESOURCE-UID' => $media->getId(),
            'Location'        => $this->getApplication()->getRouting()->getUrlByRouteName(
                'bb.rest.media.get',
                [
                    'version' => $request->attributes->get('version'),
                    'uid'     => $media->getId(),
                ],
                '',
                false
            ),
        ]);
    }

    private function getMediaRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\Media');
    }

    private function getMediaFolderRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\MediaFolder');
    }

    private function getClassContentManager()
    {
        $manager = $this->getApplication()
            ->getContainer()
            ->get('classcontent.manager')
            ->setBBUserToken($this->getApplication()->getBBUserToken())
        ;

        return $manager;
    }

    private function mediaToJson($collection)
    {
        $result = [];
        foreach ($collection as $media) {
            $content = $media->getContent();

            if (null !== $draft = $this->getClassContentManager()->getDraft($content)) {
                $content->setDraft($draft);
            }

            // we also need to load content's elements draft
            foreach ($content->getData() as $element) {
                if (null !== $draft = $this->getClassContentManager()->getDraft($element)) {
                    $element->setDraft($draft);
                }
            }

            $mediaJson = $media->jsonSerialize();
            $contentJson = $this->getClassContentManager()->jsonEncode($media->getContent());
            $mediaJson['image'] = $contentJson['image'];
            $result[] = $mediaJson;
        }

        return $result;
    }

    private function addRangeToContent(Response $response, $collection, $offset, $limit)
    {
        $total = "*";
        if ($collection instanceof Paginator) {
            $total = count($collection);
        }

        $lastResult = $offset + $limit - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$offset-$lastResult/" . $total);

        return $response;
    }

    private function getClassicCollection(Request $request, $mediafolder)
    {
        $params = $request->query->all();
        $contentType =  $request->get('contentType', null);

        if (null !== $contentType) {
            try {
                $params['contentType'] = AbstractContent::getClassnameByContentType($contentType);
            } catch (InvalidContentTypeException $e) {
                throw new NotFoundHttpException(sprintf('Provided content type (:%s) is invalid.', $params['contentType']));
            }
        }

        return $this->getMediaRepository()->getMedias($mediafolder, $params, '_modified', 'desc', [
            'start' => $start,
            'limit' => $count,
        ]);
    }

    private function getCollectionByContent($contentUid, MediaFolder $mediafolder)
    {
        $content = $this->getEntityManager()->find('BackBee\ClassContent\AbstractClassContent', $contentUid);

        if (null === $content) {
            throw new NotFoundHttpException("No content find with uid '{$contentUid}'");
        }

        $query = $this->getMediaRepository()->createQueryBuilder('m')
            ->leftJoin('m._media_folder', 'mf')
            ->where('m._content = :content')
            ->andWhere('mf._root = :root')
            ->andWhere('mf._leftnode >= :leftnode')
            ->andWhere('mf._rightnode <= :rightnode')
            ->orderBy('m._modified', 'desc')
            ->setParameters([
                'content'   => $content,
                'root'      => $mediafolder->getRoot(),
                'leftnode'  => $mediafolder->getLeftnode(),
                'rightnode' => $mediafolder->getRightnode(),
            ])
            ->getQuery()
        ;

        return new Paginator($query, false);
    }
}
