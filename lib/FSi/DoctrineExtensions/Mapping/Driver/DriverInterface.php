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
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

interface DriverInterface
{
    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory): void;

    public function getBaseMetadataFactory(): ClassMetadataFactory;

    public function loadClassMetadata(ClassMetadataInterface $metadata): void;
}
