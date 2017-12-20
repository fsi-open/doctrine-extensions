<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Translatable\Query;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use FSi\DoctrineExtensions\Exception\InvalidArgumentException;
use FSi\DoctrineExtensions\ORM\QueryBuilder as BaseQueryBuilder;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;
use FSi\DoctrineExtensions\Translatable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Translatable\TranslatableListener;

class QueryBuilder extends BaseQueryBuilder
{
    /**
     * @var TranslatableListener
     */
    private $listener;

    /**
     * @var array
     */
    private $aliasToClassMap = [];

    /**
     * @var array
     */
    private $translationsAliases = [];

    /**
     * @var array
     */
    private $translatableFieldsInSelect = [];

    /**
     * @inheritdoc
     */
    public function add($dqlPartName, $dqlPart, $append = false)
    {
        if ($this->isValidFromPart($dqlPartName, $dqlPart)) {
            $this->addFromExprToAliasMap($dqlPart);
        } elseif ($this->isValidJoinPart($dqlPartName, $dqlPart)) {
            $join = current($dqlPart);
            $this->validateJoinParent($join);
            $this->validateJoinAssociation($join);
            $this->addJoinExprToAliasMap($join);
        } elseif (in_array($dqlPartName, ['from', 'join'])) {
            throw new RuntimeException(sprintf(
                "Trying to add incompatible expression to DQL part '%s' in QueryBuilder",
                $dqlPartName
            ));
        }

        return parent::add($dqlPartName, $dqlPart, $append);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function joinTranslations(
        string $join,
        ?string $joinType = Expr\Join::LEFT_JOIN,
        ?string $locale = null,
        ?string $alias = null,
        ?string $localeParameter = null
    ): self {
        $this->validateJoinTranslations($join);

        $locale = $this->getCurrentLocale($locale);
        $alias = $this->getJoinTranslationsAlias($alias, $join, $locale);
        $condition = $this->getJoinTranslationsCondition($join, $alias, $localeParameter, $locale);
        $conditionType = $this->getJoinTranslationsConditionType($locale);

        $this->addJoinedTranslationsAlias($join, $locale, $alias);

        switch ($joinType) {
            case Expr\Join::INNER_JOIN:
                return $this->innerJoin($join, $alias, $conditionType, $condition);
            case Expr\Join::LEFT_JOIN:
                return $this->leftJoin($join, $alias, $conditionType, $condition);
            default:
                throw new InvalidArgumentException(sprintf('Unknown join type "%s"', $joinType));
        }
    }

    public function joinAndSelectCurrentTranslations(
        string $join,
        ?string $joinType = Expr\Join::LEFT_JOIN,
        ?string $alias = null,
        ?string $localeParameter = null
    ): self {
        $locale = $this->getTranslatableListener()->getLocale();
        if (isset($locale)) {
            $this->joinAndSelectTranslationsOnce(
                $join,
                $joinType,
                $locale,
                $alias,
                $localeParameter
            );
        }

        return $this;
    }

    public function joinAndSelectDefaultTranslations(
        string $join,
        ?string $joinType = Expr\Join::LEFT_JOIN,
        ?string $alias = null,
        ?string $localeParameter = null
    ): self {
        $defaultLocale = $this->getTranslatableListener()->getDefaultLocale();
        if (isset($defaultLocale)) {
            $this->joinAndSelectTranslationsOnce(
                $join,
                $joinType,
                $defaultLocale,
                $alias,
                $localeParameter
            );
        }

        return $this;
    }

    public function addTranslatableWhere(
        string $alias,
        string $field,
        $value,
        ?string $locale = null
    ): self {
        $meta = $this->getClassMetadata($this->getClassByAlias($alias));
        $checkField = $field;
        if ($this->isTranslatableProperty($alias, $field)) {
            $meta = $this->getTranslationMetadata($alias, $field);
            $checkField = $this->getTranslationField($alias, $field);
        }
        if ($meta->isCollectionValuedAssociation($checkField)) {
            $this->addTranslatableWhereOnCollection($alias, $field, $value, $locale);
        } else {
            $this->addTranslatableWhereOnField($alias, $field, $value, $locale);
        }

        return $this;
    }

    public function addTranslatableOrderBy(
        string $alias,
        string $field,
        ?array $order = null,
        ?string $locale = null
    ): self {
        $this->addOrderBy(
            $this->getTranslatableFieldExprWithOptionalHiddenSelect(
                $alias,
                $field,
                true,
                $locale
            ),
            $order
        );

        return $this;
    }

    public function getTranslatableFieldExpr(
        string $alias,
        string $property,
        ?string $locale = null
    ): string {
        return $this->getTranslatableFieldExprWithOptionalHiddenSelect(
            $alias,
            $property,
            false,
            $locale
        );
    }

    private function getTranslatableFieldExprWithOptionalHiddenSelect(
        string $alias,
        string $field,
        bool $addHiddenSelect,
        ?string $locale = null
    ): string {
        if (!$this->isTranslatableProperty($alias, $field)) {
            return sprintf('%s.%s', $alias, $field);
        }

        $this->validateCurrentLocale($locale);
        $this->joinCurrentTranslationsOnce($alias, $field, $locale);
        if (!$this->hasDefaultLocaleDifferentThanCurrentLocale($locale)) {
            return $this->getTranslatableFieldSimpleExpr($alias, $field, $locale);
        }

        $this->joinDefaultTranslationsOnce($alias, $field);
        if ($addHiddenSelect) {
            return $this->getHiddenSelectTranslatableFieldConditionalExpr($alias, $field, $locale);
        }

        return $this->getTranslatableFieldConditionalExpr($alias, $field, $locale);
    }

    private function getTranslatableCollectionExpr(
        string $alias,
        string $field,
        string $exprTemplate,
        bool $doJoin,
        ?string $locale = null
    ): string {
        if (!$this->isTranslatableProperty($alias, $field)) {
            return sprintf($exprTemplate, sprintf('%s.%s', $alias, $field));
        }

        $this->validateCurrentLocale($locale);
        $this->joinCurrentTranslationsOnce($alias, $field, $locale);
        $currentLocale = $this->getCurrentLocale($locale);
        if (!$this->hasDefaultLocaleDifferentThanCurrentLocale($locale)) {
            return $this->getTranslatableCollectionTranslationExpr(
                $alias,
                $field,
                $exprTemplate,
                $doJoin,
                $currentLocale
            );
        }

        $this->joinDefaultTranslationsOnce($alias, $field);
        $defaultLocale = $this->getTranslatableListener()->getDefaultLocale();

        $currentTranslationsAlias = $this->getJoinedCurrentTranslationsAlias($alias, $field, $currentLocale);
        $translationIdentity = $this->getClassMetadata(
            $this->getClassByAlias($currentTranslationsAlias)
        )->getSingleIdentifierFieldName();

        return sprintf(
            'CASE WHEN %s.%s IS NOT NULL AND %s THEN TRUE WHEN %s THEN TRUE ELSE FALSE END = TRUE',
            $currentTranslationsAlias,
            $translationIdentity,
            $this->getTranslatableCollectionTranslationExpr($alias, $field, $exprTemplate, $doJoin, $currentLocale),
            $this->getTranslatableCollectionTranslationExpr($alias, $field, $exprTemplate, $doJoin, $defaultLocale)
        );
    }

    private function getTranslatableCollectionTranslationExpr(
        string $alias,
        string $field,
        string $exprTemplate,
        bool $doJoin,
        string $locale
    ) {
        $translationsAssociation = $this->getTranslationAssociation($alias, $field);
        $translationsJoin = $this->getTranslationsJoin($alias, $translationsAssociation);
        $translationsAlias = $this->getJoinedTranslationsAlias($translationsJoin, $locale);
        if ($doJoin) {
            $joinAlias = $this->getCollectionJoinAlias($translationsAlias, $field);
            $this->leftJoin(sprintf('%s.%s', $translationsAlias, $field), $joinAlias);

            return sprintf($exprTemplate, $joinAlias);
        }

        return sprintf($exprTemplate, sprintf('%s.%s', $translationsAlias, $field));
    }

    /**
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     */
    private function getTranslatableListener(): TranslatableListener
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

        throw new RuntimeException(
            'Cannot find TranslatableListener in EntityManager\'s EventManager'
        );
    }

