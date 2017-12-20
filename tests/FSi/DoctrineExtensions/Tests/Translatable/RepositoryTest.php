<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use DateTime;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Section;
use FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository;

class RepositoryTest extends BaseTranslatableTest
{
    /**
     * Test if call to getTranslation creates non existent translations
     */
    public function testCreatingNonExistentTranslationThroughRepository()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new DateTime());
        $this->_em->persist($article);
        $this->_em->flush();

        $translationEn = $repository->getTranslation($article, $this->_languageEn);
        $this->assertTrue($article->getTranslations()->contains($translationEn));

        $translationPl = $repository->getTranslation($article, $this->_languagePl);
        $this->assertTrue($article->getTranslations()->contains($translationPl));

        $this->assertSame($translationEn, $article->getTranslations()->get($this->_languageEn));
        $this->assertSame($translationPl, $article->getTranslations()->get($this->_languagePl));
        $this->assertSame($translationPl, $repository->getTranslation($article, $this->_languagePl));
    }

    /**
     * Test if call to hasTranslation returns true for existing translations
     * and false otherwise
     */
    public function testCheckingIfTranslationExistsThroughRepository()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new DateTime());
        $translationEn = $repository->getTranslation($article, $this->_languageEn);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->persist($translationEn);
        $this->_em->flush();
        $this->_em->clear();

        $article = $repository->find($article->getId());
        $this->assertTrue($repository->hasTranslation($article, $this->_languageEn));
        $this->assertFalse($repository->hasTranslation($article, $this->_languagePl));
    }

    public function testNotOverwritingTranslationForNewObject()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new DateTime());

        $translationEn = $repository->getTranslation($article, $this->_languageEn);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $translationPl = $repository->getTranslation($article, $this->_languagePl);
        $translationPl->setTitle(self::POLISH_TITLE_1);
        $translationPl->setContents(self::POLISH_CONTENTS_1);

        $this->_em->persist($translationEn);
        $this->_em->persist($translationPl);
        $this->_em->persist($article);
        $this->_em->flush();

        $this->_em->refresh($article);

        $this->assertEquals(
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(self::ENGLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::ENGLISH_CONTENTS_1, 'contents', $article);
    }

    /**
     * Test if query builder returned by translatable repository has join to translation entity
     * and is constrained to current locale
     */
    public function testTranslatableRepositoryCreateQueryBuilder()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $qb = $this->_em->getRepository(self::ARTICLE)->createTranslatableQueryBuilder('a', 't');
        $this->assertEquals(
            sprintf(
                'SELECT a, t, dt FROM %s a LEFT JOIN a.translations t WITH t.locale = :locale'
                . ' LEFT JOIN a.translations dt WITH dt.locale = :deflocale',
                self::ARTICLE
            ),
            $qb->getQuery()->getDql(),
            'Wrong DQL returned from QueryBuilder'
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('locale')->getValue(),
            'Parameter :locale has wrong value'
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('deflocale')->getValue(),
            'Parameter :deflocale has wrong value'
        );
    }

    /**
     * Test if query builder returned by translatable repository has join to translation entity
     * and is constrained to current locale
     */
    public function testTranslatableRepositoryCreateQueryBuilderWithLocaleSameAsDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $qb = $this->_em->getRepository(self::ARTICLE)->createTranslatableQueryBuilder('a', 't');
        $this->assertEquals(
            sprintf(
                'SELECT a, t FROM %s a LEFT JOIN a.translations t WITH t.locale = :locale',
                self::ARTICLE
            ),
            $qb->getQuery()->getDql(),
            'Wrong DQL returned from QueryBuilder'
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('locale')->getValue(),
            'Parameter :locale has wrong value'
        );
    }

    public function testPostHydrateWithTranslatableQueryBuilder()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new DateTime());

        $translationEn = $repository->getTranslation($article, $this->_languageEn);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $translationPl = $repository->getTranslation($article, $this->_languagePl);
        $translationPl->setTitle(self::POLISH_TITLE_1);
        $translationPl->setContents(self::POLISH_CONTENTS_1);

        $this->_em->persist($translationEn);
        $this->_em->persist($translationPl);
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $this->_logger->enabled = true;
        $query = $repository->createTranslatableQueryBuilder('a', 't', 'dt')->getQuery();

        $articles = $query->execute();
        foreach ($articles as $article) {
            $this->assertAttributeEquals(self::ENGLISH_TITLE_1, 'title', $article);
            $this->assertAttributeEquals(self::ENGLISH_CONTENTS_1, 'contents', $article);
        }

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );
    }

    /**
     * tests that findTranslatableBy will return correct entity
     */
    public function testFindTranslatableByFields()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);
        $category = $this->_em->getRepository(self::CATEGORY)->findOneBy(['title' => self::CATEGORY_1]);
        $section = $this->_em->getRepository(self::SECTION)->findOneBy(['title' => self::SECTION_1]);
        $comment = $this->_em->getRepository(self::COMMENT)->findOneBy(['content' => 'Ipsum']);

        $this->_logger->enabled = true;
        /** @var Article $article */
        $articles = $repository->findTranslatableBy([
            'date' => '2014-02-02 00:00:00', //field in Article, not translated
            'title' => self::POLISH_TITLE_1, //field in ArticleTranslation with same name
            'teaser' => self::POLISH_TEASER, //field in ArticleTranslation with different name
            'section' => $section, //field in Article - single value association
            'categories' => $category, //field in Article - collection value association
            'comments' => $comment, //translatable property in Article - one to many association
        ], ['date' => 'ASC', 'title' => 'DESC']);

        $this->assertCount(1, $articles);
        $this->assertEquals($this->_languagePl, $articles[0]->getLocale());
        $this->assertEquals(self::POLISH_TITLE_1, $articles[0]->getTitle());
        $this->assertEquals(self::POLISH_TEASER, $articles[0]->getTeaser());
        $this->assertEquals(self::POLISH_CONTENTS_1, $articles[0]->getContents());
    }

    /**
     * test that findTranslatableBy will return fields from default translation
     * if translation in current locale was not found
     */
    public function testFindTranslatableByLocaleFallback()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        /** @var Article $article */
        $articles = $repository->findTranslatableBy(['date' => '2014-01-01 00:00:00']);

        $this->assertEquals(self::ENGLISH_TITLE_1, $articles[0]->getTitle());
        $this->assertEquals(self::ENGLISH_TEASER, $articles[0]->getTeaser());
        $this->assertEquals(self::ENGLISH_CONTENTS_1, $articles[0]->getContents());
    }

    /**
     * test that findTranslatableBy will return fields from translation in specified locale
     */
    public function testFindTranslatableByCustomLocale()
    {
        $this->_translatableListener->setLocale($this->_languageDe);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        /** @var Article $article */
        $articles = $repository->findTranslatableBy(
            ['date' => '2014-01-01 00:00:00'],
            null,
            null,
            null,
            $this->_languagePl
        );

        $this->assertEquals(self::ENGLISH_TITLE_1, $articles[0]->getTitle());
        $this->assertEquals(self::ENGLISH_TEASER, $articles[0]->getTeaser());
        $this->assertEquals(self::ENGLISH_CONTENTS_1, $articles[0]->getContents());
    }

    /**
     * tests that findTranslatableOneBy will return correct entity and if not found throw exception
     */
    public function testFindTranslatableOneByFields()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);
        $category = $this->_em->getRepository(self::CATEGORY)->findOneBy(['title' => self::CATEGORY_1]);
        $section = $this->_em->getRepository(self::SECTION)->findOneBy(['title' => self::SECTION_1]);
        $comment1 = $this->_em->getRepository(self::COMMENT)->findOneBy(['content' => 'Ipsum']);
        $comment2 = $this->_em->getRepository(self::COMMENT)->findOneBy(['content' => 'Lorem']);

        /** @var Article $article */
        $article = $repository->findTranslatableOneBy([
            'date' => '2014-02-02 00:00:00', //field in Article, not translated
            'title' => self::POLISH_TITLE_1, //field in ArticleTranslation with same name
            'teaser' => self::POLISH_TEASER, //field in ArticleTranslation with different name
            'section' => $section, //field in Article - single value association
            'categories' => $category, //field in Article - collection value association
            'comments' => [$comment1, $comment2], //translatable property in Article - one to many association
        ]);

        $this->assertEquals($this->_languagePl, $article->getLocale());
        $this->assertEquals(self::POLISH_TITLE_1, $article->getTitle());
        $this->assertEquals(self::POLISH_TEASER, $article->getTeaser());
        $this->assertEquals(self::POLISH_CONTENTS_1, $article->getContents());

        $this->expectException('\Doctrine\ORM\NoResultException');

        //value that not exists
        $repository->findTranslatableOneBy(['date' => '2014-01-01 00:00:01']);
    }

    /**
     * test that findTranslatableOneBy will return fields from default translation
     * if translation in current locale was not found
     */
    public function testFindTranslatableOneByLocaleFallback()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        /** @var Article $article */
        $article = $repository->findTranslatableOneBy(['date' => '2014-01-01 00:00:00']);
        $this->assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
        $this->assertEquals(self::ENGLISH_TEASER, $article->getTeaser());
        $this->assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
    }

    /**
     * test that findTranslatableOneBy will return fields from translation in specified locale
     */
    public function testFindTranslatableOneByWithCustomLocale()
    {
        $this->_translatableListener->setLocale($this->_languageDe);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        /** @var Article $article */
        $article = $repository->findTranslatableOneBy(
            ['date' => '2014-01-01 00:00:00'],
            null,
            $this->_languagePl
        );

        $this->assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
        $this->assertEquals(self::ENGLISH_TEASER, $article->getTeaser());
        $this->assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::SECTION,
            self::COMMENT,
            self::ARTICLE,
            self::ARTICLE_TRANSLATION,
            self::ARTICLE_PAGE
        ];
    }

    private function fillDataForFindTranslatable()
    {
        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        $article1 = new Article();
        $this->_em->persist($article1);
        $article1->setDate(new DateTime('2014-01-01 00:00:00'));
        $translationEn = $repository->getTranslation($article1, $this->_languageEn);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setIntroduction(self::ENGLISH_TEASER);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->persist($translationEn);

        $article2 = new Article();
        $this->_em->persist($article2);
        $article2->setDate(new DateTime('2014-02-02 00:00:00'));
        $translationPl = $repository->getTranslation($article2, $this->_languagePl);
        $translationPl->setTitle(self::POLISH_TITLE_1);
        $translationPl->setIntroduction(self::POLISH_TEASER);
        $translationPl->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($translationPl);

        $category1 = new Category();
        $category1->setTitle(self::CATEGORY_1);
        $article1->addCategory($category1);
        $article2->addCategory($category1);
        $this->_em->persist($category1);

        $section = new Section();
        $section->setTitle(self::SECTION_1);
        $article1->setSection($section);
        $article2->setSection($section);
        $this->_em->persist($section);
        $translationPl->addComment(new Comment('Lorem'));
        $translationPl->addComment(new Comment('Ipsum'));

        $this->_em->flush();
        $this->_em->refresh($article1);
        $this->_em->refresh($article2);
    }
}
