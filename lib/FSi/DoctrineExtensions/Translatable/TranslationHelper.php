<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * @param ObjectAssociationContext $context
     * @param object $translation
     * @param string $locale
     */
    public function copyTranslationProperties(ObjectAssociationContext $context, $translation, $locale)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $object = $context->getObject();

        foreach ($context->getAssociationMetadata()->getProperties() as $targetField => $sourceField) {
            $value = $propertyAccessor->getValue($translation, $sourceField);
            $propertyAccessor->setValue($object, $targetField, $value);
        }

        $this->setObjectLocale($context->getTranslatableMetadata(), $object, $locale);
    }

    /**
     * @param ObjectAssociationContext $context
     * @param string $defaultLocale
     * @throws Exception\AnnotationException
     */
    public function copyPropertiesToTranslation(ObjectAssociationContext $context, $defaultLocale)
    {
        $object = $context->getObject();
        $translationAssociationMeta = $context->getAssociationMetadata();

        $locale = $context->getObjectLocale();
        if (!isset($locale)) {
            $locale = $defaultLocale;
        }

        $translation = $context->getTranslatableRepository()->getTranslation(
            $object,
            $locale,
            $translationAssociationMeta->getAssociationName()
        );

        $objectManager = $context->getObjectManager();
        if (!$objectManager->contains($translation)) {
            $objectManager->persist($translation);
        }

        $this->copyPropertiesIfDifferent($object, $translation, $translationAssociationMeta->getProperties());
    }

    /**
     * @param ObjectAssociationContext $context
     * @throws Exception\AnnotationException
     */
    public function removeEmptyTranslation(ObjectAssociationContext $context)
    {
        if ($this->hasTranslatedProperties($context)) {
            return;
        }

        $context->getTranslationClassMetadata();
        $object = $context->getObject();

        $objectLocale = $context->getObjectLocale();
        if (!isset($objectLocale)) {
            return;
        }

        $translationAssociationMeta = $context->getAssociationMetadata();
        $associationName = $translationAssociationMeta->getAssociationName();
        $translatableRepository = $context->getTranslatableRepository();
        $translation = $translatableRepository->findTranslation($object, $objectLocale, $associationName);

        if (!isset($translation)) {
            return;
        }

        $context->getObjectManager()->remove($translation);

        $translations = $translatableRepository->getTranslations($object, $associationName);
        if ($translations->contains($translation)) {
            $translations->removeElement($translation);
        }
    }

    /**
     * @param ObjectAssociationContext $context
     */
    public function clearTranslatableProperties(ObjectAssociationContext $context)
    {
        $object = $context->getObject();
        $propertyAccessor = $this->getPropertyAccessor();
        $translationMeta = $context->getTranslationClassMetadata();

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
     * @param ObjectAssociationContext $context
     * @return bool
     */
    public function hasTranslatedProperties(ObjectAssociationContext $context)
    {
        $object = $context->getObject();
        $properties = $context->getAssociationMetadata()->getProperties();
        $translationMeta = $context->getTranslationClassMetadata();
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
    private function copyPropertiesIfDifferent($source, $target, $properties)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($properties as $sourceField => $targetField) {
            $value = $propertyAccessor->getValue($source, $sourceField);
            if ($propertyAccessor->getValue($target, $targetField) !== $value) {
                $propertyAccessor->setValue($target, $targetField, $value);
            }
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
