<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

interface DriverInterface
{
    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory $metadataFactory
     * @return null
     */
    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory);

    /**
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    public function getBaseMetadataFactory();

    /**
     * @param ClassMetadataInterface $metadata
     */
    public function loadClassMetadata(ClassMetadataInterface $metadata);
}
