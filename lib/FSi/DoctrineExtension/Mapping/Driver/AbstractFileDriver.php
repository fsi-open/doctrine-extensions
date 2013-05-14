<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtension\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\Component\Metadata\Driver\AbstractFileDriver as BaseFileDriver;
use FSi\DoctrineExtension\Mapping\Exception;

abstract class AbstractFileDriver extends BaseFileDriver implements DriverInterface
{
    /**
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * {@inheritDoc}
     */
    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory)
    {
        $this->baseMetadataFactory = $metadataFactory;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseMetadataFactory()
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new Exception\RuntimeException('Required base metadata factory has not been set on the file driver.');
        }
        return $this->baseMetadataFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $metadata)
    {
        $this->loadExtendedClassMetadata($this->getBaseMetadataFactory()->getMetadataFor($metadata->getClassName()), $metadata);
    }

    /**
     * Load extended class metadata based on class metadata coming from underlying
     * ORM or ODM and this driver abilities to read extended metadata.
     *
     * @param ClassMetadata $baseClassMetadata
     * @param ClassMetadataInterface $extendedClassMetadata
     */
    abstract protected function loadExtendedClassMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata);
}
