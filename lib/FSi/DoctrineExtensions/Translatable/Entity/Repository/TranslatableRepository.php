<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use FSi\DoctrineExtensions\ORM\QueryBuilder;
use FSi\DoctrineExtensions\Translatable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Translatable\TranslatableListener;
use FSi\DoctrineExtensions\Exception\ConditionException;

class TranslatableRepository extends EntityRepository
{
    /**
     * @var \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    protected $listener;

    /**
     * @var \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata
     */
    protected $extendedMetadata;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata[]
     */
    protected $translationMetadata;

    /**
     * @var \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata[]
     */
    protected $translationExtendedMetadata;

    public function findTranslatedOneBy(array $criteria, array $orderBy = null)
    {
        $translationCriteria = array();

        $translatableProperties = $this->getExtendedMetadata()->getTranslatableProperties();
        foreach ($translatableProperties as $translationAssociation => $translatedProperties) {
            foreach ($criteria as $criteriaField => $criteriaValue) {
                if (isset($translatedProperties[$criteriaField])) {
                    $translationCriteria[$translatedProperties[$criteriaField]] = $criteriaValue;
                    unset($criteria[$criteriaField]);
                }
            }
        }

        //find in translation
        $qb = $this->createTranslatableQueryBuilder('a', 't', 'dt');
        $this->addTranslationCriteria($qb, 't', $criteria, $translationCriteria, $orderBy);
        if ($result = $qb->getQuery()->getOneOrNullResult()) {
            return $result;
        }

        //fallback to default translation
        $qb = $this->createTranslatableQueryBuilder('a', 't', 'dt');
        $this->addTranslationCriteria($qb, 'dt', $criteria, $translationCriteria, $orderBy);
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Creates query builder for this entity joined with associated translation
     * entity and constrained to current locale of TranslatableListener if it
     * has been set. It also adds second join to translation entity constrained
     * to default locale of TranslatableListener if it has been set.
     *
     * @param string $alias
     * @param string $translationAlias
     * @param string $defaultTranslationAlias
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createTranslatableQueryBuilder($alias, $translationAlias = 't', $defaultTranslationAlias = 'dt')
    {
        $qb = new QueryBuilder($this->_em);
        $qb->select($alias)
            ->from($this->_entityName, $alias);

        $translatableProperties = $this->getExtendedMetadata()->getTranslatableProperties();
        foreach ($translatableProperties as $translationAssociation => $properties) {
            $localeProperty = $this
                ->getTranslationExtendedMetadata($translationAssociation)
                ->localeProperty;

            $locale = $this->getTranslatableListener()->getLocale();
            if (isset($locale)) {
                $qb->leftJoin(
                    sprintf('%s.%s', $alias, $translationAssociation),
                    $translationAlias,
                    Expr\Join::WITH,
                    sprintf('%s.%s = :locale', $translationAlias, $localeProperty)
                );
                $qb->addSelect($translationAlias);
                $qb->setParameter('locale', $locale);
            }

            $defaultLocale = $this->getTranslatableListener()->getDefaultLocale();
            if (isset($defaultLocale)) {
                $qb->leftJoin(
                    sprintf('%s.%s', $alias, $translationAssociation),
                    $defaultTranslationAlias,
                    Expr\Join::WITH,
                    sprintf('%s.%s = :deflocale', $defaultTranslationAlias, $localeProperty)
                );
                $qb->addSelect($defaultTranslationAlias);
                $qb->setParameter('deflocale', $defaultLocale);
            }
        }

        return $qb;
    }

    /**
     * Returns true if a translation entity for specified base entity and locale exists
     *
     * @param object $object
     * @param mixed $locale
     * @param string $translationAssociation
     * @return bool
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    public function hasTranslation($object, $locale, $translationAssociation = 'translations')
    {
        $this->validateObject($object);
        $this->validateTranslationAssociation($translationAssociation);

        $translation = $this->findTranslation(
            $object,
            $translationAssociation,
            $locale
        );

        return ($translation !== null);
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
        $this->validateObject($object);
        $this->validateTranslationAssociation($translationAssociation);

        $translation = $this->findTranslation(
            $object,
            $translationAssociation,
            $locale
        );

        if (isset($translation)) {
            return $translation;
        }

        $translation = $this->createTranslation(
            $object,
            $translationAssociation,
            $locale
        );

        return $translation;
    }

    /**
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     * @return object|null
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function findTranslation($object, $translationAssociation, $locale)
    {
        if ($this->areTranslationsIndexedByLocale($translationAssociation)) {
            return $this
                ->getTranslations($object, $translationAssociation)
                ->get($locale);
        } else {
            return $this->findNonIndexedTranslation(
                $object,
                $translationAssociation,
                $locale
            );
        }
    }

    /**
     * @param object $object
     * @param string $translationAssociation
     * @param string $locale
     * @return object|null
     */
    protected function findNonIndexedTranslation($object, $translationAssociation, $locale)
    {
        $translations = $this->getTranslations($object, $translationAssociation);
        foreach ($translations as $translation) {
            $translationLocale = $this->getTranslationLocale($translationAssociation, $translation);
            if ($translationLocale == $locale) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * @param object $object
     * @param string $translationAssociation
     * @param mixed $locale
     * @return object
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function createTranslation($object, $translationAssociation, $locale)
    {
        $translation = $this->getTranslationMetadata($translationAssociation)
            ->newInstance();

        $this->setTranslationObject(
            $translationAssociation,
            $translation,
            $object
        );
        $this->setTranslationLocale(
            $translationAssociation,
            $translation,
            $locale
        );

        if ($this->areTranslationsIndexedByLocale($translationAssociation)) {
            $this->getTranslations($object, $translationAssociation)
                ->set($locale, $translation);
        } else {
            $this->getTranslations($object, $translationAssociation)
                ->add($translation);
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

    /**
     * @return \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function getExtendedMetadata()
    {
        if (!isset($this->extendedMetadata)) {
            $this->extendedMetadata = $this->getTranslatableListener()
                ->getExtendedMetadata(
                    $this->getEntityManager(),
                    $this->getClassName()
                );
        }

        return $this->extendedMetadata;
    }

    /**
     * @param string $translationAssociation
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getTranslationMetadata($translationAssociation)
    {
        if (!isset($this->translationMetadata[$translationAssociation])) {
            $this->translationMetadata[$translationAssociation] =
                $this->getEntityManager()->getClassMetadata(
                    $this->getClassMetadata()->getAssociationTargetClass($translationAssociation)
                );
        }

        return $this->translationMetadata[$translationAssociation];
    }

    /**
     * @param string $translationAssociation
     * @return \FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function getTranslationExtendedMetadata($translationAssociation)
    {
        if (!isset($this->translationExtendedMetadata[$translationAssociation])) {
            $listener = $this->getTranslatableListener();

            $this->translationExtendedMetadata[$translationAssociation] =
                $listener->getExtendedMetadata(
                    $this->getEntityManager(),
                    $this->getClassMetadata()->getAssociationTargetClass($translationAssociation)
                );
        }

        return $this->translationExtendedMetadata[$translationAssociation];
    }

    /**
     * @param object $object
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function validateObject($object)
    {
        $className = $this->getClassName();
        if (!($object instanceof $className)) {
            throw new RuntimeException(sprintf(
                'Expected entity of class %s, but got %s',
                $className,
                is_object($object) ? get_class($object) : gettype($object)
            ));
        }
    }

    /**
     * @param string $translationAssociation
     * @return TranslatableListener
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function validateTranslationAssociation($translationAssociation)
    {
        $translatableProperties = $this->getExtendedMetadata()->getTranslatableProperties();

        if (!isset($translatableProperties[$translationAssociation])) {
            throw new RuntimeException(sprintf(
                'Entity %s has no translations association named %s',
                $this->getClassName(),
                $translationAssociation
            ));
        }
    }

    /**
     * @param string $translationAssociation
     * @return bool
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function areTranslationsIndexedByLocale($translationAssociation)
    {
        $translationAssociationMapping = $this->getClassMetadata()->getAssociationMapping($translationAssociation);
        if (!isset($translationAssociationMapping['indexBy'])) {
            return false;
        }

        $translationExtendedMeta = $this->getTranslationExtendedMetadata($translationAssociation);
        return ($translationAssociationMapping['indexBy'] == $translationExtendedMeta->localeProperty);
    }

    /**
     * @param object $object
     * @param string $translationAssociation
     * @return \Doctrine\Common\Collections\Collection
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    protected function getTranslations($object, $translationAssociation)
    {
        $translations = $this->getClassMetadata()->getFieldValue($object, $translationAssociation);

        if (!($translations instanceof Collection)) {
            throw new RuntimeException(sprintf(
                'Entity %s must contains implementation of "Doctrine\Common\Collections\Collection" in "%s" association',
                $this->getClassName(),
                $translationAssociation
            ));
        }

        return $translations;
    }

    /**
     * @param object $translation
     * @param string $translationAssociation
     * @return mixed
     */
    protected function getTranslationLocale($translationAssociation, $translation)
    {
        return $this
            ->getTranslationMetadata($translationAssociation)
            ->getFieldValue(
                $translation,
                $this->getTranslationExtendedMetadata($translationAssociation)
                    ->localeProperty
            );
    }

    /**
     * @param string $translationAssociation
     * @param object $translation
     * @param mixed $locale
     */
    protected function setTranslationLocale($translationAssociation, $translation, $locale)
    {
        $this->getTranslationMetadata($translationAssociation)
            ->setFieldValue(
                $translation,
                $this->getTranslationExtendedMetadata($translationAssociation)
                    ->localeProperty,
                $locale
            );
    }

    /**
     * @param string $translationAssociation
     * @param object $translation
     * @param object $object
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function setTranslationObject($translationAssociation, $translation, $object)
    {
        $translationAssociationMapping = $this->getClassMetadata()->getAssociationMapping($translationAssociation);
        $this->getTranslationMetadata($translationAssociation)
            ->setFieldValue(
                $translation,
                $translationAssociationMapping['mappedBy'],
                $object
            );
    }

    protected function addTranslationCriteria(
        QueryBuilder $qb,
        $translationJoinAlias,
        array $criteria = null,
        array $translationCriteria = null,
        array $orderBy = null
    ) {
        foreach ($criteria as $field => $value) {
            $this->addWhereCondition($qb, 'a', $field, $value);
        }

        foreach ($translationCriteria as $field => $value) {
            $this->addWhereCondition($qb, $translationJoinAlias, $field, $value);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $fieldName => $direction) {
                $qb->addOrderBy($fieldName, $direction);
            }
        }
    }

    protected function addWhereCondition(QueryBuilder $qb, $alias, $field, $value)
    {
        if ($this->_class->isCollectionValuedAssociation($field)) {
            $associationMapping = $this->_class->getAssociationMapping($field);
            switch ($associationMapping['type']) {
                case ClassMetadataInfo::MANY_TO_MANY:
                    throw new ConditionException(sprintf(
                        'field %s cannot be used since its ManyToMany association field',
                        $field
                    ));
                case ClassMetadataInfo::ONE_TO_MANY:
                    $qb->innerJoin(sprintf('%s.%s', $alias, $field), $field);
                    $qb->andWhere($qb->expr()->eq($field, sprintf(':%s_value', $field)));
                    $qb->setParameter(sprintf(':%s_value', $field), $value);
                    return;
            }
        }

        if (is_null($value)) {
            $qb->andWhere(sprintf('%s.%s IS NULL', $alias, $field));
        } elseif (is_array($value)) {
            $qb->andWhere($qb->expr()->in(sprintf('%s.%s', $alias, $field), sprintf(':%s', $field)));
            $qb->setParameter($field, $value);
        } else {
            $qb->andWhere($qb->expr()->eq(sprintf('%s.%s', $alias, $field), sprintf(':%s', $field)));
            $qb->setParameter($field, $value);
        }
    }
}
