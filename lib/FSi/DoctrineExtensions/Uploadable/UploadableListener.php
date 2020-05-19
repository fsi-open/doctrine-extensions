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
        if (false === is_array($filesystems) && false === $filesystems instanceof FilesystemMap) {
            throw new RuntimeException(sprintf(
                'Option "filesystems" must be an array or "%s", "%s" given.',
                FilesystemMap::class,
                is_object($filesystems) ? get_class($filesystems) : gettype($filesystems)
            ));
        }

        if (true === $filesystems instanceof FilesystemMap) {
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
     * @return Filesystem
     * @throws RuntimeException
     */
    public function getDefaultFilesystem(): Filesystem
    {
        if (false === $this->hasDefaultFilesystem()) {
            throw new RuntimeException('There\'s no default filesystem set.');
        }

        return $this->defaultFilesystem;
    }

    public function hasFilesystem(string $id): bool
    {
        return isset($this->filesystems[$id]);
    }

    /**
     * @param string $id
     * @return Filesystem
     * @throws RuntimeException
     */
    public function getFilesystem(string $id): Filesystem
    {
        if (false === $this->hasFilesystem($id)) {
            throw new RuntimeException(sprintf('There is no filesystem for id "%s".', $id));
        }

        return $this->filesystems[$id];
    }

    /**
     * After loading the entity load file if any.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $object = $eventArgs->getEntity();
        $uploadableMeta = $this->getObjectExtendedMetadata($entityManager, $object);

        if (true === $uploadableMeta->hasUploadableProperties()) {
            $this->loadFiles($entityManager, $object, $uploadableMeta);
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $object = $eventArgs->getEntity();
        $meta = $entityManager->getClassMetadata(get_class($object));
        $uploadableMeta = $this->getExtendedMetadata($entityManager, $meta->name);
        if (false === $uploadableMeta instanceof UploadableClassMetadata) {
            throw new InvalidArgumentException(sprintf(
                'Expected class metadata "%s" got "%s"',
                UploadableClassMetadata::class,
                get_class($uploadableMeta)
            ));
        }

        if (true === $uploadableMeta->hasUploadableProperties()) {
            $this->updateFiles($entityManager, $object, $uploadableMeta);
            $uow = $entityManager->getUnitOfWork();
            $uow->computeChangeSet($meta, $object);
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

        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $object) {
                $uploadableMeta = $this->getObjectExtendedMetadata($entityManager, $object);
                if (false === $uploadableMeta->hasUploadableProperties()) {
                    continue;
                }

                $this->updateFiles($entityManager, $object, $uploadableMeta);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        foreach ($this->toDelete as $file) {
            if (true === $file->exists()) {
                $file->delete();
            }
        }
    }

    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $object = $eventArgs->getEntity();
        $uploadableMeta = $this->getObjectExtendedMetadata($entityManager, $object);

        if (true === $uploadableMeta->hasUploadableProperties()) {
            $this->deleteFiles($entityManager, $object, $uploadableMeta);
        }
    }

    /**
     * @param int $length
     * @return void
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

    public function setDefaultKeymaker(KeymakerInterface $keymaker): void
    {
        $this->defaultKeymaker = $keymaker;
    }

    public function hasDefaultKeymaker(): bool
    {
        return $this->defaultKeymaker instanceof KeymakerInterface;
    }

    /**
     * @return KeymakerInterface
     * @throws RuntimeException
     */
    public function getDefaultKeymaker(): KeymakerInterface
    {
        if (false === $this->hasDefaultKeymaker()) {
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
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @param UploadableClassMetadata $uploadableMeta
     * @return void
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
            if (null === $key) {
                continue;
            }

            $propertyManipulator->setAndSaveValue(
                $object,
                $config['targetField'],
                new File($key, $this->computeFilesystem($config))
            );
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @param UploadableClassMetadata $uploadableMeta
     * @throws RuntimeException
     */
    protected function updateFiles(
        EntityManagerInterface $entityManager,
        $object,
        UploadableClassMetadata $uploadableMeta
    ): void {
        $this->assertIsObject($object);
        if (true === $object instanceof Proxy) {
            $object->__load();
        }

        $id = implode('-', $this->extractIdentifier($entityManager, $object));
        $propertyManipulator = $this->getPropertyManipulator($entityManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            if (false === $propertyManipulator->hasSavedValue($object, $config['targetField'])
                || true === $propertyManipulator->hasChangedValue($object, $config['targetField'])
            ) {
                $file = $propertyManipulator->getPropertyValue($object, $config['targetField']);
                $filesystem = $this->computeFilesystem($config);

                // Since file has changed, the old one should be removed.
                if (null !== $propertyManipulator->getPropertyValue($object, $property)) {
                    $oldFile = $propertyManipulator->getSavedValue($object, $config['targetField']);
                    if (null !== $oldFile) {
                        $this->addToDelete($oldFile);
                    }
                }

                if (null === $file) {
                    $propertyManipulator->setPropertyValue($object, $property, null);
                    $propertyManipulator->saveValue($object, $config['targetField']);
                    continue;
                }

                if (false === $this->getFileHandler()->supports($file)) {
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
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @param UploadableClassMetadata $uploadableMeta
     * @return void
     */
    protected function deleteFiles(
        EntityManagerInterface $entityManager,
        $object,
        UploadableClassMetadata $uploadableMeta
    ): void {
        $propertyManipulator = $this->getPropertyManipulator($entityManager);
        foreach ($uploadableMeta->getUploadableProperties() as $property => $config) {
            $oldKey = $propertyManipulator->getPropertyValue($object, $property);
            if (null !== $oldKey) {
                $this->addToDelete(new File($oldKey, $this->computeFilesystem($config)));
            }
        }
    }

    /**
     * @param ClassMetadata $baseClassMetadata
     * @param ClassMetadataInterface $extendedClassMetadata
     * @return void
     * @throws InvalidArgumentException
     * @throws MappingException
     */
    protected function validateExtendedMetadata(
        ClassMetadata $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void {
        if (false === $extendedClassMetadata instanceof UploadableClassMetadata) {
            throw new InvalidArgumentException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                UploadableClassMetadata::class,
                get_class($extendedClassMetadata)
            ));
        }

        foreach ($extendedClassMetadata->getUploadableProperties() as $field => $options) {
            $className = $baseClassMetadata->getName();
            if (false === $this->arrayKeyNotBlank('targetField', $options)) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" does not '
                    . 'have required "targetField" attribute, or attribute is empty.',
                    $field,
                    $className
                ));
            }

            if (false === $this->propertyExistsInClassTree($className, $options['targetField'])) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" has "targetField"'
                    . ' set to "%s", which doesn\'t exist.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (true === $baseClassMetadata->hasField($options['targetField'])) {
                throw new MappingException(sprintf(
                    'Mapping "Uploadable" in property "%s" of class "%s" have "targetField"'
                    . ' that points at already mapped field ("%s").',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (false === $baseClassMetadata->hasField($field)) {
                throw new MappingException(sprintf(
                    'Property "%s" of class "%s" have mapping "Uploadable" but isn\'t'
                    . ' mapped as Doctrine\'s column.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (null !== $options['keyLength'] && false === is_numeric($options['keyLength'])) {
                throw new MappingException(sprintf(
                    'Property "%s" of class "%s" have mapping "Uploadable" with key'
                    . ' length is not a number.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (null !== $options['keyLength'] && 1 > $options['keyLength']) {
                throw new MappingException(sprintf(
                    'Property "%s" of class "%s" have mapping "Uploadable" with'
                    . ' key length less than 1.',
                    $field,
                    $className,
                    $options['targetField']
                ));
            }

            if (null !== $options['keymaker'] && false === $options['keymaker'] instanceof KeymakerInterface) {
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
        return true === $this->arrayKeyNotBlank('filesystem', $config)
            ? $this->getFilesystem($config['filesystem'])
            : $this->getDefaultFilesystem()
        ;
    }

    private function computeKeymaker(array $config): KeymakerInterface
    {
        if (true === array_key_exists('keymaker', $config)
            && true === $config['keymaker'] instanceof KeymakerInterface
        ) {
            $keymaker = $config['keymaker'];
        } else {
            $keymaker = $this->getDefaultKeymaker();
        }

        return $keymaker;
    }

    private function computeKeyLength(array $config): int
    {
        $keyLength = $this->getDefaultKeyLength();
        if (true === $this->arrayKeyNotNull('keyLength', $config)) {
            if (0 < $config['keyLength']) {
                $keyLength = $config['keyLength'];
            }
        }

        return $keyLength;
    }

    /**
     * @param KeymakerInterface $keymaker
     * @param object $object
     * @param string $property
     * @param int|string $id
     * @param string $fileName
     * @param int $keyLength
     * @param string|null $keyPattern
     * @param Filesystem $filesystem
     * @return string|null
     * @throws RuntimeException
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
            if (1 === $match) {
                $fileName = sprintf(
                    '%s_%s%s',
                    $matches[1],
                    strval($matches[2] + 1),
                    true === $this->arrayKeyNotNull(3, $matches) ? $matches[3] : ''
                );
            } else {
                $fileParts = explode('.', $fileName);
                if (1 < count($fileParts)) {
                    $fileParts[count($fileParts)  - 2] .= '_1';
                    $fileName = implode('.', $fileParts);
                } else {
                    $fileName .= '_1';
                }
            }
        }

        if ($keyLength < mb_strlen($newKey)) {
            throw new RuntimeException(sprintf(
                'Generated key exceeded limit of %d characters (had %d characters).',
                $keyLength,
                mb_strlen($newKey)
            ));
        }

        return $newKey;
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $object
     * @return array
     */
    private function extractIdentifier(EntityManagerInterface $em, $object): array
    {
        return true === $object instanceof Proxy
            ? $em->getUnitOfWork()->getEntityIdentifier($object)
            : $em->getClassMetadata(get_class($object))->getIdentifierValues($object)
        ;
    }

    private function propertyExistsInClassTree(string $class, string $property): bool
    {
        if (true === property_exists($class, $property)) {
            return true;
        }

        $parentClass = get_parent_class($class);
        if (false !== $parentClass) {
            return $this->propertyExistsInClassTree($parentClass, $property);
        }

        return false;
    }

    /**
     * Returns PropertyManipulator for specified ObjectManager
     *
     * @param EntityManagerInterface $entityManager
     * @return PropertyManipulator
     */
    private function getPropertyManipulator(EntityManagerInterface $entityManager): PropertyManipulator
    {
        $oid = spl_object_hash($entityManager);
        if (false === $this->arrayKeyNotNull($oid, $this->propertyManipulators)) {
            $this->propertyManipulators[$oid] = new PropertyManipulator();
        }

        return $this->propertyManipulators[$oid];
    }

    /**
     * @param mixed $object
     * @return void
     * @throws InvalidArgumentException
     */
    private function assertIsObject($object): void
    {
        if (false === is_object($object)) {
            throw new InvalidArgumentException(sprintf(
                'Expected an object, got "%s"',
                gettype($object)
            ));
        }
    }

    private function arrayKeyNotNull($key, array $array): bool
    {
        return true === array_key_exists($key, $array) && null !== $array[$key];
    }

    private function arrayKeyNotBlank($key, array $array): bool
    {
        return true === array_key_exists($key, $array)
            && null !== $array[$key]
            && '' !== $array[$key]
        ;
    }
}
