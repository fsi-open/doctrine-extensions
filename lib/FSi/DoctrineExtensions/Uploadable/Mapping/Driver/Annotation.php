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
use FSi\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Mapping\ClassMetadata;
use RuntimeException;

class Annotation extends AbstractAnnotationDriver
{
    public const UPLOADABLE = 'FSi\\DoctrineExtensions\\Uploadable\\Mapping\\Annotation\\Uploadable';

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

        $classReflection = $extendedClassMetadata->getClassReflection();
        foreach ($classReflection->getProperties() as $property) {
            if ($baseClassMetadata->isMappedSuperclass
                && !$property->isPrivate()
                || $baseClassMetadata->isInheritedField($property->name)
                || isset($baseClassMetadata->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }

            $uploadableAnnotation = $this->getAnnotationReader()->getPropertyAnnotation(
                $property,
                self::UPLOADABLE
            );
            if ($uploadableAnnotation) {
                $extendedClassMetadata->addUploadableProperty(
                    $property->getName(),
                    $uploadableAnnotation->targetField,
                    $uploadableAnnotation->filesystem,
                    $uploadableAnnotation->keymaker,
                    $uploadableAnnotation->keyLength,
                    $uploadableAnnotation->keyPattern
                );
            }
        }
    }
}
