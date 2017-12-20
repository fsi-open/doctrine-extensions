<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;

class ChainHandler extends AbstractHandler
{
    /**
     * @var FileHandlerInterface[]
     */
    protected $handlers = [];

    /**
     * @throws RuntimeException
     */
    public function __construct(array $handlers = [])
    {
        $i = 0;
        foreach ($handlers as $handler) {
            if (!$handler instanceof FileHandlerInterface) {
                throw new RuntimeException(sprintf(
                    'Handlers must be instances of "%s", "%s" given at position "%d"',
                    FileHandlerInterface::class,
                    is_object($handler) ? get_class($handler) : gettype($handler),
                    $i
                ));
            }

            $this->handlers[] = $handler;
            $i++;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent($file): string
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($file)) {
                return $handler->getContent($file);
            }
        }

        throw $this->generateNotSupportedException($file);
    }

    /**
     * {@inheritdoc}
     */
    public function getName($file): string
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($file)) {
                return $handler->getName($file);
            }
        }

        throw $this->generateNotSupportedException($file);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($file): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($file)) {
                return true;
            }
        }

        return false;
    }
}
