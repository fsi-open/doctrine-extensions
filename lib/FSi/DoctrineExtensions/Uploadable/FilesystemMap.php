<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable;

class FilesystemMap extends \Gaufrette\FilesystemMap
{
    /**
     * Seek for filesystem.
     *
     * @param mixed $wanted
     * @return string|bool
     */
    public function seek($wanted)
    {
        foreach ($this->all() as $domain => $filesystem) {
            if ($wanted === $filesystem) {
                return $domain;
            }
        }
        return false;
    }
}
