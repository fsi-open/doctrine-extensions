<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Query;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use FSi\DoctrineExtensions\Translatable\Exception;
use FSi\DoctrineExtensions\Translatable\TranslatableListener;

class TranslatableTreeWalker extends TreeWalkerAdapter
{
    /**
     * Associated EntityManager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * Translatable query components detected by this tree walker
     *
     * @var array
     */
    protected $translatableComponents;

    /**
     * TranslatableListener extracted from attached EntityManager
     *
     * @var \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    protected $translatableListener;

    /**
     * @var bool
     */
    protected $skipUntranslated;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->entityManager = $query->getEntityManager();
        if (($query->getHydrationMode() != \FSi\DoctrineExtensions\ORM\Query::HYDRATE_OBJECT) ||
            ($this->entityManager->getConfiguration()->getCustomHydrationMode($query->getHydrationMode()) != 'FSi\DoctrineExtensions\ORM\Hydration\ObjectHydrator')) {
            throw new Exception\RuntimeException('Using TranslatableTreeWalker requires FSi\DoctrineExtensions\ORM\Hydration\ObjectHydrator to be set for the same query');
        }
        $this->translatableListener = $this->getTranslatableListener();
        parent::__construct($query, $parserResult, $queryComponents);
        $this->detectTranslatableComponents($queryComponents);
        $hints = $query->getHints();
        if (isset($hints[\FSi\DoctrineExtensions\Translatable\TranslatableListener::HINT_SKIP_UNTRANSLATED]))
            $this->skipUntranslated = $hints[\FSi\DoctrineExtensions\Translatable\TranslatableListener::HINT_SKIP_UNTRANSLATED];
        else
            $this->skipUntranslated = $this->translatableListener->isSkipUntranslated();
    }

    /**
     * {@inheritDoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $locale = $this->translatableListener->getLocale();
        $defaultLocale = $this->translatableListener->getDefaultLocale();
        if (!isset($locale) && isset($defaultLocale)) {
            $locale = $defaultLocale;
            $defaultLocale = null;
        }

        $result = parent::walkSelectStatement($AST);
        foreach ($AST->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $componentAlias = $identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable;
            $joins = array();

            if (isset($this->translatableComponents[$componentAlias])) {
                $meta = $this->translatableComponents[$componentAlias]['metadata'];
                $translatableMeta = $this->translatableListener->getExtendedMetadata($this->entityManager, $meta->name);

                $translatableProperties = $translatableMeta->getTranslatableProperties();
                foreach ($translatableProperties as $translation => $fields) {
                    $joins[] =
                        $this->getJoinTranslation($componentAlias, $translation, $locale, $defaultLocale);
                    $AST->selectClause->selectExpressions[] =
                        $this->getTranslationSelectExpression($componentAlias, $translation);
                }
            }

            foreach ($identificationVariableDeclaration->joins as $join) {
                $componentAlias = $join->joinAssociationDeclaration->aliasIdentificationVariable;
                if (isset($this->translatableComponents[$componentAlias])) {
                    $meta = $this->translatableComponents[$componentAlias]['metadata'];
                    $translatableMeta = $this->translatableListener->getExtendedMetadata($this->entityManager, $meta->name);

                    $translatableProperties = $translatableMeta->getTranslatableProperties();
                    foreach ($translatableProperties as $translation => $fields) {
                        $joins[] =
                            $this->getJoinTranslation($componentAlias, $translation, $locale, $defaultLocale);
                        $AST->selectClause->selectExpressions[] =
                            $this->getTranslationSelectExpression($componentAlias, $translation);
                    }
                }
            }

            foreach ($joins as $join)
                $identificationVariableDeclaration->joins[] = $join;
        }
        return $result;
    }

    /**
     * Return join object with translation entity for specified query component with apriopriate conditions
     *
     * @param string $componentAlias
     * @param string $translation
     * @param mixed $locale
     * @param mixed $defaultLocale
     */
    protected function getJoinTranslation($componentAlias, $translation, $locale, $defaultLocale)
    {
        $meta = $this->translatableComponents[$componentAlias]['metadata'];
        $translationEntity = $meta->getAssociationTargetClass($translation);
        $translationMeta = $this->entityManager->getClassMetadata($translationEntity);
        $translationConfig = $this->translatableListener->getExtendedMetadata($this->entityManager, $translationMeta->name);
        $translationLanguageField = $translationConfig->localeProperty;

        $join = new AST\Join(
            $this->skipUntranslated?(AST\Join::JOIN_TYPE_INNER):(AST\Join::JOIN_TYPE_LEFT),
            new AST\JoinAssociationDeclaration(
                new AST\JoinAssociationPathExpression($componentAlias, $translation),
                $this->getTranslationComponentAlias($componentAlias, $translation),
                null
            )
        );
        $pathExpresion = new AST\PathExpression(
            null,
            $this->getTranslationComponentAlias($componentAlias, $translation),
            $translationLanguageField
        );
        if ($translationMeta->isSingleValuedAssociation($translationLanguageField))
            $pathExpresion->type = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        else if ($translationMeta->hasField($translationLanguageField))
            $pathExpresion->type = AST\PathExpression::TYPE_STATE_FIELD;
        if (isset($locale)) {
            $join->conditionalExpression = new AST\ConditionalPrimary();
            if (isset($defaultLocale)) {
                $join->conditionalExpression->simpleConditionalExpression = new AST\InExpression(
                    new AST\ArithmeticExpression()
                );
                $join->conditionalExpression->simpleConditionalExpression->expression->simpleArithmeticExpression =
                new AST\SimpleArithmeticExpression(
                    array($pathExpresion)
                );
                $join->conditionalExpression->simpleConditionalExpression->literals = array(
                    new AST\InputParameter(':locale'.$componentAlias.$translation),
                    new AST\InputParameter(':deflocale'.$componentAlias.$translation)
                );
                $this->_getQuery()->setParameter('deflocale'.$componentAlias.$translation, $defaultLocale);
            } else {
                $join->conditionalExpression->simpleConditionalExpression = new AST\ComparisonExpression(
                    new AST\SimpleArithmeticExpression(
                        array($pathExpresion)
                    ),
                    '=',
                    new AST\SimpleArithmeticExpression(
                        array(new AST\InputParameter(':locale'.$componentAlias.$translation))
                    )
                );
            }
            $this->_getQuery()->setParameter('locale'.$componentAlias.$translation, $locale);
        }
        return $join;
    }

    /**
     * Return component alias for specified translation query component and translations association
     *
     * @param string $componentAlias
     * @param string $translation
     */
    protected function getTranslationComponentAlias($componentAlias, $translation)
    {
        return 'trans'.$componentAlias.$translation;
    }

    /**
     * Return select expression for translation component
     *
     * @param string $componentAlias
     * @param string $translation
     * @return AST\SelectExpression
     */

    protected function getTranslationSelectExpression($componentAlias, $translation)
    {
        return new AST\SelectExpression(
            $this->getTranslationComponentAlias($componentAlias, $translation),
            null,
            false
        );
    }

    /**
     * Find TranslatableListener in attached EntityManager
     *
     * @throws \FSi\DoctrineExtensions\Translatable\Exception\RuntimeException
     * @return \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    protected function getTranslatableListener()
    {
        $translatableListener = null;
        $em = $this->entityManager;
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof TranslatableListener) {
                    $translatableListener = $listener;
                    break;
                }
            }
            if (isset($translatableListener))
                break;
        }
        if (!isset($translatableListener)) {
            throw new Exception\RuntimeException('TranslatableTreeWalker needs TranslatableListener attached to its EnityManager');
        }
        return $translatableListener;
    }

    /**
     * Find translatable components and return associated translation query components
     *
     * @param array $queryComponents
     * return array
     */
    protected function detectTranslatableComponents(array $queryComponents)
    {
        $em = $this->entityManager;
        $translationComponents = array();
        foreach ($queryComponents as $alias => $component) {
            if (!isset($component['metadata'])) {
                continue;
            }
            $meta = $component['metadata'];
            $translatableMeta = $this->translatableListener->getExtendedMetadata($em, $meta->name);
            if ($translatableMeta->hasTranslatableProperties()) {
                $this->translatableComponents[$alias] = $component;
                foreach ($translatableMeta->getTranslatableProperties() as $translation => $fields) {
                    $translationMeta = $em->getClassMetadata($meta->getAssociationTargetClass($translation));
                    $this->setQueryComponent('trans'.$alias.$translation, array(
                        'metadata' => $translationMeta,
                        'parent' => $alias,
                        'relation' => $meta->getAssociationMapping($translation),
                        'map' => null,
                        'nestingLevel' => 0,
                        'token' => null
                    ));
                }
            }
        }
        return $translationComponents;
    }

}
