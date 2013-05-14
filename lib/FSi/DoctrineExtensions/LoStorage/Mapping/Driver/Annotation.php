<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\LoStorage\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use FSi\Component\Reflection\ReflectionClass;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver;
use FSi\DoctrineExtensions\LoStorage\Exception\AnnotationException;

class Annotation extends AbstractAnnotationDriver
{
    const TIMESTAMP = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\Timestamp';
    const FILENAME = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\Filename';
    const FILEPATH = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\Filepath';
    const MIMETYPE = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\MimeType';
    const SIZE = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\Size';
    const DATA = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\Data';
    const STORAGE = 'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation\\Storage';

    private static $loFields = array(
        'timestamp' => self::TIMESTAMP,
        'filename' => self::FILENAME,
        'filepath' => self::FILEPATH,
        'mimetype' => self::MIMETYPE,
        'size' => self::SIZE,
        'data' => self::DATA
    );

    /**
     * {@inheritDoc}
     */
    public function loadExtendedClassMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        $class = ReflectionClass::factory($baseClassMetadata->name);
        // class annotations
        if (($annotation = $this->getAnnotationReader()->getClassAnnotation($class, self::FILEPATH))
            && isset($annotation->value))
            $extendedClassMetadata->filepath = $annotation->value;
        $largeObjects = array();
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($baseClassMetadata->isMappedSuperclass && !$property->isPrivate()
                || $baseClassMetadata->isInheritedField($property->name)
                || isset($baseClassMetadata->associationMappings[$property->name]['inherited']))
                continue;
            foreach (self::$loFields as $field => $annotationClass) {
                if ($annotation = $this->getAnnotationReader()->getPropertyAnnotation($property, $annotationClass)) {
                    if (!isset($annotation->lo))
                        $annotation->lo = 'lo';
                    if (isset($largeObjects[$annotation->lo]['fields'][$field]) &&
                        ($largeObjects[$annotation->lo]['fields'][$field] != $property->getName())) {
                        throw new AnnotationException(
                            'Annotation "' . $annotationClass . ' is already defined for large object "'
                                . $annotation->lo
                                . '". Multiple large objects in the same entity need to be named unambiguously.');
                    }
                    $largeObjects[$annotation->lo]['fields'][$field] = $property->getName();
                    if (isset($annotation->value))
                        $largeObjects[$annotation->lo]['values'][$field] = $annotation->value;
                }
            }
            if ($storage = $this->getAnnotationReader()->getPropertyAnnotation($property, self::STORAGE)) {
                $extendedClassMetadata->storageProperty = $property->getName();
            }
        }
        if (count($largeObjects) && !isset($extendedClassMetadata->filepath))
            $extendedClassMetadata->filepath = str_replace('\\', '_', $baseClassMetadata->name);
        foreach ($largeObjects as $lo => $largeObject) {
            $extendedClassMetadata->addLargeObject($lo, $largeObject['fields'], $largeObject['values']);
        }
    }

}
