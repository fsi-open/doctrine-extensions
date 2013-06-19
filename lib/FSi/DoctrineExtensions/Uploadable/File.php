<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable;

use Gaufrette;

class File
{
    /**
     * @var Gaufrette\File
     */
    protected $file;

    /**
     * @var Gaufrette\Filesystem
     */
    protected $filesystem;

    public function __construct($key, $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->file = new Gaufrette\File($key, $filesystem);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->file->getKey();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->file->getName();
    }

    /**
     * @return Gaufrette\Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->file->getContent();
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return $this->file->delete();
    }
}
