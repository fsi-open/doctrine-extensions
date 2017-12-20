<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use Gaufrette\File;

class GaufretteHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function getContent($file): string
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        return $file->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getName($file): string
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        return basename($file->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function supports($file): bool
    {
        return $file instanceof File;
    }
}
