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
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;
use FSi\DoctrineExtensions\Translatable\Model\TranslatableRepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @internal
 */
class TranslationHelper
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessor|null $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $translation
     * @param string $locale
     */
    public function copyTranslationProperties(ClassTranslationContext $context, $object, $translation, $locale)
    {
        $this->copyProperties($translation, $object, array_flip($context->getAssociationMetadata()->getProperties()));
        $this->setObjectLocale($context->getTranslatableMetadata(), $object, $locale);
    }

    /**
     * @param ClassTranslationContext $context
     * @param string $defaultLocale
     * @throws Exception\AnnotationException
     */
    public function copyPropertiesToTranslation(
        ObjectManager $objectManager,
        TranslatableRepositoryInterface $translatableRepository,
        ClassTranslationContext $context,
        $object,
        $defaultLocale
    ) {
        $translationAssociationMeta = $context->getAssociationMetadata();

        $locale = $this->getObjectLocale($context, $object);
        if (!isset($locale)) {
            $locale = $defaultLocale;
        }

        $translation = $translatableRepository->getTranslation(
            $object,
            $locale,
            $translationAssociationMeta->getAssociationName()
        );

        if (!$objectManager->contains($translation)) {
            $objectManager->persist($translation);
        }

        $this->copyProperties($object, $translation, $translationAssociationMeta->getProperties());
    }

    /**
     * @param ClassTranslationContext $context
     * @throws Exception\AnnotationException
     */
    public function removeEmptyTranslation(
        ObjectManager $objectManager,
        ClassMetadata $translationMeta,
        TranslatableRepositoryInterface $translatableRepository,
        ClassTranslationContext $context,
        $object
    ) {
        if ($this->hasTranslatedProperties($translationMeta, $context, $object)) {
            return;
        }

        $objectLocale = $this->getObjectLocale($context, $object);
        if (!isset($objectLocale)) {
            return;
        }

        $translationAssociationMeta = $context->getAssociationMetadata();
        $associationName = $translationAssociationMeta->getAssociationName();
        $translation = $translatableRepository->findTranslation($object, $objectLocale, $associationName);

        if (!isset($translation)) {
            return;
        }

        $objectManager->remove($translation);

        $translations = $translatableRepository->getTranslations($object, $associationName);
        if ($translations->contains($translation)) {
            $translations->removeElement($translation);
        }
    }

    /**
     * @param ClassTranslationContext $context
     */
    public function clearTranslatableProperties(ClassMetadata $translationMeta, ClassTranslationContext $context, $object)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($context->getAssociationMetadata()->getProperties() as $property => $translationField) {
            if ($translationMeta->isCollectionValuedAssociation($translationField)) {
                $propertyAccessor->setValue($object, $property, array());
            } else {
                $propertyAccessor->setValue($object, $property, null);
            }
        }

        $this->setObjectLocale($context->getTranslatableMetadata(), $object, null);
    }

    /**
     * @param ClassTranslationContext $context
     * @return bool
     */
    public function hasTranslatedProperties(ClassMetadata $translationMeta, ClassTranslationContext $context, $object)
    {
        $properties = $context->getAssociationMetadata()->getProperties();
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($properties as $property => $translationField) {
            $value = $propertyAccessor->getValue($object, $property);
            if ($translationMeta->isCollectionValuedAssociation($translationField) && count($value)) {
                return true;
            } elseif (!$translationMeta->isCollectionValuedAssociation($translationField) && (null !== $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassTranslationContext $context
     * @param $object
     * @return string
     */
    public function getObjectLocale(ClassTranslationContext $context, $object)
    {
        $localeProperty = $context->getTranslatableMetadata()->localeProperty;
        return $this->getPropertyAccessor()->getValue($object, $localeProperty);
    }

    /**
     * @param TranslatableClassMetadata $classMetadata
     * @param object $object
     * @param string $locale
     */
    private function setObjectLocale(TranslatableClassMetadata $classMetadata, $object, $locale)
    {
        $localeProperty = $classMetadata->localeProperty;
        $this->getPropertyAccessor()->setValue($object, $localeProperty, $locale);
    }

    /**
     * @param object $source
     * @param object $target
     * @param array $properties
     */
    private function copyProperties($source, $target, $properties)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($properties as $sourceField => $targetField) {
            $value = $propertyAccessor->getValue($source, $sourceField);
            $propertyAccessor->setValue($target, $targetField, $value);
        }
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
