<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use FSi\DoctrineExtensions\Translatable\Mapping\TranslationAssociationMetadata;
use FSi\DoctrineExtensions\Translatable\Model\TranslatableRepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ObjectAssociationContext
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var TranslationAssociationMetadata
     */
    private $associationMetadata;

    /**
     * @var object
     */
    private $object;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param ObjectManager $objectManager
     * @param TranslationAssociationMetadata $associationMetadata
     * @param object $object
     */
    public function __construct(
        ObjectManager $objectManager,
        TranslationAssociationMetadata $associationMetadata,
        $object
    ) {
        $this->objectManager = $objectManager;
        $this->associationMetadata = $associationMetadata;
        $this->object = $object;
    }

    /**
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @return TranslationAssociationMetadata
     */
    public function getAssociationMetadata()
    {
        return $this->associationMetadata;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->objectManager->getClassMetadata(get_class($this->object));
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function getTranslatableMetadata()
    {
        return $this->associationMetadata->getClassMetadata();
    }

    /**
     * @return ClassMetadata
     */
    public function getTranslationClassMetadata()
    {
        $meta = $this->getClassMetadata();
        $assocName = $this->associationMetadata->getAssociationName();

        return $this->objectManager->getClassMetadata($meta->getAssociationTargetClass($assocName));
    }

    /**
     * @return string
     */
    public function getObjectLocale()
    {
        $localeProperty = $this->getTranslatableMetadata()->localeProperty;
        return $this->getPropertyAccessor()->getValue($this->object, $localeProperty);
    }

    /**
     * @throws Exception\AnnotationException
     * @return TranslatableRepositoryInterface
     */
    public function getTranslatableRepository()
    {
        $meta = $this->getClassMetadata();
        $repository = $this->objectManager->getRepository($meta->getName());

        if (!($repository instanceof TranslatableRepositoryInterface)) {
            throw new Exception\AnnotationException(sprintf(
                'Entity "%s" has "%s" as its "repositoryClass" which does not implement \FSi\DoctrineExtensions\Translatable\Model\TranslatableRepositoryInterface',
                $meta->getName(),
                get_class($repository)
            ));
        }

        return $repository;
    }

    /**
     * @return \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!isset($this->propertyAccessor)) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
