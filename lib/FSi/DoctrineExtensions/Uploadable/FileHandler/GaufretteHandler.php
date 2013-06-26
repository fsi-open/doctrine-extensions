<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use Gaufrette\Filesystem;
use Gaufrette\File;
use FSi\DoctrineExtensions\Uploadable\File as FSiFile;

class GaufretteHandler implements FileHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle($file, $key, Filesystem $filesystem)
    {
        if (!$file instanceof File) {
            return;
        }

        $newFile = new FSiFile($key, $filesystem);
        $newFile->setContent($file->getContent());
        return $newFile;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($file)
    {
        if (!$file instanceof File) {
            return;
        }

        return $file->getName();
    }
}
