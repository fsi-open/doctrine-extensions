<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Proxy\Proxy;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\PropertyManipulator;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use FSi\DoctrineExtensions\Uploadable\Mapping\ClassMetadata as UploadableClassMetadata;
use Gaufrette\Filesystem;
use Gaufrette\FilesystemMap;
use InvalidArgumentException;

class UploadableListener extends MappedEventSubscriber
{
    /**
     * Default key length.
     */
    public const KEY_LENGTH = 255;

    /**
     * @var Filesystem[]
     */
    protected $filesystems = [];

    /**
     * @var Filesystem
     */
    protected $defaultFilesystem;

    /**
     * @var FileHandlerInterface
     */
    protected $fileHandler;

    /**
     * @var PropertyManipulator[]
     */
    protected $propertyManipulators = [];

    /**
     * @var integer
     */
    protected $defaultKeyLength = self::KEY_LENGTH;

    /**
     * @var KeymakerInterface
     */
    protected $defaultKeymaker;

    /**
     * @var array
     */
    protected $toDelete = [];

    /**
     * @param FilesystemMap[]|FilesystemMap $filesystems
     */
    public function __construct($filesystems, FileHandlerInterface $fileHandler)
    {
        $this->setFilesystems($filesystems);
        $this->setFileHandler($fileHandler);
    }

    /**
     * @param FilesystemMap[]|FilesystemMap $filesystems
     * @throws RuntimeException
     */
    public function setFilesystems($filesystems)
    {
        if (!is_array($filesystems) && !($filesystems instanceof FilesystemMap)) {
            throw new RuntimeException(sprintf(
                'Option "filesystems" must be an array or "%s", "%s" given.',
                FilesystemMap::class,
                is_object($filesystems) ? get_class($filesystems) : gettype($filesystems)
            ));
        }

        if ($filesystems instanceof FilesystemMap) {
            $this->filesystems = $filesystems->all();
        } else {
            $this->filesystems = [];
            foreach ($filesystems as $id => $filesystem) {
                $this->setFilesystem($id, $filesystem);
            }
        }
    }

    public function setFilesystem(string $id, Filesystem $filesystem): void
    {
        $this->filesystems[$id] = $filesystem;
    }

    public function removeFilesystem(string $id): void
    {
        unset($this->filesystems[$id]);
    }

    /**
     * @return Filesystem[]
     */
    public function getFilesystems(): array
    {
        return $this->filesystems;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return ['preFlush', 'postLoad', 'postPersist', 'postFlush', 'postRemove'];
    }

    public function setDefaultFilesystem(Filesystem $filesystem): void
    {
        $this->defaultFilesystem = $filesystem;
    }

    public function hasDefaultFilesystem(): bool
    {
        return isset($this->defaultFilesystem);
    }

    /**
     * @throws RuntimeException
     */
    public function getDefaultFilesystem(): Filesystem
    {
        if (!$this->hasDefaultFilesystem()) {
            throw new RuntimeException('There\'s no default filesystem set.');
        }

        return $this->defaultFilesystem;
    }

    public function hasFilesystem(string $id): bool
    {
        return isset($this->filesystems[$id]);
    }

    /**
     * @throws RuntimeException
     */
    public function getFilesystem(string $id): Filesystem
    {
        if (!$this->hasFilesystem($id)) {
            throw new RuntimeException(sprintf('There is no filesystem for id "%s".', $id));
        }

        return $this->filesystems[$id];
    }

    /**
     * After loading the entity load file if any.
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $object = $eventArgs->getEntity();
        $uploadableMeta = $this->getObjectExtendedMetadata($entityManager, $object);

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->loadFiles($entityManager, $object, $uploadableMeta);
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $object = $eventArgs->getEntity();
        $meta = $entityManager->getClassMetadata(get_class($object));
        $uploadableMeta = $this->getExtendedMetadata($entityManager, $meta->name);
        if (!($uploadableMeta instanceof UploadableClassMetadata)) {
            throw new InvalidArgumentException(sprintf(
                'Expected class metadata "%s" got "%s"',
                UploadableClassMetadata::class,
                get_class($uploadableMeta)
            ));
        }

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->updateFiles($entityManager, $object, $uploadableMeta);
            $uow = $entityManager->getUnitOfWork();
            $uow->computeChangeSet($meta, $object);
        }
    }

    /**
     * Check and eventually update files keys.
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $object) {
                $uploadableMeta = $this->getObjectExtendedMetadata($entityManager, $object);
                if (!$uploadableMeta->hasUploadableProperties()) {
                    continue;
                }
                $this->updateFiles($entityManager, $object, $uploadableMeta);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        foreach ($this->toDelete as $file) {
            if ($file->exists()) {
                $file->delete();
            }
        }
    }

    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $object = $eventArgs->getEntity();
        $uploadableMeta = $this->getObjectExtendedMetadata($entityManager, $object);

        if ($uploadableMeta->hasUploadableProperties()) {
            $this->deleteFiles($entityManager, $object, $uploadableMeta);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function setDefaultKeyLength(int $length): void
    {
        if ($length < 1) {
            throw new RuntimeException(sprintf('Key length must be greater than "%d"', $length));
        }

        $this->defaultKeyLength = $length;
    }

    public function getDefaultKeyLength(): int
    {
        return $this->defaultKeyLength;
    }

    /**
     * @throws RuntimeException
     */
    public function setDefaultKeymaker(KeymakerInterface $keymaker): void
    {
        $this->defaultKeymaker = $keymaker;
    }

