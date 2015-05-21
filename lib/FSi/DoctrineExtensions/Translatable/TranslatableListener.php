<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Proxy\Proxy;
use FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository;
use FSi\DoctrineExtensions\Translatable\Model\TranslatableRepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\Translatable\Exception;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;

class TranslatableListener extends MappedEventSubscriber
{
    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private $_propertyAccessor;

    /**
     * Current locale of the listener
     *
     * @var mixed
     */
    private $_currentLocale;

    /**
     * Default locale of the listener used when there is no translation in current locale
     *
     * @var mixed
     */
    private $_defaultLocale;

    /**
     * Set the current locale
     *
     * @param mixed $locale
     * @return \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    public function setLocale($locale)
    {
        $this->_currentLocale = $locale;
        return $this;
    }

    /**
     * Get the current locale
     *
     * @return mixed
     */
    public function getLocale()
    {
        return $this->_currentLocale;
    }

    /**
     * Set the default locale
     *
     * @param mixed $defaultLocale
     * @return \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->_defaultLocale = $defaultLocale;
        return $this;
    }

    /**
     * Get the default locale
     *
     * @return mixed
     */
    public function getDefaultLocale()
    {
        return $this->_defaultLocale;
    }

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'postHydrate',
            'preFlush'
        );
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
     * @param \Doctrine\Common\EventArgs $eventArgs
     */
    public function postLoad(EventArgs $eventArgs)
    {
        $this->loadTranslation(
            $this->getEventObjectManager($eventArgs),
            $this->getEventObject($eventArgs),
            $this->getLocale()
        );
    }

    /**
     * After loading the entity copy the current translation fields into non-persistent translatable properties
     *
     * @param \Doctrine\Common\EventArgs $eventArgs
     */
    public function postHydrate(EventArgs $eventArgs)
    {
        $this->postLoad($eventArgs);
    }

    /**
     * This event handler will update, insert or remove translation entities if main object's translatable properties change.
     *
     * @param \Doctrine\ORM\Event\PreFlushEventArgs $eventArgs
     * @return void
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork    = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $object) {
            $this->updateObjectTranslations($entityManager, $object);
        }

        foreach ($unitOfWork->getIdentityMap() as $class => $entities) {
            foreach ($entities as $object) {
                if ($object instanceof Proxy) {
                    continue;
                }
                $this->updateObjectTranslations($entityManager, $object);
            }
        }
    }

    /**
     * Load translations fields into object properties
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param mixed $locale
     */
    public function loadTranslation(ObjectManager $objectManager, $object, $locale)
    {
        $translatableMeta = $this->getTranslatableMetadata($objectManager, $object);
        if (!$translatableMeta->hasTranslatableProperties()) {
            return;
        }

        $translatableProperties = $translatableMeta->getTranslatableProperties();
        foreach ($translatableProperties as $translationAssociation => $properties) {
            $this->loadObjectTranslation($objectManager, $object, $translationAssociation, $locale);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function validateExtendedMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        if ($extendedClassMetadata->hasTranslatableProperties()) {
            $this->validateTranslatableLocaleProperty($baseClassMetadata, $extendedClassMetadata);
            $this->validateTranslatableProperties($baseClassMetadata, $extendedClassMetadata);
        } elseif (isset($extendedClassMetadata->localeProperty)) {
            $this->validateTranslationLocaleProperty($baseClassMetadata, $extendedClassMetadata);
        }
    }

    /**
     * @return \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!isset($this->_propertyAccessor)) {
            $this->_propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->_propertyAccessor;
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $baseClassMetadata
     * @param \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translatableClassMetadata
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\MappingException
     */
    private function validateTranslatableLocaleProperty(ClassMetadata $baseClassMetadata, TranslatableClassMetadata $translatableClassMetadata)
    {
        if (!isset($translatableClassMetadata->localeProperty)) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' has translatable properties so it must have property marked with @Translatable\\Language annotation",
                $baseClassMetadata->getName()
            ));
        }
        if ($baseClassMetadata->hasField($translatableClassMetadata->localeProperty) ||
            $baseClassMetadata->hasAssociation($translatableClassMetadata->localeProperty)) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' seems to be a translatable entity so its '%s' field must not be persistent",
                $baseClassMetadata->getName(),
                $translatableClassMetadata->localeProperty
            ));
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $baseClassMetadata
     * @param \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translatableClassMetadata
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\MappingException
     */
    private function validateTranslatableProperties(
        ClassMetadata $baseClassMetadata,
        TranslatableClassMetadata $translatableClassMetadata
    ) {
        $translatableProperties = $translatableClassMetadata->getTranslatableProperties();
        foreach ($translatableProperties as $translation => $properties) {
            if (!$baseClassMetadata->hasAssociation($translation) ||
                !$baseClassMetadata->isCollectionValuedAssociation($translation)) {
                throw new Exception\MappingException(sprintf(
                    "Field '%s' in entity '%s' has to be a OneToMany association",
                    $translation,
                    $baseClassMetadata->getName()
                ));
            }
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $baseClassMetadata
     * @param \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translatableClassMetadata
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\MappingException
     */
    private function validateTranslationLocaleProperty(ClassMetadata $baseClassMetadata, TranslatableClassMetadata $translatableClassMetadata)
    {
        if (!$baseClassMetadata->hasField($translatableClassMetadata->localeProperty) &&
            !$baseClassMetadata->hasAssociation($translatableClassMetadata->localeProperty)) {
            throw new Exception\MappingException(sprintf(
                "Entity '%s' seems to be a translation entity so its '%s' field must be persistent",
                $baseClassMetadata->getName(),
                $translatableClassMetadata->localeProperty
            ));
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     */
    private function loadObjectTranslation(ObjectManager $objectManager, $object, $translationAssociation, $locale)
    {
        if ($this->findAndLoadObjectTranslationByLocale($objectManager, $object, $translationAssociation, $locale)) {
            return;
        }

        if (isset($this->_defaultLocale) &&
            $this->findAndLoadObjectTranslationByLocale($objectManager, $object, $translationAssociation, $this->_defaultLocale)) {
            return;
        }

        $this->clearObjectProperties($objectManager, $object, $translationAssociation);
        $this->setObjectLocale($objectManager, $object, null);
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     * @return bool
     */
    private function findAndLoadObjectTranslationByLocale(ObjectManager $objectManager, $object, $translationAssociation, $locale)
    {
        $translation = $this->getRepository($objectManager, $object)
            ->findTranslation($object, $locale, $translationAssociation);

        if (!isset($translation)) {
            return false;
        }

        $this->copyTranslationFieldsToObjectProperties($objectManager, $object, $translationAssociation, $translation);
        $this->setObjectLocale($objectManager, $object, $locale);

        return true;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param object $translation
     */
    private function copyTranslationFieldsToObjectProperties(ObjectManager $objectManager, $object, $translationAssociation, $translation)
    {
        $translatableProperties = $this->getTranslatableMetadata($objectManager, $object)->getTranslatableProperties();

        foreach ($translatableProperties[$translationAssociation] as $property => $translationField) {
            $this->getPropertyAccessor()->setValue(
                $object,
                $property,
                $this->getPropertyAccessor()->getValue($translation, $translationField)
            );
        }
    }

    /**
     * Helper method to insert, remove or update translations entities associated with specified object
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     */
    private function updateObjectTranslations(ObjectManager $objectManager, $object)
    {
        $translatableMeta = $this->getTranslatableMetadata($objectManager, $object);
        if (!$translatableMeta->hasTranslatableProperties()) {
            return;
        }

        $translatableProperties = $translatableMeta->getTranslatableProperties();
        foreach ($translatableProperties as $translationAssociation => $properties) {
            $this->updateObjectTranslation(
                $objectManager,
                $object,
                $translationAssociation
            );
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     */
    private function updateObjectTranslation(ObjectManager $objectManager, $object, $translationAssociation)
    {
        $locale = $this->getObjectOrCurrentLocale($objectManager, $object, $translationAssociation);

        $translationToRemove = $this->getObjectTranslationToRemove($objectManager, $object, $translationAssociation, $locale);
        if ($translationToRemove) {
            $this->removeObjectTranslation($objectManager, $object, $translationAssociation, $translationToRemove);
            return;
        }

        if (!$this->hasObjectNotNullProperties($objectManager, $object, $translationAssociation)) {
            return;
        }
        $this->copyObjectPropertiesToTranslationFields($objectManager, $object, $translationAssociation, $locale);
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @return mixed
     */
    private function getObjectOrCurrentLocale(ObjectManager $objectManager, $object, $translationAssociation)
    {
        $locale = $this->getObjectLocale($objectManager, $object);
        if (!isset($locale)) {
            $locale = $this->getLocale();
        }

        $hasNotNullProperties = $this->hasObjectNotNullProperties($objectManager, $object, $translationAssociation);
        if (!isset($locale) && $hasNotNullProperties) {
            throw new Exception\RuntimeException(
                "Neither object's locale nor the current locale was set for translatable properties"
            );
        }

        return $locale;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     * @return object
     */
    private function getObjectTranslationToRemove(ObjectManager $objectManager, $object, $translationAssociation, $locale)
    {
        $translation = $this->getRepository($objectManager, $object)
            ->findTranslation($object, $locale, $translationAssociation);
        $objectLocale = $this->getObjectLocale($objectManager, $object);
        $hasNotNullProperties = $this->hasObjectNotNullProperties($objectManager, $object, $translationAssociation);
        if (!$hasNotNullProperties && isset($translation) && isset($objectLocale)) {
            return $translation;
        } else {
            return null;
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param object $currentTranslation
     */
    private function removeObjectTranslation(ObjectManager $objectManager, $object, $translationAssociation, $currentTranslation)
    {
        $objectManager->remove($currentTranslation);

        $translations = $this->getObjectTranslations($object, $translationAssociation);
        if ($translations->contains($currentTranslation)) {
            $translations->removeElement($currentTranslation);
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     */
    private function copyObjectPropertiesToTranslationFields(ObjectManager $objectManager, $object, $translationAssociation, $locale)
    {
        $translatableProperties = $this->getTranslatableMetadata($objectManager, $object)->getTranslatableProperties();
        foreach ($translatableProperties[$translationAssociation] as $property => $translationField) {
            $propertyValue = $this->getPropertyAccessor()->getValue($object, $property);

            $translation = $this->findOrCreateObjectTranslation(
                $objectManager,
                $object,
                $translationAssociation,
                $locale
            );

            if ($this->getPropertyAccessor()->getValue($translation, $translationField) !== $propertyValue) {
                $this->getPropertyAccessor()->setValue($translation, $translationField, $propertyValue);
            }
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     * @return object
     */
    private function findOrCreateObjectTranslation(ObjectManager $objectManager, $object, $translationAssociation, $locale)
    {
        $translation = $this->getRepository($objectManager, $object)
            ->getTranslation($object, $locale, $translationAssociation);

        if (!$objectManager->contains($translation)) {
            $objectManager->persist($translation);
        }

        return $translation;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @throws Exception\AnnotationException
     * @return TranslatableRepository
     */
    private function getRepository(ObjectManager $objectManager, $object)
    {
        $meta = $this->getObjectClassMetadata($objectManager, $object);
        $repository = $objectManager->getRepository($meta->getName());

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
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @return array
     */
    private function clearObjectProperties(ObjectManager $objectManager, $object, $translationAssociation)
    {
        $translatableProperties = $this->getTranslatableMetadata($objectManager, $object)->getTranslatableProperties();
        foreach ($translatableProperties[$translationAssociation] as $property => $translationField) {
            $this->getPropertyAccessor()->setValue($object, $property, null);
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $translationAssociation
     * @return bool
     */
    private function hasObjectNotNullProperties(ObjectManager $objectManager, $object, $translationAssociation)
    {
        $translatableProperties = $this
            ->getTranslatableMetadata($objectManager, $object)
            ->getTranslatableProperties();

        $hasNotNullProperties = false;
        foreach ($translatableProperties[$translationAssociation] as $property => $translationField) {
            if (null !== $this->getPropertyAccessor()->getValue($object, $property)) {
                $hasNotNullProperties = true;
            }
        }

        return $hasNotNullProperties;
    }

    /**
     * @param object $object
     * @param string $translationAssociation
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    private function getObjectTranslations($object, $translationAssociation)
    {
        $translations = $this->getPropertyAccessor()->getValue($object, $translationAssociation);
        if (!isset($translations)) {
            $translations = new ArrayCollection();
        }

        return $translations;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @return mixed
     */
    private function getObjectLocale(ObjectManager $objectManager, $object)
    {
        $localeProperty = $this->getTranslatableMetadata($objectManager, $object)->localeProperty;

        return $this->getPropertyAccessor()->getValue($object, $localeProperty);
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @param string $locale
     */
    private function setObjectLocale(ObjectManager $objectManager, $object, $locale)
    {
        $localeProperty = $this->getTranslatableMetadata($objectManager, $object)->localeProperty;

        $this->getPropertyAccessor()->setValue($object, $localeProperty, $locale);
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    private function getObjectClassMetadata(ObjectManager $objectManager, $object)
    {
        return $objectManager->getClassMetadata(get_class($object));
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param object $object
     * @return \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata
     */
    private function getTranslatableMetadata(ObjectManager $objectManager, $object)
    {
        $meta = $this->getObjectClassMetadata($objectManager, $object);

        return $this->getExtendedMetadata($objectManager, $meta->getName());
    }
}
