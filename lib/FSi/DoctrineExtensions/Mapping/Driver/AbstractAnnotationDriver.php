<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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

    /**
     * {@inheritdoc}
     */
    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory)
    {
        $this->baseMetadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseMetadataFactory()
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new RuntimeException('Required base metadata factory has not been set on the annotation driver.');
        }

        return $this->baseMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $metadata)
    {
        if ($this->getBaseMetadataFactory()->isTransient($metadata->getClassName())) {
            return;
        }
        $this->loadExtendedClassMetadata($this->getBaseMetadataFactory()->getMetadataFor($metadata->getClassName()), $metadata);
    }

    /**
     * @param Reader $reader
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @throws RuntimeException
     * @return Reader
     */
    public function getAnnotationReader()
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
    abstract protected function loadExtendedClassMetadata(ClassMetadataInfo $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata);
}
