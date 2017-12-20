<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Translatable\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;

interface TranslatableRepositoryInterface
{
    public function findTranslatableBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $locale = null
    );

    public function findTranslatableOneBy(
        array $criteria,
        ?array $orderBy = null,
        ?string $locale = null
    );

    /**
     * Creates query builder for this entity joined with associated translation
     * entity and constrained to current locale of TranslatableListener if it
     * has been set. It also adds second join to translation entity constrained
     * to default locale of TranslatableListener if it has been set.
     */
    public function createTranslatableQueryBuilder(
        string $alias,
        ?string $translationAlias = 't',
        ?string $defaultTranslationAlias = 'dt'
    ): QueryBuilder;

    /**
     * Returns true if a translation entity for specified base entity and locale exists
     */
    public function hasTranslation(
        $object,
        string $locale,
        ?string $translationAssociation = 'translations'
    ): bool;

    /**
     * Returns existing or newly created translation entity for specified base
     * entity and locale
     * @return object existing or new translation entity for specified locale
     */
    public function getTranslation(
        $object,
        string $locale,
        ?string $translationAssociation = 'translations'
    );

    public function getTranslations(
        $object,
        ?string $translationAssociation = 'translations'
    ): Collection;

    public function findTranslation(
        $object,
        string $locale,
        ?string $translationAssociation = 'translations'
    );
}
