<?php
namespace FSi\DoctrineExtensions\Versionable\Strategy;

use Doctrine\Common\Persistence\ObjectManager;
use FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata as VersionableClassMetadata;

abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetadata;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $versionClassMetadata;

    /**
     * @var \FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata
     */
    protected $versionableMeta;

    /**
     * @var \FSi\DoctrineExtensions\Versionable\Mapping\ClassMetadata
     */
    protected $versionExtendedMeta;

    /**
     * @var array
     */
    protected $reverseFields = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(ObjectManager $objectManager, VersionableClassMetadata $versionableMeta, VersionableClassMetadata $versionExtendedMeta)
    {
        $this->objectManager = $objectManager;
        $this->versionableMeta = $versionableMeta;
        $this->versionExtendedMeta = $versionExtendedMeta;
        $this->reverseFields = array_flip($this->versionableMeta->getVersionableProperties());
        $this->classMetadata = $this->objectManager->getClassMetadata($this->versionableMeta->getClassName());
        $this->versionClassMetadata = $this->objectManager->getClassMetadata($this->versionExtendedMeta->getClassName());
    }

    protected function getVersionableProperty($field)
    {
        return isset($this->reverseFields[$field])?$this->reverseFields[$field]:null;
    }
}
