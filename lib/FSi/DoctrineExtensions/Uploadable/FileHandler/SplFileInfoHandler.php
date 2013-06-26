<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use Gaufrette\Filesystem;
use FSi\DoctrineExtensions\Uploadable\File;
use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;

class SplFileInfoHandler implements FileHandlerInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function handle($file, $key, Filesystem $filesystem)
    {
        if (!$file instanceof \SplFileInfo) {
            return;
        }

        $level = error_reporting(0);
        $content = file_get_contents($file->getRealpath());
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            throw new RuntimeException($error['message']);
        }

        $file = new File($key, $filesystem);
        $file->setContent($content);

        return $file;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($file)
    {
        if (!$file instanceof \SplFileInfo) {
            return;
        }

        return basename($file->getRealpath());
    }
}
