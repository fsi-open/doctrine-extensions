<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;

class SplFileInfoHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function getContent($file)
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        $level = error_reporting(0);
        if ($file instanceof \SplFileObject) {
            $fileObject = $file;
            $filePosition = $this->tryTellFile($fileObject);
        } else {
            $fileObject = $this->tryOpenFile($file);
        }
        $this->trySeekFile($fileObject, 0, SEEK_END);
        $fileSize = $this->tryTellFile($fileObject);
        $this->trySeekFile($fileObject, 0);
        $content = $this->tryReadFile($fileObject, $fileSize);
        if (null !== $filePosition) {
            $this->trySeekFile($fileObject, $filePosition);
        }
        error_reporting($level);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($file)
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        return basename($file->getRealpath());
    }

    /**
     * {@inheritdoc}
     */
    public function supports($file)
    {
        return $file instanceof \SplFileInfo;
    }

    private function throwException($defaultMessage = null)
    {
        $error = error_get_last();
        throw new RuntimeException(($error !== null) ? $error['message'] : $defaultMessage);
    }

    /**
     * @param \SplFileInfo $file
     * @return \SplFileObject
     */
    private function tryOpenFile(\SplFileInfo $file)
    {
        try {
            $fileObject = $file->openFile();
        } catch (\RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $fileObject;
    }

    /**
     * @param \SplFileObject $fileObject
     * @param int $position
     * @param int $whence
     * @return int
     */
    private function trySeekFile(\SplFileObject $fileObject, $position, $whence = SEEK_SET)
    {
        $seekResult = $fileObject->fseek($position, $whence);

        if ($seekResult === -1) {
            $this->throwException(sprintf('Unable to set position on file "%s"', $fileObject->getPathname()));
        }
    }

    /**
     * @param \SplFileObject $fileObject
     * @return int
     */
    private function tryTellFile($fileObject)
    {
        $fileSize = $fileObject->ftell();

        if ($fileSize === false) {
            $this->throwException(sprintf('Unable to get position of file "%s"', $fileObject->getPathname()));
        }

        return $fileSize;
    }

    /**
     * @param \SplFileObject $fileObject
     * @param int $length
     * @return string
     */
    private function tryReadFile(\SplFileObject $fileObject, $length)
    {
        $content = $fileObject->fread($length);

        if (false === $content) {
            $this->throwException(sprintf('Unable to read contents of file "%s"', $fileObject->getPathname()));
        }

        return $content;
    }
}
