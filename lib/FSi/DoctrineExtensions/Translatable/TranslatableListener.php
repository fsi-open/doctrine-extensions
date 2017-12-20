<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Translatable\Exception;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;
use FSi\DoctrineExtensions\Translatable\Mapping\TranslationAssociationMetadata;
use InvalidArgumentException;

class TranslatableListener extends MappedEventSubscriber
{
    /**
     * @var string|null
     */
    private $currentLocale;

    /**
     * @var string|null
     */
    private $defaultLocale;

    /**
     * @var TranslationHelper
     */
    private $translationHelper;

    /**
     * @var ClassTranslationContext[]
     */
    private $classTranslationContexts;

    public function __construct()
    {
        $this->translationHelper = new TranslationHelper();
    }

    public function setLocale(?string $locale): void
    {
        $this->currentLocale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->currentLocale;
    }

    public function setDefaultLocale(?string $defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    public function getSubscribedEvents()
    {
        return ['postLoad', 'preFlush'];
    }

    /**
     * Get the namespace of extension event subscriber
     * used for cache id of extensions also to know where
     * to find Mapping drivers and event adapters
     */
    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * After loading the entity copy the current translation fields into non-persistent
     * translatable properties
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $this->loadTranslation(
            $eventArgs->getEntityManager(),
            $eventArgs->getEntity(),
            $this->getLocale()
        );
    }

    /**
     * This event handler will update, insert or remove translation entities if
     * main object's translatable properties change.
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $object) {
            $this->updateObjectTranslations($entityManager, $object);
        }

        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $object) {
                $this->updateObjectTranslations($entityManager, $object);
            }
        }
    }

    /**
     * @param object $object
     */
    public function loadTranslation(EntityManagerInterface $entityManager, $object, ?string $locale): void
    {
        $translatableMeta = $this->getTranslatableMetadata($entityManager, $object);
        if (!$translatableMeta->hasTranslatableProperties()) {
            return;
        }

        foreach ($translatableMeta->getTranslationAssociationMetadatas() as $associationMeta) {
            $context = $this->getTranslationContext($entityManager, $associationMeta, $object);
            $associationName = $associationMeta->getAssociationName();
            $repository = $context->getTranslatableRepository();
            $translation = $repository->findTranslation($object, $locale, $associationName);

            //default locale fallback
            if (!isset($translation) && isset($this->defaultLocale) && $this->defaultLocale !== $locale) {
                $locale = $this->defaultLocale;
                $translation = $repository->findTranslation($object, $this->defaultLocale, $associationName);
            }

            if (isset($translation)) {
                $this->translationHelper->copyTranslationProperties($context, $object, $translation, $locale);
            } else {
                $this->translationHelper->clearTranslatableProperties($context, $object);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function validateExtendedMetadata(
        ClassMetadata $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void {
        if (!($extendedClassMetadata instanceof TranslatableClassMetadata)) {
            throw new InvalidArgumentException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                TranslatableClassMetadata::class,
                get_class($extendedClassMetadata)
            ));
        }

        if ($extendedClassMetadata->hasTranslatableProperties()) {
            $this->validateTranslatableLocaleProperty($baseClassMetadata, $extendedClassMetadata);
            $this->validateTranslatableProperties($baseClassMetadata, $extendedClassMetadata);
        } elseif (isset($extendedClassMetadata->localeProperty)) {
            $this->validateTranslationLocaleProperty($baseClassMetadata, $extendedClassMetadata);
        }
    }

    /**
     * @throws Exception\MappingException
     */
    private function validateTranslatableLocaleProperty(
        ClassMetadata $baseClassMetadata,
        TranslatableClassMetadata $translatableClassMetadata
    ): void {
        if (!isset($translatableClassMetadata->localeProperty)) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' has translatable properties so it must have property"
                . " marked with @Translatable\\Language annotation",
                $baseClassMetadata->getName()
            ));
        }

