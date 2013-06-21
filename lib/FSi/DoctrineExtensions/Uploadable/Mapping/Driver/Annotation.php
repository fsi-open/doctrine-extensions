<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use FSi\Component\Reflection\ReflectionClass;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Exception\AnnotationException;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver;

class Annotation extends AbstractAnnotationDriver
{
    const UPLOADABLE = 'FSi\\DoctrineExtensions\\Uploadable\\Mapping\\Annotation\\Uploadable';

    /**
     * {@inheritDoc}
     */
    protected function loadExtendedClassMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        $classReflection = $extendedClassMetadata->getClassReflection();
        foreach ($classReflection->getProperties() as $property) {
            if ($baseClassMetadata->isMappedSuperclass && !$property->isPrivate() ||
                $baseClassMetadata->isInheritedField($property->name) ||
                isset($baseClassMetadata->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }

            if ($uploadableAnnotation = $this->getAnnotationReader()->getPropertyAnnotation($property, self::UPLOADABLE)) {
                if (!isset($uploadableAnnotation->targetField)) {
                    throw new AnnotationException(sprintf('Annotation \'Uploadable\' in property \'%s\' of class \'%s\' does not have required \'targetField\' attribute', $property, $baseClassMetadata->name));
                }

                if (empty($uploadableAnnotation->targetField)) {
                    throw new AnnotationException(sprintf('Annotation \'Uploadable\' in property \'%s\' of class \'%s\' has empty \'targetField\' attribute', $property, $baseClassMetadata->name));
                }



                $extendedClassMetadata->addUploadableProperty(
                    $property->getName(),
                    $uploadableAnnotation->targetField,
                    $uploadableAnnotation->domain,
                    $uploadableAnnotation->keymaker,
                    $uploadableAnnotation->keyLength
                );
            }
        }
    }
}
