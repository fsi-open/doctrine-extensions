<?php
namespace FSi\DoctrineExtension\Versionable\Query;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use FSi\DoctrineExtension\Versionable\VersionableListener;
use FSi\DoctrineExtension\Versionable\VersionableException;

class VersionableTreeWalker extends TreeWalkerAdapter
{
    /**
     * Associated EntityManager
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Associated database platfrom
     *
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * Version query components added by this tree walker
     *
     * @var array
     */
    protected $versionableComponents;

    /**
     * VersionableListener extracted from attached EntityManager
     *
     * @var VersionableListener
     */
    protected $versionableListener;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->entityManager = $query->getEntityManager();
        $this->versionableListener = $this->getVersionableListener();
        parent::__construct($query, $parserResult, $queryComponents);
        $this->detectVersionableComponents($queryComponents);
    }

    /**
     * {@inheritDoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $result = parent::walkSelectStatement($AST);
        foreach ($AST->fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $componentAlias = $identificationVariableDeclaration->rangeVariableDeclaration->aliasIdentificationVariable;
            $joins = array();
            if (isset($this->versionableComponents[$componentAlias])) {
                $joins[] = $this->getJoinVersion($componentAlias);
                $AST->selectClause->selectExpressions[] = $this->getVersionSelectExpression($componentAlias);
            }
            foreach ($identificationVariableDeclaration->joins as $join) {
                $componentAlias = $join->joinAssociationDeclaration->aliasIdentificationVariable;
                if (isset($this->versionableComponents[$componentAlias])) {
                    $joins[] = $this->getJoinVersion($componentAlias);
                    $AST->selectClause->selectExpressions[] = $this->getVersionSelectExpression($componentAlias);
                }
            }
            foreach ($joins as $join)
                $identificationVariableDeclaration->joins[] = $join;
        }
        return $result;
    }

    /**
     * Return join object with version entity for specified query component with apriopriate conditions
     *
     * @param string $componentAlias
     * @return AST\Join
     */
    protected function getJoinVersion($componentAlias)
    {
        $component = $this->versionableComponents[$componentAlias];
        $meta = $component['metadata'];
        $versionableMeta = $this->versionableListener->getExtendedMetadata($this->entityManager, $meta->name);
        $componentVersionField = $versionableMeta->versionProperty;

        $versionEntity = $meta->getAssociationTargetClass($versionableMeta->versionAssociation);
        $versionMeta = $this->entityManager->getClassMetadata($versionEntity);
        $versionExtendedMeta = $this->versionableListener->getExtendedMetadata($this->entityManager, $versionMeta->name);
        $versionNumberField = $versionExtendedMeta->versionProperty;

        $join = new AST\Join(
            AST\Join::JOIN_TYPE_LEFT,
            new AST\JoinAssociationDeclaration(
                new AST\JoinAssociationPathExpression($componentAlias, $versionableMeta->versionAssociation),
                $this->getVersionComponentAlias($componentAlias),
                null
            )
        );
        $join->conditionalExpression = $this->getVersionConditionalExpression($componentAlias, $componentVersionField, $versionNumberField);
        return $join;
    }

    /**
     * Select additional conditions to select joined version entity
     *
     * @param string $componentAlias
     * @param string $componentVersionField
     * @param string $versionNumberField
     * @return AST\ConditionalPrimary
     */
    protected function getVersionConditionalExpression($componentAlias, $componentVersionField, $versionNumberField)
    {
        $meta = $this->versionableComponents[$componentAlias]['metadata'];
        $class = $meta->name;
        $versions = $this->versionableListener->getVersionsForClass($this->entityManager, $class);
        $pathExpresion1 = new AST\PathExpression(
            null,
            $this->getVersionComponentAlias($componentAlias),
            $versionNumberField
        );
        $pathExpresion1->type = AST\PathExpression::TYPE_STATE_FIELD;
        $pathExpresion2 = new AST\PathExpression(
            null,
            $componentAlias,
            $componentVersionField
        );
        $pathExpresion2->type = AST\PathExpression::TYPE_STATE_FIELD;
        $conditionalExpression = new AST\ConditionalPrimary();
        if (count($versions)) {
            foreach ($versions as $identity => $version) {
                $id = array_combine($meta->getIdentifierFieldNames(), explode('-', $identity));
                $caseConditionExpression = $this->getIdentityConditionalExpression($componentAlias, $id);
                $thenExpression = new AST\Literal(AST\Literal::NUMERIC, $version);
                $whenClause = new AST\WhenClause($caseConditionExpression, $thenExpression);
                $whenClauses[] = $whenClause;
            }
            $rightExpression = new AST\GeneralCaseExpression($whenClauses, $pathExpresion2);
        } else {
            $rightExpression = new AST\SimpleArithmeticExpression(array($pathExpresion2));
        }
        $conditionalExpression->simpleConditionalExpression = new AST\ComparisonExpression(
            new AST\SimpleArithmeticExpression(
                array($pathExpresion1)
            ),
            '=',
            $rightExpression
        );
        return $conditionalExpression;
    }

    /**
     * Return conditional expression comparing primary key of specified query component with identity values from $id
     *
     * @param string $componentAlias
     * @param array $id
     * @return AST\ConditionalTerm
     */
    protected function getIdentityConditionalExpression($componentAlias, array $id)
    {
        $conditionalFactors = array();
        foreach ($id as $field => $value) {
            $pathExpression = new AST\PathExpression(
                null,
                $componentAlias,
                $field
            );
            $pathExpression->type = AST\PathExpression::TYPE_STATE_FIELD;
            $conditionalFactor = new AST\ConditionalPrimary();
            $conditionalFactor->simpleConditionalExpression = new AST\ComparisonExpression(
                new AST\SimpleArithmeticExpression(array($pathExpression)),
                '=',
                new AST\Literal(AST\Literal::STRING, $value)
            );
            $conditionalFactors[] = $conditionalFactor;
        }
        return new AST\ConditionalTerm($conditionalFactors);
    }

    /**
     * Return component alias for version query component
     *
     * @param string $componentAlias
     * @return string
     */
    protected function getVersionComponentAlias($componentAlias)
    {
        return 'ver'.$componentAlias;
    }

    /**
     * Return select expression for version component
     *
     * @param string $componentAlias
     * @return AST\SelectExpression
     */
    protected function getVersionSelectExpression($componentAlias)
    {
        return new AST\SelectExpression(
            $this->getVersionComponentAlias($componentAlias),
            null,
            false
        );
    }

    /**
     * Find VersionableListener in attached EntityManager
     *
     * @throws VersionableException
     * @return VersionableListener
     */
    protected function getVersionableListener()
    {
        $versionableListener = null;
        $em = $this->entityManager;
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof VersionableListener) {
                    $versionableListener = $listener;
                    break;
                }
            }
            if (isset($versionableListener))
                break;
        }
        if (!isset($versionableListener)) {
            throw new VersionableException('VersionableTreeWalker needs VersionableListener attached to its EnityManager');
        }
        return $versionableListener;
    }

    /**
     * Find versionable components and return associated version query components
     *
     * @param array $queryComponents
     * return array
     */
    protected function detectVersionableComponents(array $queryComponents)
    {
        $em = $this->entityManager;
        $versionableComponents = array();
        foreach ($queryComponents as $alias => $component) {
            if (!isset($component['metadata'])) {
                continue;
            }
            $meta = $component['metadata'];
            $versionableMeta = $this->versionableListener->getExtendedMetadata($em, $meta->name);
            if ($versionableMeta->hasVersionableProperties()) {
                $this->versionableComponents[$alias] = $component;
                $versionMeta = $em->getClassMetadata($meta->getAssociationTargetClass($versionableMeta->versionAssociation));
                $this->setQueryComponent('ver'.$alias, array(
                    'metadata' => $versionMeta,
                    'parent' => $alias,
                    'relation' => $meta->getAssociationMapping($versionableMeta->versionAssociation),
                    'map' => null,
                    'nestingLevel' => 0,
                    'token' => null
                ));
            }
        }
        return $versionableComponents;
    }

}
