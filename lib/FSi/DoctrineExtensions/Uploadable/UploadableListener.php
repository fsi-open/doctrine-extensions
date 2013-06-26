<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\Component\PropertyObserver\PropertyObserver;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;
use Gaufrette\Filesystem;
use Gaufrette\FilesystemMap;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class UploadableListener extends MappedEventSubscriber
{
    /**
     * Default key length.
     */
    const KEY_LENGTH = 255;

    /**
     * @var \FSi\DoctrineExtensions\Uploadable\FilesystemMap
     */
    protected $filesystemMap;

    /**
     * @var string
     *
     * Default filesystem domain.
     */
    protected $defaultDomain;

    /**
     * @var integer
     */
    protected $defaultKeyLength = self::KEY_LENGTH;

    /**
     * @var Keymaker/KeymakerInterface
     */
    protected $defaultKeymaker;

    /**
     * @var array()
     */
    protected $toDelete = array();

    /**
     * @var \FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface
     */
    protected $fileHandler;

    /**
     * @param array|\Gaufrette\FilesystemMap $filesystems
     * @param FileHandler\FileHandlerInterface $fileHandler
     * @param array $options
     * @throws Exception\RuntimeException
     */
    public function __construct($filesystems, FileHandler\FileHandlerInterface $fileHandler, array $options = array())
    {
        // Options.
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array('default' => '', 'keyLength' => self::KEY_LENGTH, 'keymaker' => null));
        $options = $resolver->resolve($options);

        // Filesystems.
        $default = $options['default'];
        if (is_array($filesystems)) {
            $this->filesystemMap = new FilesystemMap();
            foreach ($filesystems as $domain => $filesystem) {
                if (!$filesystem instanceof Filesystem) {
                    throw new RuntimeException(sprintf(
                       'Filesystem for domain "%s" must be instance of Gaufrette\Filesystem, "%s" given.',
                        $domain,
                        is_object($filesystem) ? get_class($filesystem) : gettype($filesystem)
                    ));
                }
                $this->filesystemMap->set($domain, $filesystem);
            }
        } elseif ($filesystems instanceof FilesystemMap) {
            $this->filesystemMap = $filesystems;
        } else {
            throw new RuntimeException(sprintf(
                'Option "filesystems" must be type of "array" or "Gaufrette\FilesystemMap", "%s" given.',
                is_object($filesystems) ? get_class($filesystems) : gettype($filesystems)
            ));
        }

        // Checking filesystem map.
        if (count($this->filesystemMap->all()) === 0) {
            throw new RuntimeException(sprintf("No filesystems specified!"));
        }

        // Set file handler.
        $this->setFileHandler($fileHandler);

        // Set default filesystem if default option is empty.
        if (empty($default)) {
            $default = key($this->filesystemMap->all());
        }
        $this->setDefaultDomain($default);

        // Set key length.
        $this->setDefaultKeyLength($options['keyLength']);

        // Set keymaker.
        $this->setDefaultKeymaker($options['keymaker'] ? $options['keymaker'] : new Keymaker\Entity());
    }

    /**
     * @return array
     */
    public function getFilesystems()
    {
        return $this->filesystemMap->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'preFlush',
            'postLoad',
            'postPersist',
            'preUpdate',
            'postFlush',
            'postRemove',
        );
    }

    /**
     * @param $default
     * @throws Exception\RuntimeException
     */
    public function setDefaultDomain($default)
    {
        if (!$this->has($default)) {
            throw new RuntimeException(sprintf('There is no "%s" domain.', $default));
        }
        $this->defaultDomain = $default;
    }

    /**
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->defaultDomain;
    }

    /**
     * @param $domain
     * @return bool
     */
    public function has($domain)
    {
        return $this->filesystemMap->has($domain);
    }

    /**
     * @param $domain
     * @return Filesystem
     * @throws Exception\RuntimeException
     */
    public function getFilesystem($domain)
    {
        if (!$this->has($domain)) {
            throw new RuntimeException(sprintf('There is no filesystem for "%s" domain.', $domain));
        }
        return $this->filesystemMap->get($domain);
    }

    /**
     * After loading the entity load file if any.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postLoad(LifecycleEventArgs  $eventArgs)
    {
        $eventAdapter = $this->getEventAdapter($eventArgs);
        $objectManager = $eventAdapter->getObjectManager();
        $object = $eventAdapter->getObject();
        $meta = $objectManager->getClassMetadata(get_class($object));
        $uploadableMeta = $this->getExtendedMetadata($objectManager, $meta->name);

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->loadFiles($object, $uploadableMeta, $objectManager);
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $eventAdapter = $this->getEventAdapter($eventArgs);
        $objectManager = $eventAdapter->getObjectManager();
        $object = $eventAdapter->getObject();
        $meta = $objectManager->getClassMetadata(get_class($object));
        $uploadableMeta = $this->getExtendedMetadata($objectManager, $meta->name);

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->updateFiles($objectManager, $meta, $uploadableMeta, $object);
            $objectManager->flush($object);
        }
    }

    /**
     * Check and eventually update files keys.
     *
     * @param PreFlushEventArgs $eventArgs
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $eventAdapter = $this->getEventAdapter($eventArgs);
        $objectManager = $eventAdapter->getObjectManager();

        foreach ($unitOfWork->getIdentityMap() as $class => $entities) {
            $uploadableMeta = $this->getExtendedMetadata($entityManager, $class);
            if (!$uploadableMeta->hasUploadableProperties()) {
                continue;
            }
            $meta = $objectManager->getClassMetadata($class);
            foreach ($entities as $object) {
                if ($object instanceof Proxy) {
                    continue;
                }
                $this->updateFiles($entityManager, $meta, $uploadableMeta, $object);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $eventAdapter = $this->getEventAdapter($eventArgs);
        $objectManager = $eventAdapter->getObjectManager();
        $object = $eventAdapter->getObject();
        $meta = $objectManager->getClassMetadata(get_class($object));
        $uploadableMeta = $this->getExtendedMetadata($objectManager, $meta->name);

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->updateFiles($objectManager, $meta, $uploadableMeta, $object);
        }
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        foreach ($this->toDelete as $file) {
            $file->delete();
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        $eventAdapter = $this->getEventAdapter($eventArgs);
        $objectManager = $eventAdapter->getObjectManager();
        $object = $eventAdapter->getObject();
        $meta = $objectManager->getClassMetadata(get_class($object));
        $uploadableMeta = $this->getExtendedMetadata($objectManager, $meta->name);

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->deleteFiles($uploadableMeta, $object);
        }
    }

    /**
     * @param $length
     * @throws Exception\RuntimeException
     */
    public function setDefaultKeyLength($length)
    {
        if ($length < 1) {
            throw new RuntimeException(sprintf('Key length must be greater than "%d"', $length));
        }

        $this->defaultKeyLength = $length;
    }

    /**
     * @return int
     */
    public function getDefaultKeyLength()
    {
        return $this->defaultKeyLength;
    }

    /**
     * @param Keymaker\KeymakerInterface $keymaker
     * @throws Exception\RuntimeException
     */
    public function setDefaultKeymaker($keymaker)
    {
        /*
         * Type is checked here, not in method definition, since
         * this method is also used in constructor, so if someone would
         * give wrong keymaker he would get php error without proper message
         * and backtrace to constructor.
         */
        if (!$keymaker instanceof Keymaker\KeymakerInterface) {
            throw new RuntimeException(sprintf(
                'Keymaker must be instance of FSi\\DoctrineExtensions\\Uploadable\\Keymaker\\KeymakerInterface, "%s" given',
                is_object($keymaker) ? get_class($keymaker) : gettype($keymaker)
            ));
        }

        $this->defaultKeymaker = $keymaker;
    }

    /**
     * @return Keymaker\KeymakerInterface
     */
    public function getDefaultKeymaker()
    {
        return $this->defaultKeymaker;
    }

    /**
     * @param Filehandler\FileHandlerInterface $fileHandler
     */
    public function setFileHandler(Filehandler\FileHandlerInterface $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    /**
     * @return \FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface
     */
    public function getFileHandler()
    {
        return $this->fileHandler;
    }

    /**
     * Load object files and attach observers for key fields.
     *
     * @param object $object
     * @param \FSi\DoctrineExtensions\Uploadable\Mapping\UploadableListener $uploadableMeta
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     */
    protected function loadFiles($object, $uploadableMeta, $objectManager)
    {
        $propertyObserver = $this->getPropertyObserver($objectManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            // File key.
            $reflection = new \ReflectionProperty($object, $property);
            $reflection->setAccessible(true);
            $key = $reflection->getValue($object);

            // Injecting file.
            if (!empty($key)) {
                $domain = $this->computeDomain($config);
                $filesystem = $this->filesystemMap->get($domain);
                $file = new File($key, $filesystem);
                $propertyObserver->setValue($object, $config['targetField'], $file);
            }
        }
    }

    /**
     * Updating files keys.
     *
     * @param ObjectManager $objectManager
     * @param \Doctrine\ORM\Mapping\ClassMetadata $meta
     * @param \FSi\DoctrineExtensions\Uploadable\Mapping\UploadableListener $uploadableMeta
     * @param object $object
     * @throws Exception\RuntimeException
     */
    protected function updateFiles(ObjectManager $objectManager, $meta, $uploadableMeta, $object)
    {
        $propertyObserver = $this->getPropertyObserver($objectManager);

        $id = array();
        foreach ($meta->identifier as $keyField) {
            $reflection = new \ReflectionProperty($object, $keyField);
            $reflection->setAccessible(true);
            $id[] = $reflection->getValue($object);
        }
        $id = implode('-', $id);

        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            if (!$propertyObserver->hasSavedValue($object, $config['targetField']) || $propertyObserver->hasValueChanged($object, $config['targetField'])) {
                $file = PropertyAccess::getPropertyAccessor()->getValue($object, $config['targetField']);
                $reflection = new \ReflectionProperty($object, $property);
                $reflection->setAccessible(true);

                $domain = $this->computeDomain($config);

                // Since file has changed, the old one should be removed.
                if ($oldKey = $reflection->getValue($object)) {
                    $this->addToDelete(new File($oldKey, $this->filesystemMap->get($domain)));
                }

                if (empty($file)) {
                    $reflection->setValue($object, null);
                    $propertyObserver->saveValue($object, $config['targetField']);
                    continue;
                }

                $keymaker = $this->computeKeymaker($config);
                $keyLength = $this->computeKeyLength($config);

                if (!$fileName = $this->getFileHandler()->getName($file)) {
                    throw $this->generateCantHandleResourceException($file);
                }

                $newKey = $keymaker->createKey($object, $property, $id, $fileName, $keyLength);

                if ($newFile = $this->getFileHandler()->handle($file, $newKey, $this->filesystemMap->get($domain))) {
                    $reflection->setValue($object, $newFile->getKey());
                    PropertyAccess::getPropertyAccessor()->setValue($object, $config['targetField'], $newFile);
                    // Save its current value, so if another fetch will be called, there won't be another saving.
                    $propertyObserver->saveValue($object, $config['targetField']);
                } else {
                    throw $this->generateCantHandleResourceException($file);
                }
            }
        }
    }

    /**
     * Deleting files.
     *
     * @param \FSi\DoctrineExtensions\Uploadable\Mapping\UploadableListener $uploadableMeta
     * @param object $object
     * @throws Exception\RuntimeException
     */
    protected function deleteFiles($uploadableMeta, $object)
    {
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            $reflection = new \ReflectionProperty($object, $property);
            $reflection->setAccessible(true);
            $domain = $this->computeDomain($config);

            if ($oldKey = $reflection->getValue($object)) {
                $this->addToDelete(new File($oldKey, $this->filesystemMap->get($domain)));
            }
        }
    }

    /**
     * Returns PropertyObserver for specified ObjectManager
     *
     * @param ObjectManager $objectManager
     * @return mixed
     */
    protected function getPropertyObserver(ObjectManager $objectManager)
    {
        $oid = spl_object_hash($objectManager);
        if (!isset($this->propertyObservers[$oid])) {
            $this->propertyObservers[$oid] = new PropertyObserver();
        }
        return $this->propertyObservers[$oid];
    }

    /**
     * {@inheritDoc}
     */
    protected function validateExtendedMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @param File $file
     */
    protected function addToDelete(File $file)
    {
        $this->toDelete[] = $file;
    }

    /**
     * @param array $config
     * @return string
     */
    private function computeDomain(array $config)
    {
        return !empty($config['domain']) ? $config['domain'] : $this->getDefaultDomain();
    }

    /**
     * @param array $config
     * @return Keymaker\KeymakerInterface
     */
    private function computeKeymaker($config)
    {
        return !empty($config['keymaker']) ? $config['keymaker'] : $this->getDefaultKeymaker();
    }

    /**
     * @param $config
     * @return integer
     */
    private function computeKeyLength($config)
    {
        return !empty($config['keyLength']) ? $config['keyLength'] : $this->getDefaultKeyLength();
    }

    /**
     * @param mixed $file
     * @return RuntimeException
     */
    private function generateCantHandleResourceException($file)
    {
        return new RuntimeException(sprintf('Can\'t handle resource of type "%s".', is_object($file) ? get_class($file) : gettype($file)));
    }
}
