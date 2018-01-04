<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable;

use Gaufrette\File as BaseFile;
use Gaufrette\Filesystem;

class File extends BaseFile
{
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }
}
