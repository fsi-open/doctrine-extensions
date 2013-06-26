<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use Gaufrette\Filesystem;

class ChainHandler implements FileHandlerInterface
{
    /**
     * @var array
     */
    protected $handlers;

    public function __construct(array $handlers = array())
    {
        $i = 0;
        foreach ($handlers as $handler) {
            if (!$handler instanceof FileHandlerInterface) {
                throw new RuntimeException(sprintf(
                    'Handlers must be instances of FSi\\DoctrineExtensions\\Uploadable\\FileHandler\\FileHandlerInterface, "%s" given at position "%d"',
                    is_object($handler) ? get_class($handler) : gettype($handler),
                    $i
                ));
            }

            $this->handlers[] = $handler;

            $i++;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handle($file, $key, Filesystem $filesystem)
    {
        foreach ($this->handlers as $handler) {
            if ($result = $handler->handle($file, $key, $filesystem)) {
                return $result;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName($file)
    {
        foreach ($this->handlers as $handler) {
            if ($result = $handler->getName($file)) {
                return $result;
            }
        }
    }
}
