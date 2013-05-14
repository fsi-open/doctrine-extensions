<?php
namespace FSi\DoctrineExtensions\Versionable\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use FSi\Component\Reflection\ReflectionProperty;
use FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata as VersionableClassMetadata;
use FSi\DoctrineExtensions\Versionable\VersionableException;

class ArchiveStrategy extends AbstractStrategy
{
    const STATUS_PUBLISHED = 1;
    const STATUS_ARCHIVE   = 2;
    const STATUS_DRAFT     = 3;

    /**
     * {@inheritdoc}
     */
    public function __construct(ObjectManager $objectManager, VersionableClassMetadata $versionableMeta, VersionableClassMetadata $versionExtendedMeta)
    {
        if (!isset($versionExtendedMeta->statusProperty)) {
            throw new VersionableException('ArchiveStrategy requires status field to be defined in version entity "'.$versionExtendedMeta->getClassName().'"');
        }
        parent::__construct($objectManager, $versionableMeta, $versionExtendedMeta);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionToUpdate($object, $loadedVersion)
    {
        if (!isset($this->versionExtendedMeta->statusProperty)) {
            throw new VersionableException("Version's status field must be defined and it must be a persistent field");
        }
        $loadedStatusProperty = $this->getVersionableProperty($this->versionExtendedMeta->statusProperty);
        if (!isset($loadedStatusProperty)) {
            throw new VersionableException("Version's status field must be marked with @Versionable annotation in class '".$this->classMetadata->name."'");
        }
        $loadedVersionProperty = $this->getVersionableProperty($this->versionExtendedMeta->versionProperty);
        if (!isset($loadedVersionProperty))
            throw new VersionableException("Version's version field must be marked with @Versionable annotation in class '".$this->classMetadata->name."'");
        $loadedVersionNumber = ReflectionProperty::factory($this->classMetadata->name, $loadedVersionProperty)->getValue($object);
        if (!isset($loadedVersionNumber))
            return null;
        else
            return $loadedVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareNewVersion($object, $newVersion)
    {
        $newStatus = $this->versionClassMetadata->getFieldValue($newVersion, $this->versionExtendedMeta->statusProperty);
        if (!isset($newStatus)) {
            $this->versionClassMetadata->setFieldValue($newVersion, $this->versionExtendedMeta->statusProperty, self::STATUS_DRAFT);
            ReflectionProperty::factory($this->classMetadata->name, $this->getVersionableProperty($this->versionExtendedMeta->statusProperty))
                ->setValue($object, self::STATUS_DRAFT);
        } else if (!in_array($newStatus, array(self::STATUS_CURRENT, self::STATUS_ARCHIVE, self::STATUS_DRAFT))) {
            throw new VersionableException("Version\'s status must be one of STATUS_CURRENT, STATUS_ARCHIVE and STATUS_DRAFT");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function currentVersionChanged($object, $oldVersionNumber)
    {
        $versions = $this->classMetadata->getFieldValue($object, $this->versionableMeta->versionAssociation);
        $oldVersion = $this->findVersion($versions, $oldVersionNumber);
        $versionStatusField = $this->versionExtendedMeta->statusProperty;
        $versionNumberField = $this->versionExtendedMeta->versionProperty;
        if (isset($oldVersion)) {
            $this->versionClassMetadata->setFieldValue($oldVersion, $versionStatusField, self::STATUS_ARCHIVE);
            // if $oldVersion is currently loaded then update version status field in versionable object too
            if ($this->versionClassMetadata->getFieldValue($oldVersion, $versionNumberField) ==
                ReflectionProperty::factory($this->classMetadata->name, $this->getVersionableProperty($versionNumberField))
                    ->getValue($object))
                ReflectionProperty::factory($this->classMetadata->name, $this->getVersionableProperty($versionStatusField))
                    ->setValue($object, self::STATUS_ARCHIVE);
        }
        $newVersion = $this->findVersion($versions, $this->classMetadata->getFieldValue($object, $this->versionableMeta->versionProperty));
        if (isset($newVersion)) {
            $this->versionClassMetadata->setFieldValue($newVersion, $versionStatusField, self::STATUS_PUBLISHED);
            // if $newVersion is currently loaded then update version status field in versionable object too
            if ($this->versionClassMetadata->getFieldValue($newVersion, $versionNumberField) ==
                ReflectionProperty::factory($this->classMetadata->name, $this->getVersionableProperty($versionNumberField))
                    ->getValue($object))
                ReflectionProperty::factory($this->classMetadata->name, $this->getVersionableProperty($versionStatusField))
                    ->setValue($object, self::STATUS_PUBLISHED);
        }
    }

    /**
     * Find version entity by specified version number using filter method from ArrayCollection class
     *
     * @param Collection $versions
     * @param integer $versionNumber
     *
     * @return Collection
     */
    private function findVersion(Collection $versions, $versionNumber)
    {
        $versionClassMetadata = $this->versionClassMetadata;
        $versionNumberField = $this->versionExtendedMeta->versionProperty;
        $versions = $versions->filter(function($version) use ($versionNumber, $versionClassMetadata, $versionNumberField) {
            $currentVersionNumber = $versionClassMetadata->getFieldValue($version, $versionNumberField);
            if ($versionNumber === $currentVersionNumber)
                return true;
            else
                return false;
        });

        if (!$versions->count())
            return null;
        else if ($versions->count() > 1)
            throw new TranslatableException('Multiple versions with the same number has been found!');
        else
            return $versions->first();
    }
}
