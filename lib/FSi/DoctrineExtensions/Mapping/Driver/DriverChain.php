<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use FSi\DoctrineExtensions\Mapping\Exception\RuntimeException;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

class DriverChain implements DriverInterface
{
    /**
     * @var ClassMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * Array of nested metadata drivers to iterate over. It's indexed by class namespaces.
     *
     * @var array
     */
    private $drivers = [];

    public function __construct(array $drivers = [])
    {
        foreach ($drivers as $namespace => $driver) {
            $this->addDriver($driver, $namespace);
        }
    }

    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory): void
    {
        foreach ($this->drivers as $drivers) {
            foreach ($drivers as $driver) {
                $driver->setBaseMetadataFactory($metadataFactory);
            }
        }

        $this->baseMetadataFactory = $metadataFactory;
    }

    public function getBaseMetadataFactory(): ClassMetadataFactory
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new RuntimeException('Required base metadata factory has not been set on this driver.');
        }

        return $this->baseMetadataFactory;
    }

    public function addDriver(DriverInterface $driver, string $namespace): void
    {
        if (!isset($this->drivers[$namespace])) {
            $this->drivers[$namespace] = [];
        }

        $this->drivers[$namespace][] = $driver;
    }

    public function loadClassMetadata(ClassMetadataInterface $metadata): void
    {
        $className = $metadata->getClassName();
        foreach ($this->drivers as $namespace => $drivers) {
            if (strpos($className, $namespace) !== 0) {
                continue;
            }

            foreach ($drivers as $driver) {
                $driver->loadClassMetadata($metadata);
            }
        }
    }
}
