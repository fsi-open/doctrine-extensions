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

    /**
     * Accepts an array of DriverInterface instances indexed by class namespace
     *
     * @param \FSi\DoctrineExtensions\Metadata\Driver\DriverInterface[] $drivers
     */
    public function __construct(array $drivers = [])
    {
        foreach ($drivers as $namespace => $driver) {
            $this->addDriver($driver, $namespace);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory): void
    {
        foreach ($this->drivers as $drivers) {
            foreach ($drivers as $driver) {
                $driver->setBaseMetadataFactory($metadataFactory);
            }
        }

        $this->baseMetadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseMetadataFactory(): ClassMetadataFactory
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new RuntimeException('Required base metadata factory has not been set on this driver.');
        }

        return $this->baseMetadataFactory;
    }

    /**
     * @param \FSi\DoctrineExtensions\Metadata\Driver\DriverInterface $driver
     * @param string $namespace
     * @return \FSi\DoctrineExtensions\Metadata\Driver\DriverChain
     */
    public function addDriver(DriverInterface $driver, $namespace)
    {
        if (!isset($this->drivers[$namespace])) {
            $this->drivers[$namespace] = [];
        }
        $this->drivers[$namespace][] = $driver;

        return $this;
    }

    /**
     * @param \FSi\DoctrineExtensions\Mapping\Driver\ClassMetadataInterface $metadata
     */
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
