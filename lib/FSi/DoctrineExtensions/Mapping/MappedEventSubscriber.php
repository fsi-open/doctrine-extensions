<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\EventArgs;
use FSi\DoctrineExtensions\Mapping\Event\Adapter;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

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
     * ExtensionMetadataFactory used to read the extension
     * metadata through the extension drivers.
     *
     * @var \FSi\DoctrineExtensions\Mapping\ExtensionMetadataFactory[]
     */
    private $extensionMetadataFactory = [];

    /**
     * List of event adapters used for this listener.
     *
     * @var Adapter\ORM
     */
    private $adapter;

    /**
     * Custom annotation reader.
     *
     * @var Reader
     */
    private $annotationReader;

    /**
     * Default annotation reader.
     *
     * @var Reader
     */
    private $defaultAnnotationReader;

    /**
     * Sets the annotation reader which is passed further to the annotation driver.
     *
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param string $class
     * @return \FSi\DoctrineExtensions\Metadata\ClassMetadataInterface
     */
    public function getExtendedMetadata(ObjectManager $objectManager, $class)
    {
        $factory = $this->getExtendedMetadataFactory($objectManager);
        $extendedMetadata = $factory->getClassMetadata($class);
        $metadata = $objectManager->getClassMetadata($class);
        if (!$metadata->isMappedSuperclass) {
            $this->validateExtendedMetadata($metadata, $extendedMetadata);
        }
        return $extendedMetadata;
    }

    /**
     * Validate complete metadata for final class (i.e. that is not mapped-superclass).
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $baseClassMetadata
     * @param \FSi\DoctrineExtensions\Metadata\ClassMetadataInterface $extendedClassMetadata
     */
    abstract protected function validateExtendedMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata);

    /**
     * Get the namespace of extension event subscriber
     * used for cache id of extensions also to know where
     * to find Mapping drivers and event adapters.
     *
     * @return string
     */
    abstract protected function getNamespace();

    /**
     * Get extended metadata mapping reader.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @return \FSi\DoctrineExtensions\Metadata\MetadataFactory
     */
    protected function getExtendedMetadataFactory(ObjectManager $objectManager)
    {
        $oid = spl_object_hash($objectManager);
        if (!isset($this->extensionMetadataFactory[$oid])) {
            if (is_null($this->annotationReader)) {
                $this->annotationReader = $this->getDefaultAnnotationReader();
            }
            $this->extensionMetadataFactory[$oid] = new ExtendedMetadataFactory(
                $objectManager,
                $this->getNamespace(),
                $this->annotationReader
            );
        }
        return $this->extensionMetadataFactory[$oid];
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param $object
     * @return \FSi\DoctrineExtensions\Metadata\ClassMetadataInterface
     */
    protected function getObjectExtendedMetadata(ObjectManager $objectManager, $object)
    {
        $meta = $objectManager->getMetadataFactory()->getMetadataFor(get_class($object));
        return $this->getExtendedMetadata($objectManager, $meta->getName());
    }

    /**
     * Create default annotation reader for extensions.
     *
     * @return \Doctrine\Common\Annotations\AnnotationReader
     */
    private function getDefaultAnnotationReader()
    {
        if (null === $this->defaultAnnotationReader) {
            $reader = new \Doctrine\Common\Annotations\AnnotationReader();
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());
            $this->defaultAnnotationReader = $reader;
        }
        return $this->defaultAnnotationReader;
    }
}
