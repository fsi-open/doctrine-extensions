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
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FSi\DoctrineExtensions\Mapping\Driver\DriverInterface;
use FSi\DoctrineExtensions\Mapping\Exception\RuntimeException;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

abstract class AbstractFileDriver implements DriverInterface
{
    /**
     * @var ClassMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * @var FileLocator
     */
    private $locator;

    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory): void
    {
        $this->baseMetadataFactory = $metadataFactory;
    }

    /**
     * @return ClassMetadataFactory
     * @throws RuntimeException
     */
    public function getBaseMetadataFactory(): ClassMetadataFactory
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new RuntimeException('Required base metadata factory has not been set on the file driver.');
        }

        return $this->baseMetadataFactory;
    }

    public function loadClassMetadata(ClassMetadataInterface $metadata): void
    {
        if ($this->getBaseMetadataFactory()->isTransient($metadata->getClassName())) {
            return;
        }

        $this->loadExtendedClassMetadata($this->getBaseMetadataFactory()->getMetadataFor($metadata->getClassName()), $metadata);
    }

    public function setFileLocator(FileLocator $locator): void
    {
        $this->locator = $locator;
    }

    /**
     * @return FileLocator
     * @throws RuntimeException
     */
    public function getFileLocator(): FileLocator
    {
        if (!isset($this->locator)) {
            throw new RuntimeException('Required file locator has not been set on the file driver.');
        }

        return $this->locator;
    }

    /**
     * Load extended class metadata based on class metadata coming from underlying
     * ORM or ODM and this driver abilities to read extended metadata.
     *
     * @param ClassMetadataInfo $baseClassMetadata
     * @param ClassMetadataInterface $extendedClassMetadata
     */
    abstract protected function loadExtendedClassMetadata(
        ClassMetadataInfo $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    );

    protected function findMappingFile(ClassMetadataInterface $metadata): string
    {
        return $this->getFileLocator()->findMappingFile($metadata->getClassName());
    }
}
