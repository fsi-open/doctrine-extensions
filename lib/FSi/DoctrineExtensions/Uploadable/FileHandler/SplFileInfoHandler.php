<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use SplFileInfo;
use SplFileObject;

class SplFileInfoHandler extends AbstractHandler
{
    /**
     * @var string
     */
    private $tempFilename;

    public function __construct(string $tempFilename = 'temp')
    {
        $this->tempFilename = $tempFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent($file): string
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        $level = error_reporting(0);
        $filePosition = null;
        if ($file instanceof SplFileObject) {
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
    public function getName($file): string
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        if ($file->getRealpath()) {
            $filename = basename($file->getRealpath());

            if (!empty($filename)) {
                return $filename;
            }
        }

        return $this->tempFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($file): bool
    {
        return $file instanceof SplFileInfo;
    }

    private function throwException(string $defaultMessage = null): void
    {
        $error = error_get_last();
        throw new RuntimeException(($error !== null) ? $error['message'] : $defaultMessage);
    }

    private function tryOpenFile(SplFileInfo $file): SplFileObject
    {
        try {
            $fileObject = $file->openFile();
        } catch (\RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $fileObject;
    }

    /**
     * @param SplFileObject $fileObject
     * @param int $position
     * @param int $whence
     * @return int
     */
    private function trySeekFile(SplFileObject $fileObject, $position, $whence = SEEK_SET): void
    {
        $seekResult = $fileObject->fseek($position, $whence);

        if ($seekResult === -1) {
            $this->throwException(sprintf(
                'Unable to set position on file "%s"',
                $fileObject->getPathname()
            ));
        }
    }

    private function tryTellFile($fileObject): int
    {
        $fileSize = $fileObject->ftell();

        if ($fileSize === false) {
            $this->throwException(sprintf(
                'Unable to get position of file "%s"',
                $fileObject->getPathname()
            ));
        }

        return $fileSize;
    }

    private function tryReadFile(SplFileObject $fileObject, int $length): string
    {
        $content = $fileObject->fread($length);

        if (false === $content) {
            $this->throwException(sprintf(
                'Unable to read contents of file "%s"',
                $fileObject->getPathname()
            ));
        }

        return $content;
    }
}
