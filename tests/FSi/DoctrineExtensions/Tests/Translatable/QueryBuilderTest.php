<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable;

use Doctrine\ORM\Query\Expr;
use FSi\DoctrineExtensions\Exception\InvalidArgumentException;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticlePage;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticleTranslation;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Section;
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder;

class QueryBuilderTest extends BaseTranslatableTest
{
    public function testJoinTranslationWithWrongJoinType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown join type "RIGHT"');

        $qb = new QueryBuilder($this->entityManager);
        $qb->from(Article::class, 'a')->joinTranslations('a.translations', 'RIGHT');
    }

    public function testJoinTranslationWithAllDefaultArguments()
    {
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a')->from(Article::class, 'a')->joinTranslations('a.translations');

        $this->assertEquals(
            $this->normalizeDql(sprintf(
                'SELECT a FROM %s a LEFT JOIN a.translations atranslations',
                Article::class
            )),
            $qb->getDQL()
        );

        $qb->getQuery()->execute();
    }

    public function testJoinTranslationWithDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->joinTranslations('a.translations');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('atranslationsenloc'));

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testInnerJoinTranslationWithCustomLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->joinTranslations('a.translations', Expr\Join::INNER_JOIN, self::LANGUAGE_PL);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    INNER JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('atranslationsplloc'));

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testJoinTranslationWithAllCustomParameters()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->joinTranslations('a.translations', Expr\Join::INNER_JOIN, self::LANGUAGE_PL, 't', 'locale');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    INNER JOIN a.translations t WITH t.locale = :locale',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('locale'));

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('locale')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithCurrentLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE atranslationsen.title = :atitleval',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithDefaultLocale()
    {
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE WHEN atranslationspl.id IS NOT NULL THEN atranslationspl.title ELSE atranslationsen.title END = :atitleval',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithCustomAndDefaultLocale()
    {
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title', self::LANGUAGE_DE);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsde WITH atranslationsde.locale = :atranslationsdeloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE WHEN atranslationsde.id IS NOT NULL THEN atranslationsde.title ELSE atranslationsen.title END = :atitleval',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_DE,
            $qb->getParameter('atranslationsdeloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithSameCurrentAndDefaultLocale()
    {
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE atranslationsen.title = :atitleval',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithCurrentLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a')->from(Article::class, 'a')->addTranslatableOrderBY('a', 'title', 'ASC');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atranslationsen.title ASC',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithDefaultLocale()
    {
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableOrderBy('a', 'title', 'DESC');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a, CASE WHEN atranslationspl.id IS NOT NULL THEN atranslationspl.title ELSE atranslationsen.title END HIDDEN atitle
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atitle DESC',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithCustomAndDefaultLocale()
    {
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableOrderBy('a', 'title', 'DESC', self::LANGUAGE_DE);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a, CASE WHEN atranslationsde.id IS NOT NULL THEN atranslationsde.title ELSE atranslationsen.title END HIDDEN atitle
                FROM %s a
                    LEFT JOIN a.translations atranslationsde WITH atranslationsde.locale = :atranslationsdeloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atitle DESC',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_DE,
            $qb->getParameter('atranslationsdeloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithSameCurrentAndDefaultLocale()
    {
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableOrderBy('a', 'title', 'DESC');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atranslationsen.title DESC',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithNull()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE SIZE(atranslationspl.comments) = 0',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnCollectionWithNull()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'categories', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                WHERE SIZE(a.categories) = 0',
                Article::class
            )),
            $qb->getDQL()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithObject()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE :acommentsval MEMBER OF atranslationspl.comments',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnCollectionWithObject()
    {
        $category = new Category();
        $category->setId(1);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'categories', $category);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                WHERE :acategoriesval MEMBER OF a.categories',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $category,
            $qb->getParameter('acategoriesval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithArray()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = [$comment1, $comment2];

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN atranslationspl.comments atranslationsplcommentsjoin
                WHERE atranslationsplcommentsjoin IN(:acommentsval)',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnCollectionWithArray()
    {
        $category1 = new Category();
        $category1->setId(1);
        $category2 = new Category();
        $category2->setId(2);
        $whereCategories = [$category1, $category2];

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'categories', $whereCategories);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.categories acategoriesjoin
                WHERE acategoriesjoin IN(:acategoriesval)',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $whereCategories,
            $qb->getParameter('acategoriesval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithNullAndSameDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_PL);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE SIZE(atranslationspl.comments) = 0',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithObjectAndSameDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_PL);

        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE :acommentsval MEMBER OF atranslationspl.comments',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithArrayAndSameDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_PL);

        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = [$comment1, $comment2];

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN atranslationspl.comments atranslationsplcommentsjoin
                WHERE atranslationsplcommentsjoin IN(:acommentsval)',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithNullAndDifferentDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE
                    WHEN atranslationspl.id IS NOT NULL AND SIZE(atranslationspl.comments) = 0 THEN TRUE
                    WHEN SIZE(atranslationsen.comments) = 0 THEN TRUE
                    ELSE FALSE
                END = TRUE',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithObjectAndDifferentDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE
                    WHEN atranslationspl.id IS NOT NULL AND :acommentsval MEMBER OF atranslationspl.comments THEN TRUE
                    WHEN :acommentsval MEMBER OF atranslationsen.comments THEN TRUE
                    ELSE FALSE
                END = TRUE',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithArrayAndDifferentDefaultLocale()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = [$comment1, $comment2];

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('a');
        $qb->from(Article::class, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                    LEFT JOIN atranslationspl.comments atranslationsplcommentsjoin
                    LEFT JOIN atranslationsen.comments atranslationsencommentsjoin
                WHERE CASE
                    WHEN atranslationspl.id IS NOT NULL AND atranslationsplcommentsjoin IN(:acommentsval) THEN TRUE
                    WHEN atranslationsencommentsjoin IN(:acommentsval) THEN TRUE
                    ELSE FALSE
                END = TRUE',
                Article::class
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Category::class,
            Section::class,
            Comment::class,
            Article::class,
            ArticleTranslation::class,
            ArticlePage::class
        ];
    }

    private function normalizeDql($dql)
    {
        return preg_replace('/\s+/', ' ', trim($dql));
    }
}