    public function hasDefaultKeymaker(): bool
    {
        return isset($this->defaultKeymaker);
    }

    /**
     * @throws RuntimeException
     */
    public function getDefaultKeymaker(): KeymakerInterface
    {
        if (!$this->hasDefaultKeymaker()) {
            throw new RuntimeException('There is no default keymaker set.');
        }

        return $this->defaultKeymaker;
    }

    public function setFileHandler(Filehandler\FileHandlerInterface $fileHandler): void
    {
        $this->fileHandler = $fileHandler;
    }

    public function getFileHandler(): FileHandlerInterface
    {
        return $this->fileHandler;
    }

    /**
     * Load object files and attach observers for key fields.
     *
     * @param object $object
     */
    protected function loadFiles(
        EntityManagerInterface $entityManager,
        $object,
        UploadableClassMetadata $uploadableMeta
    ): void {
        $this->assertIsObject($object);
        $propertyManipulator = $this->getPropertyManipulator($entityManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            $key = $propertyManipulator->getPropertyValue($object, $property);

            if (!empty($key)) {
                $propertyManipulator->setAndSaveValue(
                    $object,
                    $config['targetField'],
                    new File($key, $this->computeFilesystem($config))
                );
            }
        }
    }

    /**
     * @param object $object
     * @throws RuntimeException
     */
    protected function updateFiles(
        EntityManagerInterface $entityManager,
        $object,
        UploadableClassMetadata $uploadableMeta
    ) {
        $this->assertIsObject($object);
        if ($object instanceof Proxy) {
            $object->__load();
        }

        $id = implode('-', $this->extractIdentifier($entityManager, $object));
        $propertyManipulator = $this->getPropertyManipulator($entityManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            if (!$propertyManipulator->hasSavedValue($object, $config['targetField'])
                || $propertyManipulator->hasChangedValue($object, $config['targetField'])
            ) {
                $file = $propertyManipulator->getPropertyValue($object, $config['targetField']);
                $filesystem = $this->computeFilesystem($config);

                // Since file has changed, the old one should be removed.
                if ($propertyManipulator->getPropertyValue($object, $property)) {
                    $oldFile = $propertyManipulator->getSavedValue($object, $config['targetField']);
                    if ($oldFile) {
                        $this->addToDelete($oldFile);
                    }
                }

                if (empty($file)) {
                    $propertyManipulator->setPropertyValue($object, $property, null);
                    $propertyManipulator->saveValue($object, $config['targetField']);
                    continue;
                }

                if (!$this->getFileHandler()->supports($file)) {
                    throw new RuntimeException(sprintf(
                        'Can\'t handle resource of type "%s".',
                        is_object($file) ? get_class($file) : gettype($file)
                    ));
                }

                $keymaker = $this->computeKeymaker($config);
                $keyLength = $this->computeKeyLength($config);
                $keyPattern = $config['keyPattern'] ? $config['keyPattern'] : null;
                $fileName = $this->getFileHandler()->getName($file);
                $newKey = $this->generateNewKey(
                    $keymaker,
                    $object,
                    $property,
                    $id,
                    $fileName,
                    $keyLength,
                    $keyPattern,
                    $filesystem
                );

                $newFile = new File($newKey, $filesystem);
                $newFile->setContent($this->getFileHandler()->getContent($file));
                $propertyManipulator->setPropertyValue($object, $property, $newFile->getKey());
                // Save its current value, so if another update will be called, there won't be another saving.
                $propertyManipulator->setAndSaveValue($object, $config['targetField'], $newFile);
            }
        }
    }

