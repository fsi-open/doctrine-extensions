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

        $qb = $this->createQueryBuilder($alias);

        if ($listener->getLocale()) {
            foreach ($translatableMeta->getTranslatableProperties() as $translation => $properties) {
                /* @var \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata $translationMeta */
                $translationMeta = $listener->getExtendedMetadata(
                    $this->getEntityManager(),
                    $this->getClassMetadata()->getAssociationTargetClass($translation)
                );

                $qb->leftJoin(
                    sprintf('%s.%s', $alias, $translation),
                    $translationAlias,
                    Expr\Join::WITH,
                    sprintf('%s.%s = :locale', $translationAlias, $translationMeta->localeProperty)
                );
            }

            $qb->setParameter('locale', $listener->getLocale());
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
        if ( ! ($object instanceof $className)) {
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
            $this->getEntityManager(),
            $this->getClassMetadata()->getAssociationTargetClass($translationAssociation)
        );

        $translationMeta = $this->getEntityManager()->getClassMetadata(
            $this->getClassMetadata()->getAssociationTargetClass($translationAssociation)
        );

        $translationAssociationMapping = $this->getClassMetadata()->getAssociationMapping($translationAssociation);

        $translations = $this->getClassMetadata()->getFieldValue($object, $translationAssociation);
        if ( ! ($translations instanceof Collection)) {
            throw new RuntimeException(sprintf('Entity %s must contains implementation of "Doctrine\Common\Collections\Collection" in "%s" assotiation', $className, $translationAssociation));
        }

        if (isset($translationAssociationMapping['indexBy']) &&
            ($translationAssociationMapping['indexBy'] == $translationExtendedMeta->localeProperty)) {
            // preferred way of indexing translations collection by translation's locale
            if (!$translations->containsKey($locale)) {
                $translation = $this->createNewTranslation($translationMeta,
                    $translationAssociationMapping['mappedBy'],
                    $object,
                    $translationExtendedMeta->localeProperty,
                    $locale);
                $translations->set($locale, $translation);
            }

            return $translations->get($locale);
        } else {
            // fallback method when translations collection is not indexed by translation's locale
            $translation = null;
            foreach ($translations as $trans) {
                if ($translationMeta->getFieldValue($translation, $translationExtendedMeta->localeProperty) == $locale) {
                    $translation = $trans;
                    break;
                }
            }

            if (!isset($translation)) {
                $translation = $this->createNewTranslation($translationMeta,
                    $translationAssociationMapping['mappedBy'],
                    $object,
                    $translationExtendedMeta->localeProperty,
                    $locale);
                $translations->add($translation);
            }

            return $translation;
        }
    }

    protected function createNewTranslation(ClassMetadata $translationMeta, $objectProperty, $object, $localeProperty, $locale)
    {
        $translation = $translationMeta->newInstance();
        $translationMeta->setFieldValue($translation, $objectProperty, $object);
        $translationMeta->setFieldValue($translation, $localeProperty, $locale);
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