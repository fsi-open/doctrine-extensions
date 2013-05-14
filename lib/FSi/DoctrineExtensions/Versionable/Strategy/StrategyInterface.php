<?php
namespace FSi\DoctrineExtensions\Versionable\Strategy;

use Doctrine\Common\Persistence\ObjectManager;
use FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata as VersionableClassMetadata;

interface StrategyInterface
{
    /**
     * Constructrs strategy object
     *
     * @param ObjectManager $objectManager
     * @param \FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata $versionableMeta
     * @param \FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata $versionExtendedMeta
     * @param string $class
     */
    public function __construct(ObjectManager $objectManager, VersionableClassMetadata $versionableMeta, VersionableClassMetadata $versionExtendedMeta);

    /**
     * Returns version object which should be updated or null if new version should be created
     *
     * @param object $object
     * @param object $loadedVersion
     */
    public function getVersionToUpdate($object, $loadedVersion);

    /**
     * Prepare new version object before setting its fields
     *
     * @param object $object
     * @param object $loadedVersion
     */
    public function prepareNewVersion($object, $newVersion);

    /**
     * Method called when current version has changed
     *
     * @param object $object
     * @param integer $oldVersionNumber
     */
    public function currentVersionChanged($object, $oldVersionNumber);
}
