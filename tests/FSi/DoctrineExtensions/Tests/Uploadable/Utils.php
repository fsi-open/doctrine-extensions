<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use DirectoryIterator;

class Utils
{
    public static function deleteRecursive(string $path): void
    {
        foreach (new DirectoryIterator($path) as $file) {
            if ($file->isDot()) {
                continue;
            }

            $filename = $path . DIRECTORY_SEPARATOR . $file->getFilename();

            if ($file->isDir()) {
                self::deleteRecursive($filename);
                rmdir($filename);
            } else {
                unlink($filename);
            }
        }
    }
}
