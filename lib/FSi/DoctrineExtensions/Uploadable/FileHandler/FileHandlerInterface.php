<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use Gaufrette\Filesystem;

interface FileHandlerInterface
{
    /**
     * Get name of resource, that will be a part of its key.
     *
     * This can be for example base name of file path.
     *
     * @param mixed $file
     * @return string
     */
    public function getName($file);

    /**
     * Method must return instance of FSi\DoctrineExtensions\Uploadable\File or null,
     * if can't handle given resource.
     *
     * @param mixed $file
     * @param string $key
     * @param \Gaufrette\Filesystem $filesystem
     * @return \FSi\DoctrineExtensions\Uploadable\File|null
     */
    public function handle($file, $key, Filesystem $filesystem);
}
