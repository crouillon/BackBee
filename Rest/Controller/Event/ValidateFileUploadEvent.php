<?php

namespace BackBee\Rest\Controller\Event;

use BackBee\Event\Event;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class ValidateFileUploadEvent extends Event
{
    const EVENT_NAME = 'file_upload.validation';

    protected $filepath;

    /**
     * @param string $filepath
     */
    public function __construct($filepath)
    {
        $this->filepath = (string) $filepath;

        parent::__construct($this->filepath);
    }

    /**
     * Returns the path of the file to validate.
     *
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * Invalidates the provided file by throwing an exception.
     *
     * @param  string $message
     * @throws BadRequestHttpException
     */
    public function invalidateFile($message)
    {
        unlink($this->filepath);

        throw new BadRequestHttpException($message);
    }
}