    /**
     * @param object $object
     * @throws RuntimeException
     */
    protected function deleteFiles(
        EntityManagerInterface $entityManager,
        $object,
        UploadableClassMetadata $uploadableMeta
    ): void {
        $propertyManipulator = $this->getPropertyManipulator($entityManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            $oldKey = $propertyManipulator->getPropertyValue($object, $property);
            if ($oldKey) {
                $this->addToDelete(new File($oldKey, $this->computeFilesystem($config)));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateExtendedMetadata(
        ClassMetadata $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void {
        if (!($extendedClassMetadata instanceof UploadableClassMetadata)) {
            throw new InvalidArgumentException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                UploadableClassMetadata::class,
                get_class($extendedClassMetadata)
            ));
        }

        foreach ($extendedClassMetadata->getUploadableProperties() as $field => $options) {
            $className = $baseClassMetadata->getName();
            if (empty($options['targetField'])) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" does not '
                    . 'have required "targetField" attribute, or attribute is empty.',
                    $field,
                    $className
                ));
            }

            if (!$this->propertyExistsInClassTree($className, $options['targetField'])) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" has "targetField"'
                    . ' set to "%s", which doesn\'t exist.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if ($baseClassMetadata->hasField($options['targetField'])) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" have "targetField"'
                    . ' that points at already mapped field ("%s").',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (!$baseClassMetadata->hasField($field)) {
                throw new MappingException(sprintf(
                    'Property "%s" of class "%s" have mapping "Uploadable" but isn\'t'
                    . ' mapped as Doctrine\'s column.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (!is_null($options['keyLength']) && !is_numeric($options['keyLength'])) {
                throw new MappingException(sprintf(
                    'Property "%s" of class "%s" have mapping "Uploadable" with key'
                    . ' length is not a number.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (!is_null($options['keyLength']) && $options['keyLength'] < 1) {
                throw new MappingException(sprintf(
                    'Property "%s" of class "%s" have mapping "Uploadable" with'
                    . ' key length less than 1.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (!is_null($options['keymaker']) && !$options['keymaker'] instanceof KeymakerInterface) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" does have '
                    . 'keymaker that isn\'t instance of expected "%s"'
                    . ' ("%s" given).',
                    $field,
                    $className,
                    KeymakerInterface::class,
                    is_object($options['keymaker']) ? get_class($options['keymaker']) : gettype($options['keymaker'])
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    protected function addToDelete(File $file): void
    {
        $this->toDelete[] = $file;
    }

    private function computeFilesystem(array $config): Filesystem
    {
        return !empty($config['filesystem'])
            ? $this->getFilesystem($config['filesystem'])
            : $this->getDefaultFilesystem()
        ;
    }

    private function computeKeymaker(array $config): KeymakerInterface
    {
        return !empty($config['keymaker']) ? $config['keymaker'] : $this->getDefaultKeymaker();
    }

    private function computeKeyLength(array $config): int
    {
        return !empty($config['keyLength']) ? $config['keyLength'] : $this->getDefaultKeyLength();
    }

    /**
     * Algorithm to transform names from name.txt to name_i.txt and name_i.txt into name_{i++}.txt
     * when given key already exists and can't be reused.
     *
     * @param object $object
     * @param int|string $id
     */
    private function generateNewKey(
        KeymakerInterface $keymaker,
        $object,
        string $property,
        $id,
        string $fileName,
        int $keyLength,
        ?string $keyPattern,
        Filesystem $filesystem
    ): ?string {
        while ($filesystem->has($newKey = $keymaker->createKey($object, $property, $id, $fileName, $keyPattern))) {
            $matches = [];
            $match = preg_match('/(.*)_(\d+)(\.[^\.]*)?$/', $fileName, $matches);
            if ($match) {
                $fileName = sprintf(
                    '%s_%s%s',
                    $matches[1],
                    strval($matches[2] + 1),
                    isset($matches[3]) ? $matches[3] : ''
                );
            } else {
                $fileParts = explode('.', $fileName);
                if (count($fileParts) > 1) {
                    $fileParts[count($fileParts)  - 2] .= '_1';
                    $fileName = implode('.', $fileParts);
                } else {
                    $fileName .= '_1';
                }
            }
        }

        if (mb_strlen($newKey) > $keyLength) {
            throw new RuntimeException(sprintf(
                'Generated key exceeded limit of %d characters (had %d characters).',
                $keyLength,
                mb_strlen($newKey)
            ));
        }

        return $newKey;
    }

    /**
     * Extracts identifiers from object or proxy.
     * @param object $object
     */
    private function extractIdentifier(EntityManagerInterface $em, $object): array
    {
        return $object instanceof Proxy
            ? $em->getUnitOfWork()->getEntityIdentifier($object)
            : $em->getClassMetadata(get_class($object))->getIdentifierValues($object)
        ;
    }

    private function propertyExistsInClassTree(string $class, string $property): bool
    {
        if (property_exists($class, $property)) {
            return true;
        }

        $parentClass = get_parent_class($class);
        if ($parentClass !== false) {
            return $this->propertyExistsInClassTree($parentClass, $property);
        }

        return false;
    }

    /**
     * Returns PropertyManipulator for specified ObjectManager
     */
    private function getPropertyManipulator(EntityManagerInterface $entityManager): PropertyManipulator
    {
        $oid = spl_object_hash($entityManager);
        if (!isset($this->propertyManipulators[$oid])) {
            $this->propertyManipulators[$oid] = new PropertyManipulator();
        }

        return $this->propertyManipulators[$oid];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertIsObject($object): void
    {
        if (is_object($object)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Expected an object, got "%s"',
            gettype($object)
        ));
    }
}
