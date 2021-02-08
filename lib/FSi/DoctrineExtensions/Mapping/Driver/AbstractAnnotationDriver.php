<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use FSi\DoctrineExtensions\Mapping\Driver\DriverInterface;
use FSi\DoctrineExtensions\Mapping\Exception\RuntimeException;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

abstract class AbstractAnnotationDriver implements DriverInterface
{
    /**
     * @var ClassMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * @var Reader
     */
    private $reader;

    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory): void
    {
        $this->baseMetadataFactory = $metadataFactory;
    }

    public function getBaseMetadataFactory(): ClassMetadataFactory
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new RuntimeException('Required base metadata factory has not been set on the annotation driver.');
        }

        return $this->baseMetadataFactory;
    }

    public function loadClassMetadata(ClassMetadataInterface $metadata): void
    {
        if ($this->getBaseMetadataFactory()->isTransient($metadata->getClassName())) {
            return;
        }

        $this->loadExtendedClassMetadata(
            $this->getBaseMetadataFactory()->getMetadataFor($metadata->getClassName()),
            $metadata
        );
    }

    public function setAnnotationReader(Reader $reader): void
    {
        $this->reader = $reader;
    }

    /**
     * @throws RuntimeException
     */
    public function getAnnotationReader(): Reader
    {
        if (!isset($this->reader)) {
            throw new RuntimeException('Required annotation reader has not been set on the annotation driver.');
        }

        return $this->reader;
    }

    /**
     * Load extended class metadata based on class metadata coming from underlying
     * ORM and this driver abilities to read extended metadata.
     *
     * @param ClassMetadataInfo $baseClassMetadata
     * @param ClassMetadataInterface $extendedClassMetadata
     */
    abstract protected function loadExtendedClassMetadata(
        ClassMetadataInfo $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void;
}
