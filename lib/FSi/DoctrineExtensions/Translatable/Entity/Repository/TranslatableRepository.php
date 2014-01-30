<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use FSi\DoctrineExtensions\ORM\QueryBuilder;
use FSi\DoctrineExtensions\Translatable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Translatable\TranslatableListener;

class TranslatableRepository extends EntityRepository
{
    /**
     * @var \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    protected $listener;

    /**
     * Creates query builder for this entity joined with associated translation
     * entity and constrained to current locale of TranslatableListener if it
     * has been set
     *
     * @param string $alias
     * @param string $translationAlias
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createTranslatableQueryBuilder($alias, $translationAlias = 't')
    {
        $listener = $this->getTranslatableListener();
        /* @var \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translatableMeta */
        $translatableMeta = $listener->getExtendedMetadata($this->getEntityManager(), $this->getClassName());

        $qb = new QueryBuilder($this->_em);
        $qb->select($alias)
            ->from($this->_entityName, $alias);

        foreach ($translatableMeta->getTranslatableProperties() as $translation => $properties) {
            $qb->leftJoin(
                sprintf('%s.%s', $alias, $translation),
                $translationAlias
            );
            break;
        }

        return $qb;
    }

    /**
     * Returns existing or newly created translation entity for specified base
     * entity and locale
     *
     * @param object $object
     * @param mixed $locale
     * @param string $translationAssociation
     * @return object existing or new translation entity for specified locale
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    public function getTranslation($object, $locale, $translationAssociation = 'translations')
    {
        $className = $this->getClassName();
        $translationClass = $this->getClassMetadata()->getAssociationTargetClass($translationAssociation);
        if (!($object instanceof $className)) {
            throw new RuntimeException(sprintf('Expected entity of class %s, but got %s', $className, get_class($object)));
        }

        $listener = $this->getTranslatableListener();
        /* @var \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translatableMeta */
        $translatableMeta = $listener->getExtendedMetadata($this->getEntityManager(), $this->getClassName());

        $translatableProperties = $translatableMeta->getTranslatableProperties();
        if (!isset($translatableProperties[$translationAssociation])) {
            throw new RuntimeException(sprintf('Entity %s has no translations association named %s', $className, $translationAssociation));
        }

        /* @var \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translationExtendedMeta */
        $translationExtendedMeta = $listener->getExtendedMetadata(
            $this->getEntityManager(), $translationClass
        );

        $translationMeta = $this->getEntityManager()->getClassMetadata($translationClass);

        $translationAssociationMapping = $this->getClassMetadata()->getAssociationMapping($translationAssociation);

        $translations = $this->getClassMetadata()->getFieldValue($object, $translationAssociation);
        if (!($translations instanceof Collection)) {
            throw new RuntimeException(sprintf('Entity %s must contains implementation of "Doctrine\Common\Collections\Collection" in "%s" assotiation', $className, $translationAssociation));
        }

        $translationsIndexed = (isset($translationAssociationMapping['indexBy']) &&
            ($translationAssociationMapping['indexBy'] == $translationExtendedMeta->localeProperty));

        $translation = null;

        if ($translationsIndexed) {
            if ($translations->containsKey($locale)) {
                $translation = $translations->get($locale);
            }
        } else {
            $translation = $this->findTranslation($translations, $locale, $translationMeta, $translationExtendedMeta);
        }

        if (!isset($translation)) {
            $translation = $this->createNewTranslation($translationMeta,
                $translationAssociationMapping['mappedBy'],
                $object,
                $translationExtendedMeta->localeProperty,
                $locale);
        }

        if ($translationsIndexed) {
            $translations->set($locale, $translation);
        } else {
            $translations->add($translation);
        }

        return $translation;
    }

    protected function createNewTranslation(ClassMetadata $translationMeta, $objectProperty, $object, $localeProperty, $locale)
    {
        $translation = $translationMeta->newInstance();
        $translationMeta->setFieldValue($translation, $objectProperty, $object);
        $translationMeta->setFieldValue($translation, $localeProperty, $locale);
        return $translation;
    }

    /**
     * @param string $locale
     * @param \Doctrine\Common\Collections\Collection $translations
     * @param \Doctrine\ORM\Mapping\ClassMetadata $translationMeta
     * @param \Doctrine\ORM\Mapping\ClassMetadata $translationExtendedMeta
     * @return object
     */
    protected function findTranslation($translations, $locale, $translationMeta, $translationExtendedMeta)
    {
        $translation = null;
        foreach ($translations as $trans) {
            if ($translationMeta->getFieldValue($trans, $translationExtendedMeta->localeProperty) == $locale) {
                $translation = $trans;
                break;
            }
        }
        return $translation;
    }

    /**
     * @return \FSi\DoctrineExtensions\Translatable\TranslatableListener
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function getTranslatableListener()
    {
        if (!isset($this->listener)) {
            $evm = $this->getEntityManager()->getEventManager();
            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof TranslatableListener) {
                        $this->listener = $listener;
                    }
                }
            }
        }

        if (isset($this->listener)) {
            return $this->listener;
        }

        throw new RuntimeException('Cannot find TranslatableListener in EntityManager\'s EventManager');
    }
}