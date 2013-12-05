<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Entity\Repository;

use Doctrine\ORM\EntityRepository;
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

        return $qb;
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