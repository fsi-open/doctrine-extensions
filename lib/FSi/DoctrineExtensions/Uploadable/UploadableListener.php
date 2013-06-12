<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Proxy;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Exception;
use FSi\Component\PropertyObserver\PropertyObserver;

class UploadableListener extends MappedEventSubscriber
{
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
     * @var array
     *
     * Hash table for property observers.
     */
    protected $propertyObservers;

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'preFlush',
        );
    }

    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('filesystems'));
        $resolver->setDefaults(array('default' => '', 'keymaker' => null, 'keylength' => 255));
        $resolver->setAllowedTypes(array('filesystems' => 'array', 'default' => 'string'));
        $options = $resolver->resolve($options);

        $this->filesystemMap = new FilesystemMap();

        $default = $options['default'];
        foreach ($options['filesystems'] as $domain => $filesystem) {
            if (empty($default)) {
                $default = $domain;
            }
            $this->filesystemMap->set($domain, $filesystem);
        }

        $this->setDefaultDomain($default);
    }

    /**
     * After loading the entity load file if any.
     *
     * @param \Doctrine\Common\EventArgs $eventArgs
     */
    public function postLoad(EventArgs $eventArgs)
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
     * Check and eventually update files keys.
     *
     * @param \Doctrine\ORM\Event\PreFlushEventArgs $eventArgs
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $object) {
            $class = get_class($object);
            $uploadableMeta = $this->getExtendedMetadata($entityManager, $class);
            if (!$uploadableMeta->hasUploadableProperties()) {
                continue;
            }
            $meta = $entityManager->getClassMetadata($class);
            $this->updateFiles($entityManager, $meta, $uploadableMeta, $object);
        }

        foreach ($unitOfWork->getIdentityMap() as $class => $entities) {
            $uploadableMeta = $this->getExtendedMetadata($entityManager, $class);
            if (!$uploadableMeta->hasUploadableProperties()) {
                continue;
            }
            $meta = $entityManager->getClassMetadata($class);
            foreach ($entities as $object) {
                if ($object instanceof Proxy) {
                    continue;
                }
                $this->updateFiles($entityManager, $meta, $uploadableMeta, $object);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritDoc}
     */
    protected function validateExtendedMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
    }

    /**
     * Load object files and attach observers for key fields.
     *
     * @param object $object
     * @param \FSi\DoctrineExtensions\Uploadable\Mapping\UploadableListener $uploadableMeta
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     */
    private function loadFiles($object, $uploadableMeta, $objectManager)
    {
        $propertyObserver = $this->getPropertyObserver($objectManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            // File key.
            $reflection = new \ReflectionProperty($object, $property);
            $reflection->setAccessible(true);
            $key = $reflection->getValue($object);

            // Injecting file.
            if (!empty($key)) {
                $domain = $this->getDomain($config);
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
     * @param $meta
     * @param $uploadableMeta
     * @param $object
     * @throws Exception\RuntimeException
     */
    protected function updateFiles(ObjectManager $objectManager, $meta, $uploadableMeta, $object)
    {
        $propertyObserver = $this->getPropertyObserver($objectManager);

        $key = array();
        foreach ($meta->identifier as $keyField) {
            $reflection = new \ReflectionProperty($object, $keyField);
            $reflection->setAccessible(true);
            $key[] = $reflection->getValue($object);
        }
        $key = implode('-', $key);

        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            if (!$propertyObserver->hasSavedValue($object, $config['targetField']) || $propertyObserver->hasValueChanged($object, $config['targetField'])) {
                $file = PropertyAccess::getPropertyAccessor()->getValue($object, $config['targetField']);
                // Save its current value, so if another fetch would be called, there wouldn't be another saving.
                $propertyObserver->saveValue($object, $config['targetField']);
                $reflection = new \ReflectionProperty($object, $property);
                $reflection->setAccessible(true);

                if (empty($file)) {
                    $reflection->setValue($object, null);
                    continue;
                }
                $domain = $this->getDomain($config);
                $keymaker = $this->getKeymaker($config);

                if ($file instanceof File) {
                    if ($domain !== $this->filesystemMap->seek($file->getFilesystem())) {
                        $newKey = $keymaker->createKey($object, $property, $key, $file->getName());
                        $file = File::fetchFrom($file, $newKey, $this->filesystemMap->get($domain));
                    }
                    $reflection->setValue($object, $file->getKey());
                } elseif ($file instanceof \SplFileInfo) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->getClientOriginalName();
                    } else {
                        $path = $file->getRealPath();
                    }
                    $newKey = $keymaker->createKey($object, $property, $key, $path);
                    $file = File::fromLocalFile($file, $newKey, $this->filesystemMap->get($domain));
                    $reflection->setValue($object, $file->getKey());
                } else {
                    throw new Exception\RuntimeException(sprintf('Can\'t handle resource of type "%s".', is_object($file) ? get_class($file) : gettype($file)));
                }
            }
        }
    }

    /**
     * Set default filesystem domain.
     *
     * @param string $domain
     */
    public function setDefaultDomain($domain = null)
    {
        $this->defaultDomain = $domain;
    }

    /**
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->defaultDomain;
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
     * @param array $config
     * @return string
     */
    protected function getDomain(array $config)
    {
        return !empty($config['domain']) ? $config['domain'] : $this->getDefaultDomain();
    }

    /**
     * Get strategy for creating keys.
     *
     * @param array $config
     * @return Keymaker\KeymakerInterface
     */
    protected function getKeymaker($config)
    {
        if (isset($config['keymaker']) and !empty($config['keymaker'])) {
            return $config['keymaker'];
        }

        return new Keymaker\Entity();
    }
}
