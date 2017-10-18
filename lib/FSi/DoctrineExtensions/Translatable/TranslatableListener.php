<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Translatable\Exception;
use FSi\DoctrineExtensions\Translatable\Exception\MappingException;
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
     * @var array
     */
    private $classTranslationContexts;

    public function __construct()
    {
        $this->translationHelper = new TranslationHelper();
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->currentLocale = $locale;
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        return $this->currentLocale;
    }

    /**
     * @param string $defaultLocale
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return string|null
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Specifies the list of events to listen
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'postLoad',
            'preFlush'
        ];
    }

    /**
     * Get the namespace of extension event subscriber
     * used for cache id of extensions also to know where
     * to find Mapping drivers and event adapters
     *
     * @return string
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * After loading the entity copy the current translation fields into non-persistent translatable properties
     *
     * @param LifecycleEventArgs $eventArgs
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
     * This event handler will update, insert or remove translation entities if main object's translatable properties change.
     *
     * @param PreFlushEventArgs $eventArgs
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
     * Load translations fields into object properties
     *
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @param string $locale
     */
    public function loadTranslation(EntityManagerInterface $entityManager, $object, $locale)
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
    protected function validateExtendedMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        if (!($extendedClassMetadata instanceof TranslatableClassMetadata)) {
            throw new InvalidArgumentException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                '\FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata',
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
     * @param ClassMetadata $baseClassMetadata
     * @param TranslatableClassMetadata $translatableClassMetadata
     * @throws MappingException
     */
    private function validateTranslatableLocaleProperty(
        ClassMetadata $baseClassMetadata,
        TranslatableClassMetadata $translatableClassMetadata
    ) {
        if (!isset($translatableClassMetadata->localeProperty)) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' has translatable properties so it must have property marked with @Translatable\\Language annotation",
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
     * @param ClassMetadata $baseClassMetadata
     * @param TranslatableClassMetadata $translatableClassMetadata
     * @throws MappingException
     */
    private function validateTranslatableProperties(
        ClassMetadata $baseClassMetadata,
        TranslatableClassMetadata $translatableClassMetadata
    ) {
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
     * @param ClassMetadata $baseClassMetadata
     * @param TranslatableClassMetadata $translatableClassMetadata
     * @throws MappingException
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
     * Helper method to insert, remove or update translations entities associated with specified object
     *
     * @param EntityManagerInterface $entityManager
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
     * @param EntityManagerInterface $entityManager
     * @param TranslationAssociationMetadata $associationMeta
     * @param object $object
     * @return ClassTranslationContext
     */
    private function getTranslationContext(
        EntityManagerInterface $entityManager,
        TranslationAssociationMetadata $associationMeta,
        $object
    ) {
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
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @return ClassMetadata
     */
    private function getObjectClassMetadata(EntityManagerInterface $entityManager, $object)
    {
        return $entityManager->getClassMetadata(get_class($object));
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param object $object
     * @return TranslatableClassMetadata
     */
    private function getTranslatableMetadata(EntityManagerInterface $entityManager, $object)
    {
        $meta = $this->getObjectClassMetadata($entityManager, $object);
        return $this->getExtendedMetadata($entityManager, $meta->getName());
    }
}
