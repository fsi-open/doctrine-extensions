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
        return new self($newKey, $filesystem);
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
        return new self($newKey, $filesystem);
    }
}
