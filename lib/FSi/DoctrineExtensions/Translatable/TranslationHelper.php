<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use FSi\DoctrineExtensions\PropertyManipulator;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;

/**
 * @internal
 */
class TranslationHelper
{
    /**
     * @var PropertyManipulator
     */
    private $propertyManipulator;

    public function __construct()
    {
        $this->propertyManipulator = new PropertyManipulator();
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @param object $translation
     * @param string $locale
     */
    public function copyTranslationProperties(ClassTranslationContext $context, $object, $translation, $locale)
    {
        $this->copyProperties(
            $translation,
            $object,
            array_flip($context->getAssociationMetadata()->getProperties())
        );
        $this->setObjectLocale($context->getTranslatableMetadata(), $object, $locale);
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @param string $defaultLocale
     */
    public function copyPropertiesToTranslation(
        ClassTranslationContext $context,
        $object,
        $defaultLocale
    ) {
        $translationAssociationMeta = $context->getAssociationMetadata();

        $locale = $this->getObjectLocale($context, $object);
        if (is_null($locale) || $locale === '') {
            $locale = $defaultLocale;
        }

        $translatableRepository = $context->getTranslatableRepository();
        $translation = $translatableRepository->getTranslation(
            $object,
            $locale,
            $translationAssociationMeta->getAssociationName()
        );

        $objectManager = $context->getObjectManager();
        if (!$objectManager->contains($translation)) {
            $objectManager->persist($translation);
        }

        $this->copyProperties(
            $object,
            $translation,
            $translationAssociationMeta->getProperties()
        );
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     */
    public function removeEmptyTranslation(ClassTranslationContext $context, $object)
    {
        if ($this->hasTranslatedProperties($context, $object)) {
            return;
        }

        $objectLocale = $this->getObjectLocale($context, $object);
        if (is_null($objectLocale) || $objectLocale === '') {
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
     * @param ClassTranslationContext $context
     * @param object $object
     */
    public function clearTranslatableProperties(ClassTranslationContext $context, $object)
    {
        $translationMeta = $context->getTranslationMetadata();
        foreach ($context->getAssociationMetadata()->getProperties() as $property => $translationField) {
            $clearValue = null;
            if ($translationMeta->isCollectionValuedAssociation($translationField)) {
                $clearValue = new ArrayCollection();
            }

            $this->propertyManipulator->setPropertyValue($object, $property, $clearValue);
        }

        $this->setObjectLocale($context->getTranslatableMetadata(), $object, null);
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @return bool
     */
    public function hasTranslatedProperties(ClassTranslationContext $context, $object)
    {
        $translationMeta = $context->getTranslationMetadata();
        $properties = $context->getAssociationMetadata()->getProperties();

        foreach ($properties as $property => $translationField) {
            $value = $this->propertyManipulator->getPropertyValue($object, $property);
            if ($translationMeta->isCollectionValuedAssociation($translationField)
                && count($value)
                || !$translationMeta->isCollectionValuedAssociation($translationField)
                && null !== $value
            ) {
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
        return $this->propertyManipulator->getPropertyValue(
            $object,
            $context->getTranslatableMetadata()->localeProperty
        );
    }

    /**
     * @param TranslatableClassMetadata $classMetadata
     * @param object $object
     * @param string $locale
     */
    private function setObjectLocale(TranslatableClassMetadata $classMetadata, $object, $locale)
    {
        $this->propertyManipulator->setPropertyValue($object, $classMetadata->localeProperty, $locale);
    }

    /**
     * @param object $source
     * @param object $target
     * @param array $properties
     */
    private function copyProperties($source, $target, $properties)
    {
        foreach ($properties as $sourceField => $targetField) {
            $sourceProperty = $this->propertyManipulator->getPropertyValue($source, $sourceField);
            if ($sourceProperty instanceof PersistentCollection) {
                $sourceProperty->initialize();
            }

            $this->propertyManipulator->setPropertyValue(
                $target,
                $targetField,
                $sourceProperty
            );
        }
    }
}
