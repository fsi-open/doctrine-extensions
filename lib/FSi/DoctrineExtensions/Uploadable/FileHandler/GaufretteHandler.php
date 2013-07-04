<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use Gaufrette\File;

class GaufretteHandler extends AbstractHandler
{
    /**
     * {@inheritDoc}
     *
     */
    public function getContent($file)
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        return $file->getContent();
    }

    /**
     * {@inheritDoc}
     */
    public function getName($file)
    {
        if (!$this->supports($file)) {
            throw $this->generateNotSupportedException($file);
        }

        return $file->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($file)
    {
        return $file instanceof File;
    }
}
