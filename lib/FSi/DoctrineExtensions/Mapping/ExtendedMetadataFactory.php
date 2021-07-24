<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping;

use Doctrine\Bundle\DoctrineBundle\Mapping\MappingDriver as DoctrineBundleMappingDriver;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractFileDriver;
use FSi\DoctrineExtensions\Mapping\Driver\DriverChain;
use FSi\DoctrineExtensions\Mapping\Driver\DriverInterface;
use FSi\DoctrineExtensions\Mapping\Exception\RuntimeException;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use ReflectionClass;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use function class_exists;

final class ExtendedMetadataFactory
{
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

    /**
     * @param EntityManagerInterface $objectManager
     * @param string $extensionNamespace
     * @param Reader $annotationReader
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
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
        if ($cache) {
            $this->cache = $cache;
            $this->cachePrefix = $extensionNamespace;
        }

        // TODO this really should be done differently
        $metadataClassName = ltrim(
            sprintf('%s\Mapping\ClassMetadata', $this->extensionNamespace),
            '\\'
        );
        if (!class_exists($metadataClassName)) {
            throw new RuntimeException(
                sprintf('Metadata class "%s" does not exist!', class_exists($metadataClassName))
            );
        }

        $metadataClassReflection = new ReflectionClass($metadataClassName);
        if (!$metadataClassReflection->implementsInterface(ClassMetadataInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Metadata class must implement %s',
                ClassMetadataInterface::class
            ));
        }

        $this->metadataClassName = $metadataClassName;
    }

    public function getClassMetadata(string $class): ClassMetadataInterface
    {
        $class = ltrim($class, '\\');
        $metadataIndex = $this->getCacheId($class);

        if (isset($this->loadedMetadata[$metadataIndex])) {
            return $this->loadedMetadata[$metadataIndex];
        }

        if ($this->cache) {
            $metadata = $metadata = $this->cache->fetch($metadataIndex);
            if (false !== $metadata) {
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

        if ($this->cache) {
            $this->cache->save($metadataIndex, $metadata);
        }
        $this->loadedMetadata[$metadataIndex] = $metadata;

        return $metadata;
    }

    private function getCacheId(string $class): string
    {
        return $this->cachePrefix . $this->metadataClassName . $class;
    }

    /**
     * @param MappingDriver $omDriver
     * @return DriverInterface
     * @throws RuntimeException
     */
    private function getDriver(MappingDriver $omDriver): DriverInterface
    {
        $className = get_class($omDriver);
        $driverName = substr($className, strrpos($className, '\\') + 1);
        if (class_exists(DoctrineBundleMappingDriver::class) && $omDriver instanceof DoctrineBundleMappingDriver) {
            $omDriver = $omDriver->getDriver();
        }
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
                    "Driver of class %s does not implement required %s",
                    get_class($driver),
                    DriverInterface::class
                ));
            }
            if ($driver instanceof AbstractFileDriver && $omDriver instanceof FileDriver) {
                $driver->setFileLocator($omDriver->getLocator());
            }
            if ($driver instanceof AbstractAnnotationDriver) {
                $driver->setAnnotationReader($this->annotationReader);
            }
        }

        return $driver;
    }
}
