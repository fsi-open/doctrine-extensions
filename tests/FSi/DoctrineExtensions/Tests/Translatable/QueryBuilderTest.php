<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use Doctrine\ORM\Query\Expr;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder;

class QueryBuilderTest extends BaseTranslatableTest
{
    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::SECTION,
            self::COMMENT,
            self::ARTICLE,
            self::ARTICLE_TRANSLATION
        );
    }

    public function testJoinTranslationWithAllDefaultArguments()
    {
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations');

        $this->assertEquals(
            'SELECT FROM FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article a LEFT JOIN a.translations atranslations',
            $qb->getDQL()
        );
    }

    public function testJoinTranslationWithDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations');

        $this->assertEquals(
            'SELECT FROM FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article a LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc',
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('atranslationsenloc'));

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );
    }

    public function testInnerJoinTranslationWithCustomLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations', Expr\Join::INNER_JOIN, $this->_languagePl);

        $this->assertEquals(
            'SELECT FROM FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article a INNER JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc',
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('atranslationsplloc'));

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );
    }

    public function testJoinTranslationWithAllCustomParameters()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations', Expr\Join::INNER_JOIN, $this->_languagePl, 't', 'locale');

        $this->assertEquals(
            sprintf('SELECT FROM %s a INNER JOIN a.translations t WITH t.locale = :locale', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('locale'));

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('locale')->getValue()
        );
    }

    public function testTranslatableWhereWithCurrentLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            sprintf('SELECT FROM %s a LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc WHERE atranslationsen.title = :atitleval', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );
    }

    public function testTranslatableWhereWithDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languagePl);
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            sprintf('SELECT CASE WHEN atranslationspl.id IS NOT NULL THEN atranslationspl.title ELSE atranslationsen.title END HIDDEN atitle FROM %s a LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc WHERE atitle = :atitleval', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );
    }

    public function testTranslatableWhereWithSameCurrentAndDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            sprintf('SELECT FROM %s a LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc WHERE atranslationsen.title = :atitleval', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );
    }

    public function testTranslatableWhereOnOneToManyCollectionWithNull()
    {
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            sprintf('SELECT FROM %s a LEFT JOIN a.comments acommentsjoin WHERE acommentsjoin IS NULL', self::ARTICLE),
            $qb->getDQL()
        );
    }

    public function testTranslatableWhereOnManyToManyCollectionWithNull()
    {
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'categories', null);

        $this->assertEquals(
            sprintf('SELECT FROM %s a LEFT JOIN a.categories acategoriesjoin WHERE acategoriesjoin IS NULL', self::ARTICLE),
            $qb->getDQL()
        );
    }

    public function testTranslatableWhereOnOneToManyCollectionWithObject()
    {
        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            sprintf('SELECT FROM %s a WHERE :acommentsval MEMBER OF a.comments', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );
    }

    public function testTranslatableWhereOnManyToManyCollectionWithObject()
    {
        $category = new Category();
        $category->setId(1);

        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'categories', $category);

        $this->assertEquals(
            sprintf('SELECT FROM %s a WHERE :acategoriesval MEMBER OF a.categories', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $category,
            $qb->getParameter('acategoriesval')->getValue()
        );
    }

    public function testTranslatableWhereOnOneToManyCollectionWithArray()
    {
        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = array($comment1, $comment2);

        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            sprintf('SELECT FROM %s a LEFT JOIN a.comments acommentsjoin WHERE acommentsjoin IN(:acommentsval)', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );
    }

    public function testTranslatableWhereOnManyToManyCollectionWithArray()
    {
        $category1 = new Category();
        $category1->setId(1);
        $category2 = new Category();
        $category2->setId(2);
        $whereCategories = array($category1, $category2);

        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'categories', $whereCategories);

        $this->assertEquals(
            sprintf('SELECT FROM %s a LEFT JOIN a.categories acategoriesjoin WHERE acategoriesjoin IN(:acategoriesval)', self::ARTICLE),
            $qb->getDQL()
        );

        $this->assertEquals(
            $whereCategories,
            $qb->getParameter('acategoriesval')->getValue()
        );
    }
}
