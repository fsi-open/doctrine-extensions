<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable;

use Gaufrette;

class FileFactory
{
    /**
     * Get instance of File that is loaded from other instance.
     *
     * @param File $file
     * @param string $newKey
     * @param Gaufrette\Filesystem $filesystem
     * @return File
     */
    public static function fetchFrom(File $file, $newKey, Gaufrette\Filesystem $filesystem)
    {
        $tmpFile = new Gaufrette\File($newKey, $filesystem);
        $tmpFile->setContent($file->getContent());
        return new File($newKey, $filesystem);
    }

    /**
     * Get instance of File loaded from local file.
     *
     * @param \SplFileInfo $file
     * @param $newKey
     * @param \Gaufrette\Filesystem $filesystem
     * @return File
     * @throws Exception\RuntimeException
     */
    public static function fromLocalFile(\SplFileInfo $file, $newKey, Gaufrette\Filesystem $filesystem)
    {
        $level = error_reporting(0);
        $content = file_get_contents($file->getRealpath());
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            throw new Exception\RuntimeException($error['message']);
        }

        $tmpFile = new Gaufrette\File($newKey, $filesystem);
        $tmpFile->setContent($content);
        return new File($newKey, $filesystem);
    }
}