        if ($baseClassMetadata->hasField($translatableClassMetadata->localeProperty)
            || $baseClassMetadata->hasAssociation($translatableClassMetadata->localeProperty)
        ) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' seems to be a translatable entity so its '%s' field must not be persistent",
                $baseClassMetadata->getName(),
                $translatableClassMetadata->localeProperty
            ));
        }
    }

    /**
     * @throws Exception\MappingException
     */
    private function validateTranslatableProperties(
        ClassMetadata $baseClassMetadata,
        TranslatableClassMetadata $translatableClassMetadata
    ): void {
        $translatableProperties = $translatableClassMetadata->getTranslatableProperties();
        foreach (array_keys($translatableProperties) as $translation) {
            if (!$baseClassMetadata->hasAssociation($translation)
                || !$baseClassMetadata->isCollectionValuedAssociation($translation)
            ) {
                throw new Exception\MappingException(sprintf(
                    "Field '%s' in entity '%s' has to be a OneToMany association",
                    $translation,
                    $baseClassMetadata->getName()
                ));
            }
        }
    }

    /**
     * @throws Exception\MappingException
     */
    private function validateTranslationLocaleProperty(
        ClassMetadata $baseClassMetadata,
        TranslatableClassMetadata $translatableClassMetadata
    ) {
        if (!$baseClassMetadata->hasField($translatableClassMetadata->localeProperty)
            && !$baseClassMetadata->hasAssociation($translatableClassMetadata->localeProperty)
        ) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' seems to be a translation entity so its '%s' field must be persistent",
                $baseClassMetadata->getName(),
                $translatableClassMetadata->localeProperty
            ));
        }
    }

    /**
     * Helper method to insert, remove or update translations entities associated
     * with specified object.
     *
     * @param object $object
     */
    private function updateObjectTranslations(EntityManagerInterface $entityManager, $object)
    {
        $translatableMeta = $this->getTranslatableMetadata($entityManager, $object);
        if (!$translatableMeta->hasTranslatableProperties()) {
            return;
        }

        foreach ($translatableMeta->getTranslationAssociationMetadatas() as $associationMeta) {
            $context = $this->getTranslationContext($entityManager, $associationMeta, $object);
            $locale = $this->translationHelper->getObjectLocale($context, $object);
            if (is_null($locale) || $locale === '') {
                $locale = $this->getLocale();
            }

            $hasTranslatedProperties = $this->translationHelper->hasTranslatedProperties($context, $object);
            if (!isset($locale) && $hasTranslatedProperties) {
                throw new Exception\RuntimeException(
                    "Neither object's locale nor the current locale was set for translatable properties"
                );
            }

            if ($hasTranslatedProperties) {
                $this->translationHelper->copyPropertiesToTranslation(
                    $context,
                    $object,
                    $locale
                );
            } else {
                $this->translationHelper->removeEmptyTranslation(
                    $context,
                    $object
                );
            }
        }
    }

    /**
     * @param object $object
     */
    private function getTranslationContext(
        EntityManagerInterface $entityManager,
        TranslationAssociationMetadata $associationMeta,
        $object
    ): ClassTranslationContext {
        $classMeta = $this->getObjectClassMetadata($entityManager, $object);
        $className = $classMeta->getName();
        $associationName = $associationMeta->getAssociationName();

        if (empty($this->classTranslationContexts[$className][$associationName])) {
            $context = new ClassTranslationContext($entityManager, $classMeta, $associationMeta);
            $this->classTranslationContexts[$className][$associationName] = $context;
        }

        return $this->classTranslationContexts[$className][$associationName];
    }

    /**
     * @param object $object
     */
    private function getObjectClassMetadata(
        EntityManagerInterface $entityManager,
        $object
    ): ClassMetadata {
        return $entityManager->getClassMetadata(get_class($object));
    }

    /**
     * @param object $object
     */
    private function getTranslatableMetadata(
        EntityManagerInterface $entityManager,
        $object
    ): TranslatableClassMetadata {
        $meta = $this->getObjectClassMetadata($entityManager, $object);
        return $this->getExtendedMetadata($entityManager, $meta->getName());
    }
}
