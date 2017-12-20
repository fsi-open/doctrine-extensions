<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManagerInterface;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractFileDriver;
use FSi\DoctrineExtensions\Mapping\Driver\DriverChain;
use FSi\DoctrineExtensions\Mapping\Driver\DriverInterface;
use FSi\DoctrineExtensions\Mapping\Exception\RuntimeException;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use ReflectionClass;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

final class ExtendedMetadataFactory
{
    const METADATA_CLASS = 'FSi\DoctrineExtensions\Metadata\ClassMetadata';

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * @var string
     */
    private $metadataClassName;

    /**
     * @var array
     */
    private $loadedMetadata = [];

    /**
     * @var EntityManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $extensionNamespace;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(
        EntityManagerInterface $objectManager,
        string $extensionNamespace,
        Reader $annotationReader
    ) {
        $this->objectManager = $objectManager;
        $this->annotationReader = $annotationReader;
        $this->extensionNamespace = $extensionNamespace;
        $metadataDriver = $objectManager->getConfiguration()->getMetadataDriverImpl();
        if (is_null($metadataDriver)) {
            throw new RuntimeException('The entity manager did not return a metadata driver!');
        }
        $this->driver = $this->getDriver($metadataDriver);
        $this->driver->setBaseMetadataFactory($objectManager->getMetadataFactory());

        $cache = $this->objectManager->getMetadataFactory()->getCacheDriver();
        if (isset($cache)) {
            $this->cache = $cache;
            if (isset($extensionNamespace)) {
                $this->cachePrefix = $extensionNamespace;
            }
        }

        if (class_exists($this->extensionNamespace . '\Mapping\ClassMetadata')) {
            $metadataClassName = ltrim(
                sprintf('%s\Mapping\ClassMetadata', $this->extensionNamespace),
                '\\'
            );
            $metadataClassReflection = new ReflectionClass($metadataClassName);
            if (!$metadataClassReflection->implementsInterface('FSi\DoctrineExtensions\Metadata\ClassMetadataInterface')) {
                throw new InvalidArgumentException(
                    'Metadata class must implement FSi\DoctrineExtensions\Metadata\ClassMetadataInterface'
                );
            }
            $this->metadataClassName = $metadataClassName;
        } else {
            $this->metadataClassName = self::METADATA_CLASS;
        }
    }

    /**
     * Returns class metadata read by the driver. This method calls itself
     * recursively for each ancestor class.
     */
    public function getClassMetadata(string $class): ClassMetadataInterface
    {
        $class = ltrim($class, '\\');
        $metadataIndex = $this->getCacheId($class);

        if (isset($this->loadedMetadata[$metadataIndex])) {
            return $this->loadedMetadata[$metadataIndex];
        }

        if (isset($this->cache)) {
            if (false !== ($metadata = $this->cache->fetch($metadataIndex))) {
                return $metadata;
            }
        }

        $metadata = new $this->metadataClassName($class);

        $parentClasses = array_reverse(class_parents($class));
        foreach ($parentClasses as $parentClass) {
            $metadata->setClassName($parentClass);
            $this->driver->loadClassMetadata($metadata);
        }

        $metadata->setClassName($class);
        $this->driver->loadClassMetadata($metadata);

        if (isset($this->cache)) {
            $this->cache->save($metadataIndex, $metadata);
        }
        $this->loadedMetadata[$metadataIndex] = $metadata;

        return $metadata;
    }

    /**
     * Returns identifier used to store class metadata in cache
     */
    private function getCacheId(string $class): string
    {
        return $this->cachePrefix . $this->metadataClassName . $class;
    }

    /**
     * Get the extended driver instance which will read the metadata required by extension.
     *
     * @throws RuntimeException if driver was not found in extension or it is not compatible
     */
    private function getDriver(MappingDriver $omDriver): DriverInterface
    {
        $className = get_class($omDriver);
        $driverName = substr($className, strrpos($className, '\\') + 1);
        if ($omDriver instanceof MappingDriverChain) {
            $driver = new DriverChain();
            foreach ($omDriver->getDrivers() as $namespace => $nestedOmDriver) {
                $driver->addDriver($this->getDriver($nestedOmDriver), $namespace);
            }
        } else {
            $driverName = substr($driverName, 0, strpos($driverName, 'Driver'));
            // create driver instance
            $driverClassName = $this->extensionNamespace . '\Mapping\Driver\\' . $driverName;
            if (!class_exists($driverClassName)) {
                $driverClassName = $this->extensionNamespace . '\Mapping\Driver\Annotation';
                if (!class_exists($driverClassName)) {
                    throw new RuntimeException(
                        "Failed to fallback to annotation driver: ({$driverClassName}), extension driver was not found."
                    );
                }
            }
            $driver = new $driverClassName();
            if (!$driver instanceof DriverInterface) {
                throw new RuntimeException(sprintf(
                    "Driver of class %s does not implement required FSi\DoctrineExtensions\Mapping\Driver\DriverInterface",
                    get_class($driver)
                ));
            }
            if ($driver instanceof AbstractFileDriver && $omDriver instanceof FileDriver) {
                /** @var $driver FileDriver */
                $driver->setFileLocator($omDriver->getLocator());
            }
            if ($driver instanceof AbstractAnnotationDriver) {
                $driver->setAnnotationReader($this->annotationReader);
            }
        }

        return $driver;
    }
}
