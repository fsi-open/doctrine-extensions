<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtension\Versionable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use FSi\Component\Reflection\ReflectionClass;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtension\Mapping\Driver\AbstractAnnotationDriver;
use FSi\DoctrineExtension\Translatable\Exception\AnnotationException;

class Annotation extends AbstractAnnotationDriver
{
    const VERSIONABLE = 'FSi\\DoctrineExtension\\Versionable\\Mapping\\Annotation\\Versionable';
    const VERSION     = 'FSi\\DoctrineExtension\\Versionable\\Mapping\\Annotation\\Version';
    const STATUS      = 'FSi\\DoctrineExtension\\Versionable\\Mapping\\Annotation\\Status';

    /**
     * {@inheritDoc}
     */
    public function loadExtendedClassMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        $classReflection = $extendedClassMetadata->getClassReflection();
        if ($versionableAnnotation = $this->getAnnotationReader()->getClassAnnotation($classReflection, self::VERSIONABLE)) {
            if (!isset($versionableAnnotation->mappedBy)) {
                throw new AnnotationException(
                    'Annotation \'Versionable\' of class \''.$baseClassMetadata->name.'\' does not have required \'mappedBy\' attribute'
                );
            }
            if (!isset($versionableAnnotation->strategy)) {
                throw new AnnotationException(
                    'Annotation \'Versionable\' class \''.$baseClassMetadata->name.'\' does not have required \'strategy\' attribute');
            } else {
                $extendedClassMetadata->strategy = $versionableAnnotation->strategy;
            }
            $extendedClassMetadata->versionAssociation = $versionableAnnotation->mappedBy;
        }

        foreach ($classReflection->getProperties() as $property) {
            if ($baseClassMetadata->isMappedSuperclass && !$property->isPrivate() ||
                $baseClassMetadata->isInheritedField($property->name) ||
                isset($baseClassMetadata->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }

            if ($versionableAnnotation = $this->getAnnotationReader()->getPropertyAnnotation($property, self::VERSIONABLE)) {
                if (isset($versionableAnnotation->mappedBy)) {
                    throw new AnnotationException(
                        'Annotation \'Versionable\' in property \''.$property->getName().'\' of class \''.$baseClassMetadata->name.'\' has \'mappedBy\' attribute which is not allowed at property level'
                    );
                }

                $extendedClassMetadata->addVersionableProperty($property->getName(), $versionableAnnotation->targetField);
            }

            if ($statusAnnotation = $this->getAnnotationReader()->getPropertyAnnotation($property, self::STATUS))
                $extendedClassMetadata->statusProperty = $property->getName();

            if ($versionAnnotation = $this->getAnnotationReader()->getPropertyAnnotation($property, self::VERSION))
                $extendedClassMetadata->versionProperty = $property->getName();
        }
    }
}