    private function isTranslatableProperty(string $alias, string $property): bool
    {
        $translatableProperties = $this->getTranslatableMetadata(
            $this->getClassByAlias($alias)
        )->getTranslatableProperties();

        foreach ($translatableProperties as $properties) {
            if (isset($properties[$property])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \RuntimeException
     */
    private function validateCurrentLocale(?string $locale): void
    {
        $locale = $this->getCurrentLocale($locale);

        if (!isset($locale)) {
            throw new RuntimeException(
                'At least current locale must be set on TranslatableListener'
            );
        }
    }

    private function isValidFromPart(string $dqlPartName, $dqlPart): bool
    {
        return ($dqlPartName == 'from') && ($dqlPart instanceof Expr\From);
    }

    private function addFromExprToAliasMap(Expr\From $from): void
    {
        $this->aliasToClassMap[$from->getAlias()] = $from->getFrom();
    }

    private function isValidJoinPart(string $dqlPartName, $dqlPart): bool
    {
        return ($dqlPartName == 'join')
            && is_array($dqlPart)
            && (current($dqlPart) instanceof Expr\Join)
        ;
    }

    private function addJoinExprToAliasMap(Expr\Join $join): void
    {
        $alias = $this->getJoinParentAlias($join->getJoin());
        $association = $this->getJoinAssociation($join->getJoin());

        $this->aliasToClassMap[$join->getAlias()] = $this->getClassMetadata(
            $this->getClassByAlias($alias)
        )->getAssociationTargetClass($association);
    }

    /**
     * @throws \RuntimeException
     */
    private function getClassByAlias(string $alias): string
    {
        if (!isset($this->aliasToClassMap[$alias])) {
            throw new RuntimeException(sprintf(
                'Alias "%s" is not present in QueryBuilder',
                $alias
            ));
        }

        return $this->aliasToClassMap[$alias];
    }

    private function getClassMetadata(string $class): ClassMetadata
    {
        return $this->getEntityManager()->getClassMetadata($class);
    }

    private function getTranslatableMetadata(string $class): TranslatableClassMetadata
    {
        return $this->getTranslatableListener()->getExtendedMetadata(
            $this->getEntityManager(),
            $class
        );
    }

    /**
     * @throws \RuntimeException
     */
    private function validateJoinParent(Expr\Join $join): void
    {
        $alias = $this->getJoinParentAlias($join->getJoin());
        if (!isset($this->aliasToClassMap[$alias])) {
            throw new RuntimeException(sprintf(
                "Cannot find alias %s in QueryBuilder (%s)",
                $alias,
                $this->getDQL()
            ));
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function validateJoinAssociation(Expr\Join $join): void
    {
        $alias = $this->getJoinParentAlias($join->getJoin());
        $association = $this->getJoinAssociation($join->getJoin());
        $parentClassMetadata = $this->getClassMetadata($this->getClassByAlias($alias));
        if (!$parentClassMetadata->hasAssociation($association)) {
            throw new RuntimeException(sprintf(
                "Cannot find association named %s in class %s",
                $association,
                $parentClassMetadata->getName()
            ));
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function validateJoinTranslations(string $join): void
    {
        $translatableAlias = $this->getJoinParentAlias($join);
        $translationAssociation = $this->getJoinAssociation($join);
        $translatableMetadata = $this->getTranslatableMetadata(
            $this->getClassByAlias($translatableAlias)
        );

        $translatableProperties = $translatableMetadata->getTranslatableProperties();
        if (!isset($translatableProperties[$translationAssociation])) {
            throw new RuntimeException(
                sprintf(
                    "'%s' is not an association with translation entity in class '%s'",
                    $translationAssociation,
                    $this->aliasToClassMap[$translatableAlias]
                )
            );
        }
    }

    private function getJoinParentAlias(string $join): string
    {
        return substr($join, 0, strpos($join, '.'));
    }

    private function getJoinAssociation(string $join): string
    {
        return substr($join, strpos($join, '.') + 1);
    }

    private function getJoinTranslationsAlias(string $alias, string $join, string $locale): string
    {
        if (isset($alias)) {
            return $alias;
        }

        return sprintf('%s%s', str_replace('.', '', $join), (string) $locale);
    }

    private function getJoinTranslationsConditionType(?string $locale): string
    {
        return isset($locale) ? Expr\Join::WITH : Expr\Join::ON;
    }

    private function getJoinTranslationsCondition(
        string $join,
        string $alias,
        string $localeParameter,
        ?string $locale
    ): ?string {
        if (!isset($locale)) {
            return null;
        }

        $localeParameter = $this->getJoinTranslationsLocaleParameter($alias, $localeParameter);
        $this->setParameter($localeParameter, $locale);
        return $this->getJoinTranslationsLocaleCondition($alias, $join, $localeParameter);
    }

    private function getJoinTranslationsLocaleParameter(string $alias, ?string $localeParameter): string
    {
        if (isset($localeParameter)) {
            return $localeParameter;
        }

        return sprintf('%sloc', $alias);
    }

    private function getJoinTranslationsLocaleCondition(
        string $alias,
        string $join,
        string $localeParameter
    ): string {
        $translatableAlias = $this->getJoinParentAlias($join);
        $translationAssociation = $this->getJoinAssociation($join);
        $translationClass = $this->getClassMetadata(
            $this->getClassByAlias($translatableAlias)
        )->getAssociationTargetClass($translationAssociation);
        $translationMetadata = $this->getTranslatableMetadata($translationClass);

        return sprintf(
            '%s.%s = :%s',
            $alias,
            $translationMetadata->localeProperty,
            $localeParameter
        );
    }

    private function addJoinedTranslationsAlias(string $join, string $locale, string $alias): string
    {
        if (!isset($this->translationsAliases[$join])) {
            $this->translationsAliases[$join] = [];
        }

        $this->translationsAliases[$join][$locale] = $alias;
    }

    private function hasJoinedTranslationsAlias(string $join, string $locale): bool
    {
        return isset($this->translationsAliases[$join][$locale]);
    }

    private function getJoinedTranslationsAlias(string $join, string $locale): ?string
    {
        if (isset($this->translationsAliases[$join][$locale])) {
            return $this->translationsAliases[$join][$locale];
        }
    }

    private function getTranslationField(string $alias, string $property): string
    {
        $translatableProperties = $this->getTranslatableMetadata(
            $this->getClassByAlias($alias)
        )->getTranslatableProperties();

        foreach ($translatableProperties as $properties) {
            if (isset($properties[$property])) {
                return $properties[$property];
            }
        }

        $this->throwUnknownTranslatablePropertyException($alias, $property);
    }

    private function getTranslationAssociation(string $alias, string $property): string
    {
        $translatableProperties = $this->getTranslatableMetadata(
            $this->getClassByAlias($alias)
        )->getTranslatableProperties();

        foreach ($translatableProperties as $translationAssociation => $properties) {
            if (isset($properties[$property])) {
                return $translationAssociation;
            }
        }

        $this->throwUnknownTranslatablePropertyException($alias, $property);
    }

    private function joinCurrentTranslationsOnce(
        string $alias,
        string $property,
        ?string $locale
    ): void {
        $translationAssociation = $this->getTranslationAssociation($alias, $property);
        $join = $this->getTranslationsJoin($alias, $translationAssociation);

        $this->joinTranslationsOnce(
            $join,
            Expr\Join::LEFT_JOIN,
            $this->getCurrentLocale($locale)
        );
    }

    private function joinDefaultTranslationsOnce(string $alias, string $property): void
    {
        $translationAssociation = $this->getTranslationAssociation($alias, $property);
        $join = $this->getTranslationsJoin($alias, $translationAssociation);
        $this->joinTranslationsOnce(
            $join,
            Expr\Join::LEFT_JOIN,
            $this->getTranslatableListener()->getDefaultLocale()
        );
    }

    private function joinTranslationsOnce(
        string $join,
        string $joinType,
        string $locale,
        ?string $alias = null,
        ?string $localeParameter = null
    ): void {
        if (!$this->hasJoinedTranslationsAlias($join, $locale)) {
            $this->joinTranslations($join, $joinType, $locale, $alias, $localeParameter);
        }
    }

    /**
     * @param string $join
     * @param string $joinType
     * @param mixed $locale
     * @param string $alias
     * @param string $localeParameter
     * @internal param string $property
     */
    private function joinAndSelectTranslationsOnce(
        string $join,
        string $joinType,
        string $locale,
        ?string $alias = null,
        ?string $localeParameter = null
    ): void {
        if (!$this->hasJoinedTranslationsAlias($join, $locale)) {
            $this->joinTranslations($join, $joinType, $locale, $alias, $localeParameter);
            $this->addSelect($alias);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function throwUnknownTranslatablePropertyException(string $alias, string $property): void
    {
        throw new RuntimeException(
            sprintf(
                'Unknown translatable property "%s" in class "%s"',
                $property,
                $this->getClassByAlias($alias)
            )
        );
    }

    private function hasDefaultLocaleDifferentThanCurrentLocale(?string $locale): bool
    {
        $locale = $this->getCurrentLocale($locale);
        return null !== $this->getTranslatableListener()->getDefaultLocale()
            && $locale !== $this->getTranslatableListener()->getDefaultLocale()
        ;
    }

    private function getTranslatableFieldConditionalExpr(
        string $alias,
        string $property,
        ?string $locale = null
    ): string {
        $currentTranslationsAlias = $this->getJoinedCurrentTranslationsAlias($alias, $property, $locale);
        $defaultTranslationsAlias = $this->getJoinedDefaultTranslationsAlias($alias, $property);
        $translationIdentity = $this->getClassMetadata(
            $this->getClassByAlias($currentTranslationsAlias)
        )->getSingleIdentifierFieldName();
        $translationField = $this->getTranslationField($alias, $property);

        return sprintf(
            'CASE WHEN %s.%s IS NOT NULL THEN %s.%s ELSE %s.%s END',
            $currentTranslationsAlias,
            $translationIdentity,
            $currentTranslationsAlias,
            $translationField,
            $defaultTranslationsAlias,
            $translationField
        );
    }

    private function getHiddenSelectTranslatableFieldConditionalExpr(
        string $alias,
        string $property,
        ?string $locale = null
    ): string {
        $hiddenSelect = sprintf('%s%s', $alias, $property);
        if (!isset($this->translatableFieldsInSelect[$hiddenSelect])) {
            $this->addSelect(sprintf(
                '%s HIDDEN %s',
                $this->getTranslatableFieldConditionalExpr($alias, $property, $locale),
                $hiddenSelect
            ));
            $this->translatableFieldsInSelect[$hiddenSelect] = $hiddenSelect;
        }

        return $this->translatableFieldsInSelect[$hiddenSelect];
    }

    private function getJoinedDefaultTranslationsAlias(string $alias, string $property): array
    {
        $translationAssociation = $this->getTranslationAssociation($alias, $property);
        $join = $this->getTranslationsJoin($alias, $translationAssociation);
        $defaultLocale = $this->getTranslatableListener()->getDefaultLocale();
        $defaultTranslationsAlias = $this->getJoinedTranslationsAlias($join, $defaultLocale);

        return $defaultTranslationsAlias;
    }

    private function getJoinedCurrentTranslationsAlias(
        string $alias,
        string $property,
        ?string $locale = null
    ): string {
        $translationAssociation = $this->getTranslationAssociation($alias, $property);
        $join = $this->getTranslationsJoin($alias, $translationAssociation);

        return $this->getJoinedTranslationsAlias($join, $this->getCurrentLocale($locale));
    }

    private function getTranslatableFieldSimpleExpr(
        string $alias,
        string $property,
        ?string $locale = null
    ): string {
        $translationsAssociation = $this->getTranslationAssociation($alias, $property);
        $translationsJoin = $this->getTranslationsJoin($alias, $translationsAssociation);

        return sprintf(
            '%s.%s',
            $this->getJoinedTranslationsAlias(
                $translationsJoin,
                $this->getCurrentLocale($locale)
            ),
            $this->getTranslationField($alias, $property)
        );
    }

    private function getCurrentLocale(?string $locale): ?string
    {
        return !is_null($locale)
            ? $locale
            : $this->getTranslatableListener()->getLocale()
        ;
    }

    private function getTranslationsJoin(string $alias, string $translationAssociation): string
    {
        return sprintf('%s.%s', $alias, $translationAssociation);
    }

    private function addTranslatableWhereOnCollection(
        string $alias,
        string $field,
        $value,
        ?string $locale = null
    ): void {
        $parameter = $this->getTranslatableValueParameter($alias, $field);

        if (null === $value) {
            $fieldExpr = 'SIZE(%s) = 0';
            $collectionExpr = $this->getTranslatableCollectionExpr($alias, $field, $fieldExpr, false, $locale);
            $this->andWhere($collectionExpr);
        } elseif (is_array($value)) {
            if ($this->isTranslatableProperty($alias, $field)) {
                $fieldExpr = $this->expr()->in('%s', $parameter);
                $collectionExpr = $this->getTranslatableCollectionExpr($alias, $field, $fieldExpr, true, $locale);
                $this->andWhere($collectionExpr);
            } else {
                $fieldExpr = $this->getTranslatableFieldExpr($alias, $field, $locale);
                $joinAlias = $this->getCollectionJoinAlias($alias, $field);
                $this->leftJoin($fieldExpr, $joinAlias);
                $this->andWhere($this->expr()->in($joinAlias, $parameter));
            }
            $this->setParameter($parameter, $value);
        } else {
            $fieldExpr = sprintf('%s MEMBER OF %s', $parameter, '%s');
            $collectionExpr = $this->getTranslatableCollectionExpr($alias, $field, $fieldExpr, false, $locale);
            $this->andWhere($collectionExpr);
            $this->setParameter($parameter, $value);
        }
    }

    private function addTranslatableWhereOnField(
        string $alias,
        string $field,
        $value,
        ?string $locale = null
    ): void {
        $fieldExpr = $this->getTranslatableFieldExpr($alias, $field, $locale);
        $parameter = $this->getTranslatableValueParameter($alias, $field);

        if (null === $value) {
            $this->andWhere($this->expr()->isNull($fieldExpr));
        } elseif (is_array($value)) {
            $this->andWhere($this->expr()->in($fieldExpr, $parameter));
            $this->setParameter($parameter, $value);
        } else {
            $this->andWhere($this->expr()->eq($fieldExpr, $parameter));
            $this->setParameter($parameter, $value);
        }
    }

    private function getTranslatableValueParameter(string $alias, string $field): string
    {
        return sprintf(':%s%sval', $alias, $field);
    }

    private function getCollectionJoinAlias(string $alias, string $field): string
    {
        return sprintf('%s%sjoin', $alias, $field);
    }

    private function getTranslationMetadata(string $alias, string $field): ClassMetadata
    {
        return $this->getClassMetadata(
            $this->getClassMetadata($this->getClassByAlias($alias))
                ->getAssociationTargetClass(
                    $this->getTranslationAssociation($alias, $field)
                )
        );
    }
}
