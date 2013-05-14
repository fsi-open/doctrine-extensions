<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\LoStorage;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\Component\Reflection\ReflectionProperty;
use FSi\Component\PropertyObserver\PropertyObserver;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\LoStorage\Mapping\ClassMetadata as LoStorageClassMetadata;

/**
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class LoStorageListener extends MappedEventSubscriber
{
    /**
     * Base path for storing cache files
     *
     * @var string
     */
    protected $basePath;

    /**
     * Default create mode for new directories created in cache
     *
     * @var integer
     */
    protected $createMode;

    /**
     * Flag indicating if orhpan files should be checked and removed during every cache operation
     *
     * @var bool
     */
    protected $removeOrphans;

    /**
     * The string that is used to implode compound primary keys into one string
     *
     * @var string
     */
    protected $identifierGlue;

    /**
     * Array of property observers for each object manager
     *
     * @var array
     */
    private $_propertyObservers = array();

    /**
     * Public constructor
     *
     * @param string $basePath
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['basePath']))
            $options['basePath'] = sys_get_temp_dir();
        $this->setBasePath($options['basePath']);
        if (!isset($options['createMode']))
            $options['createMode'] = 0700;
        $this->setCreateMode($options['createMode']);
        if (!isset($options['removeOrphans']))
            $options['removeOrphans'] = false;
        $this->setRemoveOrphans($options['removeOrphans']);
        if (!isset($options['identifierGlue']))
            $options['identifierGlue'] = '-';
        $this->setIdentifierGlue($options['identifierGlue']);
    }

    /**
     * Set the base path for storing cache files
     *
     * @param string $basePath
     * @return \FSi\DoctrineExtensions\LoStorage\LoStorageListener
     */
    public function setBasePath($basePath)
    {
        $basePath = rtrim((string)$basePath, '/');
        if (!file_exists($basePath) || !is_dir($basePath))
            throw new Exception\RuntimeException('Directory "'.$basePath.'" specified as a LoStorage\'s base path does not exists or is not a directory');
        $this->basePath = realpath($basePath);
        return $this;
    }

    /**
     * Get the base path where cache files are stored
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Set the create mode for new directories
     *
     * @param integer $createMode
     * @return \FSi\DoctrineExtensions\LoStorage\LoStorageListener
     */
    public function setCreateMode($createMode)
    {
        $this->createMode = (int)$createMode;
        return $this;
    }

    /**
     * Get the default create mode for new directories
     *
     * @return integer
     */
    public function getCreateMode()
    {
        return $this->createMode;
    }

    /**
     * Set the current mode of removing orphans
     *
     * @param integer $removeOrphans
     * @return \FSi\DoctrineExtensions\LoStorage\LoStorageListener
     */
    public function setRemoveOrphans($removeOrphans)
    {
        $this->removeOrphans = (int)$removeOrphans;
        return $this;
    }

    /**
     * Get the current mode of removing orphans
     *
     * @return bool
     */
    public function getRemoveOrphans()
    {
        return $this->removeOrphans;
    }

    /**
     * Set the current identifier glue
     *
     * @param string $identifierGlue
     * @return \FSi\DoctrineExtensions\LoStorage\LoStorageListener
     */
    public function setIdentifierGlue($identifierGlue)
    {
        if (strstr($identifierGlue, DIRECTORY_SEPARATOR) !== false)
            throw new LoStorageException('Identifier glue cannot contain \'' . DIRECTORY_SEPARATOR . '\' which is the DIRECTORY_SEPARATOR');
        $this->identifierGlue = (string)$identifierGlue;
        return $this;
    }

    /**
     * Get the current identifier glue
     *
     * @return string
     */
    public function getIdentitierGlue()
    {
        return $this->identitierGlue;
    }

    /**
     * Returns PropertyObserver for specified ObjectManager
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @return \FSi\Component\PropertyObserver\PropertyObserver
     */
    protected function getPropertyObserver(ObjectManager $objectManager)
    {
        $oid = spl_object_hash($objectManager);
        if (!isset($this->_propertyObservers[$oid])) {
            $this->_propertyObservers[$oid] = new PropertyObserver();
        }
        return $this->_propertyObservers[$oid];
    }

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
//            'loadClassMetadata',
            'preUpdate',
            'preRemove',
            'postLoad',
            'postPersist',
            'postUpdate',
            'preFlush'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateExtendedMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        if (!($extendedClassMetadata instanceof LoStorageClassMetadata)) {
            throw new Exception\MappingException('Metadata passed to LoStorage listener is not an object of class FSi\DoctrineExtensions\LoStorage\Mapping\ClassMetadata');
        }
        if (isset($extendedClassMetadata->storageProperty)) {
            if ($extendedClassMetadata->hasLargeObjects())
                throw new Exception\AnnotationException(
                    'Entity ' . $baseClassMetadata->getName() . ' has a \'storage\' field so it must not contain any large objects');
        }
        else if ($extendedClassMetadata->hasLargeObjects()) {
            foreach ($extendedClassMetadata->getLargeObjects() as $lo => $loConfig) {
                if (!isset($loConfig['fields']['filepath']))
                    throw new AnnotationException('Large object ' . $lo . ' does not have \'filepath\' field defined');
                else if ($baseClassMetadata->hasField($loConfig['fields']['filepath']))
                    throw new AnnotationException(
                        'Large object ' . $lo
                            . '\'s \'filepath\' field must not be a mapped field and must not be an association');
                if (!isset($loConfig['fields']['filename']))
                    throw new AnnotationException('Large object ' . $lo . ' does not have \'filename\' field defined');
                else if (!$baseClassMetadata->hasField($loConfig['fields']['filename']) && !isset($loConfig['values']['filename']))
                    throw new AnnotationException(
                        'Large object ' . $lo
                            . '\'s \'filename\' field must have a value attribute with default filename');
                else if ($baseClassMetadata->hasAssociation($loConfig['fields']['filename']))
                    throw new AnnotationException(
                        'Large object ' . $lo . '\'s \'filename\' field must not be an association');
                if (isset($loConfig['fields']['timestamp'])
                    && (!$baseClassMetadata->hasField($loConfig['fields']['timestamp'])
                        || $baseClassMetadata->hasAssociation($loConfig['fields']['timestamp'])))
                    throw new AnnotationException(
                        'Large object ' . $lo
                            . '\'s \'timestamp\' field must be a mapped field and must not be an association');
                if (!isset($loConfig['fields']['data'])
                    || !$baseClassMetadata->isSingleValuedAssociation($loConfig['fields']['data']))
                    throw new AnnotationException('Large object ' . $lo . ' does not have \'data\' association defined');
            }
        }
    }

    /**
     * Mapps additional metadata for the Entity
     *
     * @param EventArgs $eventArgs
     */
    /*public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
        $class = $eventArgs->getClassMetadata()->getName();
        if (!isset($this->configurations[$class]['lo']))
            return;
        $config = $this->configurations[$class];
        $changeTrackingListener = $this->getChangeTrackingListener($ea->getObjectManager());
        foreach ($config['lo'] as $lo => $loConfig) {
            if (isset($loConfig['fields']['filepath']))
                $changeTrackingListener->addTrackedProperty($class, $loConfig['fields']['filepath']);
        }
    }*/

    /**
     * Helper method which removes all contents from specified directory and optionally leave only specified paths. Paths to
     * leave can be of any depth and can point to files or directories as well.
     *
     * @param string $path
     * @param array $leave
     * @return bool
     */
    protected function clearDirectory($path, array $leave = array())
    {
        if (!file_exists($path) || !is_dir($path))
            return false;
        $iterator = new \DirectoryIterator($path);
        $return = false;
        /* @var $item \SplFileInfo */
        foreach ($iterator as $item) {
            if (in_array($item->getFilename(), array('.', '..')))
                continue;
            if (in_array($item->getPathname(), $leave)) {
                $return = true;
                continue;
            }
            if ($item->isDir()) {
                if ($this->clearDirectory($item->getPathname(), $leave))
                    $return = true;
                else
                    rmdir($item->getPathname());
            } else
                unlink($item->getPathname());
        }
        return $return;
    }

    /**
     * Cache one entity's Large Object in file if necessary and store cached filepath in the appropriate entity field. This
     * method also updates fields describing the Large Object witch are not persisted and should be computed on every access
     * to the entity
     *
     * @param ObjectManager $om
     * @param ClassMetadata $meta
     * @param Mapping\ClassMetadata $loStorageMeta
     * @param string $lo
     * @param object $object
     * @return null|string
     */
    protected function cacheObjectLO(ObjectManager $om, ClassMetadata $meta, LoStorageClassMetadata $loStorageMeta, $lo, $object)
    {
        $loConfig = $loStorageMeta->getLargeObject($lo);
        $propertyObserver = $this->getPropertyObserver($om);
        if ($this->hasLoData($meta, $object, $loConfig)) {
            $filepath = $this->getCachedFilepath($meta, $loStorageMeta, $lo, $object);
            $timestamp = 0;
            if (isset($loConfig['fields']['timestamp']))
                $timestamp = $meta->getFieldValue($object, $loConfig['fields']['timestamp']);
            if (!file_exists($filepath) || !$timestamp || ($timestamp->getTimestamp() > filemtime($filepath))) {
                $data = $this->getLoData($om, $meta, $object, $loConfig);
                if (!file_exists(dirname($filepath)))
                    mkdir(dirname($filepath), $this->createMode, true);
                file_put_contents($filepath, $data);
            }
            $propertyObserver->setValue($object, $loConfig['fields']['filepath'], $filepath);
            //ReflectionProperty::factory($meta->getName(), $loConfig['fields']['filepath'])->setValue($object, $filepath);
            if (isset($loConfig['fields']['filename']) && !$meta->hasField($loConfig['fields']['filename']))
                ReflectionProperty::factory($meta->getName(), $loConfig['fields']['filename'])->setValue($object, basename($filepath));
            if (isset($loConfig['fields']['mimetype']) && !$meta->hasField($loConfig['fields']['mimetype'])) {
                $finfo = new \finfo();
                if (!isset($data)) {
                    $data = $this->getLoData($om, $meta, $object, $loConfig);
                }
                ReflectionProperty::factory($meta->getName(), $loConfig['fields']['mimetype'])
                    ->setValue($object, $finfo->buffer($data, FILEINFO_MIME_TYPE));
            }
            if (isset($loConfig['fields']['size']) && !$meta->hasField($loConfig['fields']['size']))
                ReflectionProperty::factory($meta->getName(), $loConfig['fields']['size'])->setValue($object, filesize($filepath));
            return $filepath;
        } else {
            $propertyObserver->setValue($object, $loConfig['fields']['filepath'], null);
            //ReflectionProperty::factory($meta->getName(), $loConfig['fields']['filepath'])->setValue($object, null);
            if (isset($loConfig['fields']['filename']) && !$meta->hasField($loConfig['fields']['filename']))
                ReflectionProperty::factory($meta->getName(), $loConfig['fields']['filename'])->setValue($object, null);
            if (isset($loConfig['fields']['mimetype']) && !$meta->hasField($loConfig['fields']['mimetype']))
                ReflectionProperty::factory($meta->getName(), $loConfig['fields']['mimetype'])->setValue($object, null);
            if (isset($loConfig['fields']['size']) && !$meta->hasField($loConfig['fields']['size']))
                ReflectionProperty::factory($meta->getName(), $loConfig['fields']['size'])->setValue($object, null);
            return null;
        }
    }

    /**
     * Cache all entity's Large Objects in files if necessary and store cached filepaths in entity fields
     *
     * @param ObjectManager $om
     * @param ClassMetadata $meta
     * @param object $object
     */
    protected function cacheObjectLOs(ObjectManager $om, ClassMetadata $meta, $object)
    {
        $loStorageMeta = $this->getExtendedMetadata($om, $meta->getName());
        if (!$loStorageMeta->hasLargeObjects())
            return;
        $objectId = $this->getObjectIdentifier($meta, $object);
        $leave = array();
        foreach ($loStorageMeta->getLargeObjects() as $lo => $loConfig) {
            if ($filepath = $this->cacheObjectLO($om, $meta, $loStorageMeta, $lo, $object))
                $leave[] = $filepath;
        }
        if ($this->removeOrphans)
            $this->clearDirectory($this->getCachedObjectPath($meta, $loStorageMeta, $object), $leave);
    }

    /**
     * After loading en entity cache its large objects in files if necessary and store cached filepaths in entity fields
     *
     * @param EventArgs $eventArgs
     */
    public function postLoad(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $this->cacheObjectLOs($om, $meta, $object);
    }

    /**
     * After persisting en entity cache its large objects in files if necessary and store cached filepaths in entity fields
     *
     * @param EventArgs $eventArgs
     */
    public function postPersist(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $this->cacheObjectLOs($om, $meta, $object);
    }

    /**
     * After updating en entity cache its large objects in files if necessary and store cached filepaths in entity fields
     *
     * @param EventArgs $eventArgs
     */
    public function postUpdate(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $this->cacheObjectLOs($om, $meta, $object);
    }

    /**
     * Before removing an entity physically from database remove its large objects cached in files
     *
     * @param EventArgs $eventArgs
     */
    public function preRemove(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $loStorageMeta = $this->getExtendedMetadata($om, $meta->name);
        if (!$loStorageMeta->hasLargeObjects())
            return;
        $objectId = $this->getObjectIdentifier($meta, $object);
        foreach ($loStorageMeta->getLargeObjects() as $lo => $loConfig) {
            if ($this->hasLoData($meta, $object, $loConfig)) {
                $filepath = $this->getCachedFilepath($meta, $loStorageMeta, $lo, $object);
                unlink($filepath);
            }
        }
        $this->clearDirectory($this->getCachedObjectPath($meta, $loStorageMeta, $object));
    }

    /**
     * Before updating an entity remove its large objects cached in files
     *
     * @param EventArgs $eventArgs
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $loStorageMeta = $this->getExtendedMetadata($om, $meta->name);
        if (!$loStorageMeta->hasLargeObjects())
            return;
        $objectId = $this->getObjectIdentifier($meta, $object);
        foreach ($loStorageMeta->getLargeObjects() as $lo => $loConfig) {
            $propertyObserver = $this->getPropertyObserver($om);
            $filepath = $propertyObserver->getSavedValue($object, $loConfig['fields']['filepath']);
            $objectCachePath = $this->getCachedObjectPath($meta, $loStorageMeta, $object);
            // additional sanity check before removing previously cached file
            if (isset($filepath) && (substr($filepath, 0, strlen($objectCachePath)) === $objectCachePath))
                unlink($filepath);
        }
    }

    /**
     * Helper method returning large object's data as a string
     *
     * @param ObjectManager $om
     * @param ClassMetadata $meta
     * @param object $object
     * @param array $loConfig
     * @throws AnnotationException
     * @return string
     */
    protected function getLoData(ObjectManager $om, ClassMetadata $meta, $object, array $loConfig)
    {
        if ($dataObject = $meta->getFieldValue($object, $loConfig['fields']['data'])) {
            $dataAssociation = $meta->getAssociationTargetClass($loConfig['fields']['data']);
            $storageMeta = $this->getExtendedMetadata($om, $dataAssociation);
            if (!isset($storageMeta->storageProperty))
                throw new Exception\MappingException('Entity class '.$dataAssociation.' must have \'storage\' field defined');
            $dataMeta = $om->getClassMetadata($dataAssociation);
            // TODO: find a cleaner way to ensure object is loaded
            if (method_exists($dataObject, '__load'))
                $dataObject->__load();
            return $dataMeta->getFieldValue($dataObject, $storageMeta->storageProperty);
        }
        return null;
    }

    /**
     * Helper method checking if specified large object has a value without the need to load it from database
     *
     * @param ClassMetadata $meta
     * @param object $object
     * @param array $loConfig
     * @return bool
     */
    protected function hasLoData(ClassMetadata $meta, $object, array $loConfig)
    {
        return ($meta->getFieldValue($object, $loConfig['fields']['data']) !== null);
    }

    /**
     * Helper method setting specified large object's data by creating new storage entity or replacing data in the existing one.
     *
     * @param ObjectManager $om
     * @param ClassMetadata $meta
     * @param array $loConfig
     * @param object $object
     * @param string $value
     */
    protected function setLoData(ObjectManager $om, ClassMetadata $meta, array $loConfig, $object, $value)
    {
        $data = $meta->getFieldValue($object, $loConfig['fields']['data']);
        $dataAssociation = $meta->getAssociationTargetClass($loConfig['fields']['data']);
        $dataLoMeta = $this->getExtendedMetadata($om, $dataAssociation);
        $dataMeta = $om->getClassMetadata($dataAssociation);
        if (!isset($data) && isset($value) && $value) {
            $data = new $dataAssociation();
            $meta->setFieldValue($object, $loConfig['fields']['data'], $data);
            $dataMeta->setFieldValue($data, $dataLoMeta->storageProperty, $value);
            $om->persist($data);
        } else {
            if (isset($value) && $value) {
                if ($data instanceof \Doctrine\ORM\Proxy\Proxy)
                    $data->__load();
                $dataMeta->setFieldValue($data, $dataLoMeta->storageProperty, $value);
            } else if ($data) {
                $meta->setFieldValue($object, $loConfig['fields']['data'], null);
                $om->getUnitOfWork()->scheduleForDelete($data);
            }
        }
    }

    /**
     * Helper method returning object's identifier as a single string
     *
     * @param ClassMetadata $meta
     * @param object $object
     * @return string
     */
    protected function getObjectIdentifier(ClassMetadata $meta, $object)
    {
        return implode($this->identifierGlue, $meta->getIdentifierValues($object));
    }

    /**
     * Helper method returning directory path to the cache for the whole enity class
     *
     * @param ClassMetadata $meta
     * @param array $config
     * @return string
     */
    protected function getCachedClassPath(ClassMetadata $meta, LoStorageClassMetadata $loStorageMeta)
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $loStorageMeta->filepath;
    }

    /**
     * Helper method returning directory path to the cache for specific object
     *
     * @param ClassMetadata $meta
     * @param array $config
     * @param object $object
     * @return string
     */
    protected function getCachedObjectPath(ClassMetadata $meta, LoStorageClassMetadata $loStorageMeta, $object)
    {
        $objectId = $this->getObjectIdentifier($meta, $object);
        if (!$objectId)
            return null;
        return $this->getCachedClassPath($meta, $loStorageMeta) . DIRECTORY_SEPARATOR . $objectId;
    }

    /**
     * Helper method returning filepath to specified large object or null if the object is not yet persisted
     *
     * @param ClassMetadata $meta
     * @param array $config
     * @param string $lo
     * @param object $object
     * @param string $filename
     * @return string
     */
    protected function getCachedFilepath(ClassMetadata $meta, LoStorageClassMetadata $loStorageMeta, $lo, $object)
    {
        $path = $this->getCachedObjectPath($meta, $loStorageMeta, $object);
        if (!isset($path))
            return null;
        $loConfig = $loStorageMeta->getLargeObject($lo);
        if (isset($loConfig['values']['filepath']))
            $path .= DIRECTORY_SEPARATOR . $loConfig['values']['filepath'];
        if ($meta->hasField($loConfig['fields']['filename']))
            $path .= DIRECTORY_SEPARATOR . $meta->getFieldValue($object, $loConfig['fields']['filename']);
        else
            $path .= DIRECTORY_SEPARATOR . $loConfig['values']['filename'];
        return $path;
    }

    /**
     * Helper method used to update all necessary entity fields according to the new filepath
     *
     * @param ObjectManager $om
     * @param ClassMetadata $meta
     * @param array $loConfig
     * @param object $object
     * @param string $filepath
     */
    protected function updateLoState(ObjectManager $om, ClassMetadata $meta, array $loConfig, $object, $filepath)
    {
        if (isset($filepath)) {
            $filedata = file_get_contents($filepath);
            if ($meta->hasField($loConfig['fields']['filename']))
                $meta->setFieldValue($object, $loConfig['fields']['filename'], basename($filepath));
            if (isset($loConfig['fields']['timestamp']))
                $meta->setFieldValue($object, $loConfig['fields']['timestamp'], new \DateTime());
            if (isset($loConfig['fields']['mimetype']) && $meta->hasField($loConfig['fields']['mimetype'])) {
                $finfo = new \finfo();
                $mimetype = $finfo->buffer($filedata, FILEINFO_MIME_TYPE);
                $meta->setFieldValue($object, $loConfig['fields']['mimetype'], $mimetype);
            }
            if (isset($loConfig['fields']['size']) && $meta->hasField($loConfig['fields']['size']))
                $meta->setFieldValue($object, $loConfig['fields']['size'], strlen($filedata));
        } else {
            $filedata = null;
            if ($meta->hasField($loConfig['fields']['filename']))
                $meta->setFieldValue($object, $loConfig['fields']['filename'], null);
            if (isset($loConfig['fields']['timestamp']))
                $meta->setFieldValue($object, $loConfig['fields']['timestamp'], null);
            if (isset($loConfig['fields']['mimetype']) && $meta->hasField($loConfig['fields']['mimetype']))
                $meta->setFieldValue($object, $loConfig['fields']['mimetype'], null);
            if (isset($loConfig['fields']['size']) && $meta->hasField($loConfig['fields']['size']))
                $meta->setFieldValue($object, $loConfig['fields']['size'], null);
        }
        $this->setLoData($om, $meta, $loConfig, $object, $filedata);
    }

    /**
     * Check all managed and new entities if their filepath field have changed and load the new files into large objects if
     * necessary
     *
     * @param PreFlushEventArgs $eventArgs
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        /* @var $uow \Doctrine\ORM\UnitOfWork */
        $uow = $em->getUnitOfWork();
        $propertyObserver = $this->getPropertyObserver($em);

        $newEntities = $uow->getScheduledEntityInsertions();
        foreach ($newEntities as $object) {
            $meta = $em->getClassMetadata(get_class($object));
            $loStorageMeta = $this->getExtendedMetadata($em, $meta->name);
            if (!$loStorageMeta->hasLargeObjects())
                continue;
            foreach ($loStorageMeta->getLargeObjects() as $lo => $loConfig) {
                $filepath = ReflectionProperty::factory($meta->name, $loConfig['fields']['filepath'])->getValue($object);
                if (isset($filepath))
                    $this->updateLoState($em, $meta, $loConfig, $object, $filepath);
            }
        }

        $identityMap = $uow->getIdentityMap();
        foreach ($identityMap as $class => $entities) {
            $meta = $em->getClassMetadata($class);
            $loStorageMeta = $this->getExtendedMetadata($em, $meta->name);
            if (!$loStorageMeta->hasLargeObjects())
                continue;
            foreach ($entities as $object) {
                if ($object instanceof \Doctrine\ORM\Proxy\Proxy)
                    continue;
                foreach ($loStorageMeta->getLargeObjects() as $lo => $loConfig) {
                    if (!$propertyObserver->hasSavedValue($object, $loConfig['fields']['filepath']) || $propertyObserver->hasValueChanged($object, $loConfig['fields']['filepath'])) {
                        $filepath = ReflectionProperty::factory($meta->name, $loConfig['fields']['filepath'])->getValue($object);
                        $this->updateLoState($em, $meta, $loConfig, $object, $filepath);
                    }
                }
            }
        }
    }

    /**
     * Clear all cached files on the entity, class or database level
     *
     * @param ObjectManager $om
     * @param string $class
     * @param object $entity
     * @throws LoStorageException
     * @return LoStorageListener
     */
    public function clearCache(ObjectManager $om, $class = null, $entity = null)
    {
        $configs = array();
        if (isset($class)) {
            if (isset($entity) && (get_class($entity) !== $class))
                throw new Exception\RuntimeException('Specified enitity is not an instance of specified class.');
            $configs[$class] = $this->getExtendedMetadata($om, $class);
        } else if (isset($entity)) {
            $class = get_class($entity);
            $configs[$class] = $this->getExtendedMetadata($om, $class);
        } else {
            $classes = $om->getMetadataFactory()->getAllMetadata();
            foreach ($classes as $meta)
                $configs[$meta->getName()] = $this->getExtendedMetadata($om, $meta->getName());
        }
        foreach ($configs as $class => $loStorageMeta) {
            if (!$loStorageMeta->hasLargeObjects())
                continue;
            $meta = $om->getClassMetadata($class);
            if (isset($entity))
                $entities = array($entity);
            else
                $entities = $om->getRepository($class)->findAll();
            foreach ($entities as $object) {
                $dirname = $this->getCachedObjectPath($meta, $loStorageMeta, $object);
                if (isset($dirname) && is_dir($dirname)) {
                    $this->clearDirectory($dirname);
                    rmdir($dirname);
                }
            }
            $dirname = $this->getCachedClassPath($meta, $loStorageMeta);
            if (!isset($entity) && is_dir($dirname)) {
                $this->clearDirectory($dirname);
                rmdir($dirname);
            }
        }
        return $this;
    }

    /**
     * Fill Large Object cache on entity, class or database level
     *
     * @param ObjectManager $om
     * @param string $class
     * @param object $entity
     * @throws LoStorageException
     * @return LoStorageListener
     */
    public function fillCache(ObjectManager $om, $class = null, $entity = null)
    {
        $configs = array();
        if (isset($class)) {
            if (isset($entity) && (get_class($entity) !== $class)) {
                throw new Exception\RuntimeException('Specified enitity is not an instance of specified class.');
            }
            $configs[$class] = $this->getExtendedMetadata($om, $class);
        } else if (isset($entity)) {
            $class = get_class($entity);
            $configs[$class] = $this->getExtendedMetadata($om, $class);
        } else {
            $classes = $om->getMetadataFactory()->getAllMetadata();
            foreach ($classes as $meta)
                $configs[$meta->name] = $this->getExtendedMetadata($om, $meta->getName());
        }
        foreach ($configs as $class => $loStorageMeta) {
            $meta = $om->getClassMetadata($class);
            if (isset($entity))
                $this->cacheObjectLOs($om, $meta, $entity);
            else if (isset($loStorageMeta->filepath)) {
                $entities = $om->getRepository($class)->findAll();
                foreach ($entities as $e)
                    $this->cacheObjectLOs($om, $meta, $e);
                $dirname = $this->getCachedClassPath($meta, $loStorageMeta);
                if (is_dir($dirname) && $this->removeOrphans) {
                    $leave = array();
                    foreach ($entities as $object)
                        $leave[] = $this->getCachedObjectPath($meta, $loStorageMeta, $object);
                    $this->clearDirectory($dirname, $leave);
                }
            }
        }
        return $this;
    }

    /**
     * Get the namespace of extension event subscriber. Used for cache id of extensions also to know where to find Mapping
     * drivers and event adapters
     *
     * @return string
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }
}
