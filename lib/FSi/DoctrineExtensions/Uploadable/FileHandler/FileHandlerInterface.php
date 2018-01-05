<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

interface FileHandlerInterface
{
    /**
     * @param $file
     * @return bool
     */
    public function supports($file): bool;

    /**
     * Get name of resource, that will be a part of its key (for example base name of file path).
     *
     * @param mixed $file
     * @return string
     */
    public function getName($file): string;

    /**
     * @param mixed $file
     * @return string
     */
    public function getContent($file): string;
}
