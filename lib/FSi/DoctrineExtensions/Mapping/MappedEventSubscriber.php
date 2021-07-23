<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use RuntimeException;

/**
 * This is extension of event subscriber class and is
 * used specifically for handling the extension metadata
 * mapping for extensions.
 *
 * It dries up some reusable code which is common for
 * all extensions who mapps additional metadata through
 * extended drivers.
 *
 * Extension is based at Gedmo\Mapping.
 */
abstract class MappedEventSubscriber implements EventSubscriber
{
    /**
     * ExtendedMetadataFactory used to read the extension
     * metadata through the extension drivers.
     *
     * @var ExtendedMetadataFactory[]
     */
    private $extendedMetadataFactory = [];

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var Reader
     */
    private $defaultAnnotationReader;

    public function setAnnotationReader(Reader $reader): void
    {
        $this->annotationReader = $reader;
    }

    public function getExtendedMetadata(
        EntityManagerInterface $entityManager,
        string $class
    ): ClassMetadataInterface {
        $factory = $this->getExtendedMetadataFactory($entityManager);
        $extendedMetadata = $factory->getClassMetadata($class);
        $metadata = $entityManager->getClassMetadata($class);
        if (!$metadata instanceof ClassMetadataInfo) {
            throw new RuntimeException(sprintf(
                'Expected object of class "%s", got "%s"',
                '\Doctrine\ORM\Mapping\ClassMetadataInfo',
                get_class($metadata)
            ));
        }
        if (!$metadata->isMappedSuperclass) {
            $this->validateExtendedMetadata($metadata, $extendedMetadata);
        }
        return $extendedMetadata;
    }

    abstract protected function validateExtendedMetadata(
        ClassMetadata $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void;

    /**
     * Get the namespace of extension event subscriber
     * used for cache id of extensions also to know where
     * to find Mapping drivers and event adapters.
     *
     * @return string
     */
    abstract protected function getNamespace(): string;

    protected function getExtendedMetadataFactory(
        EntityManagerInterface $entityManager
    ): ExtendedMetadataFactory {
        $oid = spl_object_hash($entityManager);
        if (!isset($this->extendedMetadataFactory[$oid])) {
            if (is_null($this->annotationReader)) {
                $this->annotationReader = $this->getDefaultAnnotationReader($entityManager);
            }
            $this->extendedMetadataFactory[$oid] = new ExtendedMetadataFactory(
                $entityManager,
                $this->getNamespace(),
                $this->annotationReader
            );
        }

        return $this->extendedMetadataFactory[$oid];
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @return ClassMetadataInterface
     */
    protected function getObjectExtendedMetadata(
        EntityManagerInterface $entityManager,
        $object
    ): ClassMetadataInterface {
        $meta = $entityManager->getMetadataFactory()->getMetadataFor(get_class($object));
        return $this->getExtendedMetadata($entityManager, $meta->getName());
    }

    private function getDefaultAnnotationReader(EntityManagerInterface $entityManager): Reader
    {
        if (null === $this->defaultAnnotationReader) {
            $this->defaultAnnotationReader = new AnnotationReader();

            $cache = $entityManager->getConfiguration()->getMetadataCacheImpl();
            if (null !== $cache) {
                $this->defaultAnnotationReader = new CachedReader($this->defaultAnnotationReader, $cache);
            }
        }

        return $this->defaultAnnotationReader;
    }
}
