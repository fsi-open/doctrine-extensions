<?php
namespace FSi\DoctrineExtensions\Versionable\Strategy;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use FSi\Component\Reflection\ReflectionProperty;
use FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata as VersionableClassMetadata;
use FSi\DoctrineExtensions\Versionable\VersionableException;

class SimpleStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ObjectManager $objectManager, VersionableClassMetadata $versionableMeta, VersionableClassMetadata $versionExtendedMeta)
    {
        if (isset($versionExtendedMeta->statusProperty)) {
            throw new VersionableException('SimpleStrategy does not support different version statuses');
        }
        parent::__construct($objectManager, $versionableMeta, $versionExtendedMeta);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionToUpdate($object, $loadedVersion)
    {
        $loadedVersionProperty = $this->getVersionableProperty($this->versionExtendedMeta->versionProperty);
        if (!isset($loadedVersionProperty)) {
            throw new VersionableException("Version's version field must be marked with @Versionable annotation");
        }
        $loadedVersionNumber = ReflectionProperty::factory($this->classMetadata->name, $loadedVersionProperty)->getValue($object);
        if (!isset($loadedVersionNumber)) {
            return null;
        } else {
            return $loadedVersion;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareNewVersion($object, $newVersion)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function currentVersionChanged($object, $oldVersionNumber)
    {

    }
}
