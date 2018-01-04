<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractXmlDriver;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Mapping\ClassMetadata;
use RuntimeException;

class Xml extends AbstractXmlDriver
{
    protected function loadExtendedClassMetadata(
        ClassMetadataInfo $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void {
        if (!($extendedClassMetadata instanceof ClassMetadata)) {
            throw new RuntimeException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                ClassMetadata::class,
                get_class($extendedClassMetadata)
            ));
        }

        $mapping = $this->getFileMapping($extendedClassMetadata);
        if (isset($mapping->field)) {
            foreach ($mapping->field as $fieldMapping) {
                $fieldMappingDoctrine = $fieldMapping;
                $fieldMapping = $fieldMapping->children(self::FSI_NAMESPACE_URI);
                if (isset($fieldMapping->uploadable)) {
                    $data = $fieldMapping->uploadable;
                    $extendedClassMetadata->addUploadableProperty(
                        $this->getAttribute($fieldMappingDoctrine, 'name'),
                        $this->getAttribute($data, 'targetField'),
                        $this->getAttribute($data, 'filesystem'),
                        $this->getAttribute($data, 'keymaker'),
                        $this->getAttribute($data, 'keyLength'),
                        $this->getAttribute($data, 'keyPattern')
                    );
                }
            }
        }
    }
}
