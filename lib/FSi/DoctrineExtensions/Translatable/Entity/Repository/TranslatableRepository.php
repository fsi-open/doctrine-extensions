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
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder;
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

    /**
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @param mixed $locale
     * @return array
     */
    public function findTranslatableBy(array $criteria, array $orderBy = null, $limit = null, $offset = null, $locale = null)
    {
        $qb = $this->createFindTranslatableQueryBuilder('e', $criteria, $orderBy, $limit, $offset, $locale);

        return $qb->getQuery()->execute();
    }

    /**
     * @param array $criteria
     * @param array $orderBy
     * @param mixed $locale
     * @return array
     */
    public function findTranslatableOneBy(array $criteria, array $orderBy = null, $locale = null)
    {
        $qb = $this->createFindTranslatableQueryBuilder('e', $criteria, $orderBy, 1, null, $locale);

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
            $join = sprintf('%s.%s', $alias, $translationAssociation);
            $qb->joinAndSelectCurrentTranslations(
                $join, Expr\Join::LEFT_JOIN,
                $translationAlias,
                'locale'
            );
            $qb->joinAndSelectDefaultTranslations(
                $join, Expr\Join::LEFT_JOIN,
                $defaultTranslationAlias,
                'deflocale'
            );
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

    /**
     * @param $alias
     * @param array $criteria
     * @param array $orderBy
     * @param $limit
     * @param $offset
     * @param mixed $locale
     * @return QueryBuilder
     * @throws ConditionException
     */
    private function createFindTranslatableQueryBuilder(
        $alias,
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $locale = null
    ) {
        $qb = new QueryBuilder($this->_em);
        $qb->from($this->_entityName, $alias);
        $qb->select($alias);

        foreach ($criteria as $criteriaField => $criteriaValue) {
            $qb->addTranslatableWhere($alias, $criteriaField, $criteriaValue, $locale);
        }

        if (isset($orderBy)) {
            foreach ($orderBy as $orderField => $orderDirection) {
                $qb->addTranslatableOrderBy($alias, $orderField, $orderDirection, $locale);
            }
        }

        if (isset($limit)) {
            $qb->setMaxResults($limit);
        }
        if (isset($offset)) {
            $qb->setFirstResult($offset);

            return $qb;
        }

        return $qb;
    }
}
