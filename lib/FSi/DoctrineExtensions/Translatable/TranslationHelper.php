<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use FSi\DoctrineExtensions\PropertyManipulator;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;
use InvalidArgumentException;

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
     * @return void
     */
    public function copyTranslationProperties(
        ClassTranslationContext $context,
        $object,
        $translation,
        string $locale
    ): void {
        $properties = array_flip($context->getAssociationMetadata()->getProperties());
        foreach ($properties as $sourceField => $targetField) {
            $sourceProperty = $this->propertyManipulator->getPropertyValue($translation, $sourceField);
            if ($sourceProperty instanceof Collection) {
                // We do not want the translation and translated object to share
                // the same Collection instance
                $sourceProperty = new ArrayCollection($sourceProperty->toArray());
            }
            $this->propertyManipulator->setPropertyValue(
                $object,
                $targetField,
                $sourceProperty
            );
        }
        $this->setObjectLocale($context->getTranslatableMetadata(), $object, $locale);
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @param string $defaultLocale
     * @return void
     */
    public function copyPropertiesToTranslation(
        ClassTranslationContext $context,
        $object,
        string $defaultLocale
    ): void {
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

        foreach ($translationAssociationMeta->getProperties() as $sourceField => $targetField) {
            $sourceProperty = $this->propertyManipulator->getPropertyValue($object, $sourceField);
            if ($context->getTranslationMetadata()->isCollectionValuedAssociation($targetField)) {
                $this->handleTranslationsCollection(
                    $context->getTranslationMetadata(),
                    $translation,
                    $targetField,
                    $sourceProperty
                );
            } else {
                $this->propertyManipulator->setPropertyValue(
                    $translation,
                    $targetField,
                    $sourceProperty
                );
            }
        }
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @return void
     */
    public function removeEmptyTranslation(ClassTranslationContext $context, $object): void
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
        $translation = $translatableRepository->findTranslation(
            $object,
            $objectLocale,
            $associationName
        );

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
     * @param \FSi\DoctrineExtensions\Translatable\ClassTranslationContext $context
     * @param object $object
     * @return void
     */
    public function clearTranslatableProperties(ClassTranslationContext $context, $object): void
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
     * @param \FSi\DoctrineExtensions\Translatable\ClassTranslationContext $context
     * @param object $object
     * @return bool
     */
    public function hasTranslatedProperties(ClassTranslationContext $context, $object): bool
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
     * @param \FSi\DoctrineExtensions\Translatable\ClassTranslationContext $context
     * @param object $object
     * @return string|null
     */
    public function getObjectLocale(ClassTranslationContext $context, $object): ?string
    {
        return $this->propertyManipulator->getPropertyValue(
            $object,
            $context->getTranslatableMetadata()->localeProperty
        );
    }

    /**
     * @param ClassMetadata $metadata
     * @param object $translation
     * @param string $collectionField
     * @param Collection|array $initialNewCollection
     */
    private function handleTranslationsCollection(
        ClassMetadata $metadata,
        $translation,
        string $collectionField,
        $initialNewCollection
    ): void {
        $newCollection = $this->transformArrayToCollection($initialNewCollection);

        /* @var $currentCollection Collection */
        $currentCollection = $this->propertyManipulator->getPropertyValue(
            $translation,
            $collectionField
        );

        $relationType = $metadata->getAssociationMapping($collectionField)['type'];
        $targetRelationField = $metadata->getAssociationMappedByTargetField($collectionField);
        // Remove elements from collection which are not in the new set
        foreach ($currentCollection as $currentElement) {
            if ($newCollection->contains($currentElement)) {
                continue;
            }

            $this->removeFromRelation(
                $relationType,
                $translation,
                $currentElement,
                $targetRelationField
            );
            $currentCollection->removeElement($currentElement);
        }

        // Add new elements to current collection
        foreach ($newCollection as $newElement) {
            if ($currentCollection->contains($newElement)) {
                continue;
            }

            $this->addToRelation(
                $relationType,
                $translation,
                $newElement,
                $targetRelationField
            );
            $currentCollection->add($newElement);
        }
    }

    /**
     * @param int $relationType
     * @param object $translation
     * @param Collection|array $collectionElement
     * @param string|bool $targetField
     * @return void
     */
    private function removeFromRelation(
        int $relationType,
        $translation,
        $collectionElement,
        $targetField
    ): void {
        if (!$targetField) {
            // one-sided relation, no property to set relation on
            return;
        }

        if ($relationType === ClassMetadata::MANY_TO_MANY) {
            /* @var $inversedCollection Collection */
            $inversedCollection = $this->propertyManipulator->getPropertyValue(
                $collectionElement,
                $targetField
            );
            $inversedCollection->removeElement($translation);
        } else {
            $this->propertyManipulator->setPropertyValue(
                $collectionElement,
                $targetField,
                null
            );
        }
    }

    /**
     * @param int $relationType
     * @param object $translation
     * @param object $collectionElement
     * @param string|boolean $targetField
     * @return void
     */
    private function addToRelation(
        int $relationType,
        $translation,
        $collectionElement,
        $targetField
    ): void {
        if (!$targetField) {
            // one-sided relation, no property to set relation on
            return;
        }

        if ($relationType === ClassMetadata::MANY_TO_MANY) {
            /* @var $inversedCollection Collection */
            $inversedCollection = $this->propertyManipulator->getPropertyValue(
                $collectionElement,
                $targetField
            );
            $inversedCollection->add($translation);
        } else {
            $this->propertyManipulator->setPropertyValue(
                $collectionElement,
                $targetField,
                $translation
            );
        }
    }

    /**
     * @param Collection|array $collection
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function transformArrayToCollection($collection): Collection
    {
        if ($collection instanceof Collection) {
            return $collection;
        }

        if (is_array($collection)) {
            return new ArrayCollection($collection);
        }

        throw new InvalidArgumentException(sprintf(
            'Expected an array or Collection, got "%s" instead',
            is_object($collection) ? get_class($collection) : gettype(($collection))
        ));
    }

    /**
     * @param TranslatableClassMetadata $classMetadata
     * @param object $object
     * @param string|null $locale
     * @return void
     */
    private function setObjectLocale(
        TranslatableClassMetadata $classMetadata,
        $object,
        ?string $locale
    ): void {
        $this->propertyManipulator->setPropertyValue($object, $classMetadata->localeProperty, $locale);
    }
}
