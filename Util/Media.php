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

namespace BackBee\Util;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

use BackBee\ClassContent\Element\File as ElementFile;
use BackBee\Exception\BBException;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Utils\File\File;

/**
 * Set of utility methods to deal with media files.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Media
{

    /**
     * Returns the computed storage filename of an element file.
     *
     * @param  ElementFile $content
     * @param  int         $folderSize Optional, size in characters of the storing folder.
     *
     * @return string
     *
     * @throws InvalidArgumentException if the provided element file is empty.
     */
    public static function getPathFromContent(ElementFile $content, $folderSize = 3)
    {
        if (null === $content->getUid()
            || empty($content->originalname)
        ) {
            throw new InvalidArgumentException(
                'Enable to compute path, the provided element file is not yet initialized'
            );
        }

        $folder = '';
        $filename = $content->getUid();
        if (null !== $draft = $content->getDraft()) {
            $filename = $draft->getUid();
        }

        if (0 < $folderSize && strlen($filename) > $folderSize) {
            $folder = substr($filename, 0, $folderSize).'/';
            $filename = substr($filename, $folderSize);
        }

        $extension = File::getExtension($content->originalname, true);

        return $folder.$filename.$extension;
    }

    /**
     * Returns the computed storage filename base on an uid.
     *
     * @param  string  $uid
     * @param  string  $originalname
     * @param  int     $folderSize
     * @param  boolean $includeOriginalName
     *
     * @return string
     *
     * @throws InvalidArgumentException if the provided $uid is invalid.
     */
    public static function getPathFromUid($uid, $originalname, $folderSize = 3, $includeOriginalName = false)
    {
        if (!is_string($uid) || empty($uid)) {
            throw new InvalidArgumentException(
                'Enable to compute path, the provided uid is not a valid string'
            );
        }

        $folder = '';
        $filename = $uid;
        if (0 < $folderSize && strlen($uid) > $folderSize) {
            $folder = substr($uid, 0, $folderSize).DIRECTORY_SEPARATOR;
            $filename = substr($uid, $folderSize);
        }

        if (true === $includeOriginalName) {
            $filename .= DIRECTORY_SEPARATOR.$originalname;
        } else {
            $extension = File::getExtension($originalname, true);
            $filename .= $extension;
        }

        return $folder.$filename;
    }

    /**
     * Resizes an image and saves it to the provided file path.
     *
     * @param  string $source The filepath of the source image
     * @param  string $dest   The filepath of the target image
     * @param  int    $width
     * @param  int    $height
     *
     * @return boolean Returns TRUE on success, FALSE on failure.
     *
     * @throws BBException              if gd extension is not loaded.
     * @throws InvalidArgumentException on unsupported file type or unreadable file source.
     */
    public static function resize($source, $dest, $width, $height)
    {
        if (!extension_loaded('gd')) {
            throw new BBException('gd extension is required');
        }

        if (!is_readable($source)) {
            throw new InvalidArgumentException('Enable to read source file');
        }

        if (false === $size = getimagesize($source)) {
            throw new InvalidArgumentException('Unsupported picture type');
        }

        $sourceWidth = $size[0];
        $sourceHeight = $size[1];
        $mimeType = MimeTypeGuesser::getInstance()->guess($source);

        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImg = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImg = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $sourceImg = imagecreatefromgif($source);
                break;
            default:
                throw new InvalidArgumentException('Unsupported picture type');
        }

        if ($sourceWidth < $width && $sourceHeight < $height) {
            // Picture to small, no resize
            return @copy($source, $dest);
        }

        $ratio = min($width / $sourceWidth, $height / $sourceHeight);
        $width = $sourceWidth * $ratio;
        $height = $sourceHeight * $ratio;

        $targetImg = imagecreatetruecolor($width, $height);

        if ('image/jpeg' !== $mimeType) {
            // Preserve alpha
            imagecolortransparent($targetImg, imagecolorallocatealpha($targetImg, 0, 0, 0, 127));
            imagealphablending($targetImg, false);
            imagesavealpha($targetImg, true);
        }

        imagecopyresampled($targetImg, $sourceImg, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($targetImg, $dest);
                break;
            case 'image/png':
                return @imagepng($targetImg, $dest);
                break;
            case 'image/gif':
                return @imagegif($targetImg, $dest);
                break;
        }
    }
}
