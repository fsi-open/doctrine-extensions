<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use FSi\DoctrineExtensions\ORM\Query;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticleTranslation;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Section;
use FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository;

class SimpleTest extends BaseORMTest
{
    const CATEGORY = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Category";
    const SECTION = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Section";
    const COMMENT = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Comment";
    const ARTICLE = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Article";
    const ARTICLE_TRANSLATION = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\ArticleTranslation";

    const SECTION_1 = 'Section 1';
    const CATEGORY_1 = 'Category 1';
    const CATEGORY_2 = 'Category 2';
    const POLISH_TITLE_1 = 'Tytuł polski 1';
    const POLISH_TITLE_2 = 'Tytuł polski 2';
    const POLISH_TEASER = 'Wstep polski';
    const POLISH_CONTENTS_1 = 'Treść artukułu po polsku 1';
    const POLISH_CONTENTS_2 = 'Treść artukułu po polsku 2';
    const ENGLISH_TITLE_1 = 'English title 1';
    const ENGLISH_TITLE_2 = 'English title 2';
    const ENGLISH_TEASER = 'English teaser';
    const ENGLISH_CONTENTS_1 = 'English contents of article 1';
    const ENGLISH_CONTENTS_2 = 'English contents of article 2';

    protected $_languagePl = 'pl';

    protected $_languageEn = 'en';

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }

    /**
     * Test simple entity creation with translation its state after $em->flush()
     */
    public function testInsert()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test simple entity creation without translations and adding translation later its state after $em->flush()
     */
    public function testInsertAndAddFirstTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $article->setLocale($this->_languagePl);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test simple entity creation with one translation and adding one later
     */
    public function testInsertAndAddTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::ENGLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languageEn)
        );

        $this->assertAttributeEquals(
            self::ENGLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languageEn)
        );
    }

    /**
     * Test simple entity creation with two translation and check its state after $em->clear(), change default locale and load
     */
    public function testInsertWithTwoTranslationsClearAndLoad()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->flush();

        $this->_em->clear();
        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test simple entity creation with two translations and removing one of them later
     */
    public function testInsertAndRemoveTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->flush();
        $this->_em->clear();

        $this->_translatableListener->setLocale($this->_languageEn);
        $article = $this->_em->find(self::ARTICLE, $article->getId());
        $article->setTitle(null);
        $article->setContents(null);

        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test simple entity creation with translation and reloading it after $em->clear()
     */
    public function testInsertClearAndLoad()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test updating previously created and persisted translation and its state after $em->flush()
     */
    public function testUpdate()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setTitle(self::POLISH_TITLE_2);
        $article->setContents(self::POLISH_CONTENTS_2);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_2,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_2,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test updating previously created and persisted translation and its state after $em->clear()
     */
    public function testUpdateClearAndLoad()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setTitle(self::POLISH_TITLE_2);
        $article->setContents(self::POLISH_CONTENTS_2);
        $this->_em->flush();

        $this->_em->clear();
        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_2,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_2,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_2,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test copying one translation to another
     */
    public function testCopyTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languageEn)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languageEn)
        );
    }

    /**
     * Test entity creation with one translation in default language and check if that translation is loaded after changing language
     * to other
     */
    public function testLoadDefaultTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $this->_translatableListener->setLocale($this->_languageEn);
        $this->_translatableListener->setDefaultLocale($this->_languagePl);
        $this->_logger->enabled = true;
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            $this->_languagePl,
            'locale',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article
        );

        $article->setLocale($this->_languageEn);
        $this->_em->flush();
        $this->_em->clear();
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertAttributeEquals(
                $this->_languageEn,
                'locale',
                $article
        );

        $this->assertAttributeEquals(
                self::POLISH_TITLE_1,
                'title',
                $article
        );

        $this->assertAttributeEquals(
                self::POLISH_CONTENTS_1,
                'contents',
                $article
        );
    }

    /**
     * Test entity creation with two translation and check its state after $em->clear(), change default locale and load with some
     * specific translation
     */
    public function testInsertWithTwoTranslationsClearAndLoadTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->flush();

        $this->_em->clear();
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->_logger->enabled = true;
        $this->_translatableListener->loadTranslation($this->_em, $article, $this->_languageEn);

        $this->assertEquals(
            0,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::ENGLISH_TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::ENGLISH_CONTENTS_1,
            'contents',
            $article
        );
    }

    /**
     * Test if query builder returned by translatable repository has join to translation entity
     * and is constrained to current locale
     */
    public function testTranslatableRepositoryCreateQueryBuilder()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $repository = $this->_em->getRepository(self::ARTICLE);

        $qb = $repository->createTranslatableQueryBuilder('a', 't');

        $this->assertEquals(
            sprintf('SELECT a, t, dt FROM %s a LEFT JOIN a.translations t WITH t.locale = :locale LEFT JOIN a.translations dt WITH dt.locale = :deflocale', self::ARTICLE),
            $qb->getQuery()->getDql(),
            'Wrong DQL returned from QueryBuilder'
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('locale')->getValue(),
            'Parameter :locale has wrong value'
        );
    }

    /**
     * Test if call to getTranslation creates non existent translations
     */
    public function testCreatingNonExistentTranslationThroughRepository()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new \DateTime());
        $this->_em->persist($article);
        $this->_em->flush();

        $translationEn = $repository->getTranslation($article, $this->_languageEn);

        $translationPl = $repository->getTranslation($article, $this->_languagePl);

        $this->assertTrue(
            $article->getTranslations()->contains($translationEn)
        );

        $this->assertTrue(
            $article->getTranslations()->contains($translationPl)
        );

        $this->assertSame(
            $translationEn,
            $article->getTranslations()->get($this->_languageEn)
        );

        $this->assertSame(
            $translationPl,
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertSame(
            $translationPl,
            $repository->getTranslation($article, $this->_languagePl)
        );
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
        $article->setDate(new \DateTime());
        $translationEn = $repository->getTranslation($article, $this->_languageEn);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->persist($translationEn);
        $this->_em->flush();
        $this->_em->clear();

        $article = $repository->find($article->getId());

        $this->assertTrue(
            $repository->hasTranslation($article, $this->_languageEn)
        );

        $this->assertFalse(
            $repository->hasTranslation($article, $this->_languagePl)
        );
    }

    public function testNotOverwritingTranslationForNewObject()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new \DateTime());

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

        $this->assertAttributeEquals(
            self::ENGLISH_TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::ENGLISH_CONTENTS_1,
            'contents',
            $article
        );
    }

    public function testInternalPropertyObserver()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = new Article();
        $article->setDate(new \DateTime());
        $this->_em->persist($article);
        $this->_em->flush();
        $emOid = spl_object_hash($this->_em);

        $propertyObservers = \PHPUnit_Framework_Assert::readAttribute($this->_translatableListener, '_propertyObservers');
        $this->assertArrayHasKey(
            $emOid,
            $propertyObservers
        );

        $this->_em->clear();

        $propertyObservers = \PHPUnit_Framework_Assert::readAttribute($this->_translatableListener, '_propertyObservers');
        $this->assertArrayNotHasKey(
            $emOid,
            $propertyObservers
        );
    }

    public function testPostHydrate()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $repository = $this->_em->getRepository(self::ARTICLE);
        $article = new Article();
        $article->setDate(new \DateTime());

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

        $this->assertTrue(
            $query->getHint(\Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS)
        );

        $articles = $query->execute();
        foreach ($articles as $article) {
            $this->assertAttributeEquals(
                self::ENGLISH_TITLE_1,
                'title',
                $article
            );

            $this->assertAttributeEquals(
                self::ENGLISH_CONTENTS_1,
                'contents',
                $article
            );
        }

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );
    }

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

    private function fillDataForFindTranslatedOneBy()
    {
        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        $article1 = new Article();
        $this->_em->persist($article1);
        $article1->setDate(new \DateTime('2014-01-01 00:00:00'));
        $translationEn = $repository->getTranslation($article1, $this->_languageEn);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setIntroduction(self::ENGLISH_TEASER);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->persist($translationEn);

        $article2 = new Article();
        $this->_em->persist($article2);
        $article2->setDate(new \DateTime('2014-02-02 00:00:00'));
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

        $comment = new Comment();
        $comment->setContent('Lorem');
        $comment->setDate(new \DateTime());
        $comment->setArticle($article1);
        $this->_em->persist($comment);

        $comment = new Comment();
        $comment->setContent('Ipsum');
        $comment->setDate(new \DateTime());
        $comment->setArticle($article2);
        $this->_em->persist($comment);

        $this->_em->flush();
        $this->_em->refresh($article1);
        $this->_em->refresh($article2);
    }

    /**
     * tests that findTranslatedOneBy will return correct entity and if not found throw exception
     */
    public function testFindTranslatedOneByFields()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatedOneBy();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);
        $section = $this->_em->getRepository(self::SECTION)->findOneBy(array('title' => self::SECTION_1));
        $comment = $this->_em->getRepository(self::COMMENT)->findOneBy(array('content' => 'Ipsum'));

        /** @var Article $article */
        $article = $repository->findTranslatedOneBy(array(
            'date' => '2014-02-02 00:00:00', //fiend in Article, not translated
            'title' => self::POLISH_TITLE_1, //field in ArticleTranslation with same name
            'teaser' => self::POLISH_TEASER, //field in ArticleTranslation with different name
            'section' => $section, //field in Article - single value association
            'comments' => $comment, //field in Article - one to many association
        ));

        $this->assertEquals($this->_languagePl, $article->getLocale());
        $this->assertEquals(self::POLISH_TITLE_1, $article->getTitle());
        $this->assertEquals(self::POLISH_TEASER, $article->getTeaser());
        $this->assertEquals(self::POLISH_CONTENTS_1, $article->getContents());

        $this->setExpectedException('\Doctrine\ORM\NoResultException');

        $repository->findTranslatedOneBy(array(
            'date' => '2014-01-01 00:00:01', //value that not exists
        ));
    }

    /**
     * test that findTranslatedOneBy will return fields from default translation
     * if translation in current locale was not found
     */
    public function testFindTranslatedOneByLocaleFallback()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatedOneBy();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);

        /** @var Article $article */
        $article = $repository->findTranslatedOneBy(array(
            'date' => '2014-01-01 00:00:00',
        ));

        $this->assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
        $this->assertEquals(self::ENGLISH_TEASER, $article->getTeaser());
        $this->assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
    }

    /**
     * if field is many to many association findTranslatedOneBy should throw exception
     */
    public function testFindTranslatedOneByManyToManyAssociationField()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $this->fillDataForFindTranslatedOneBy();

        /** @var TranslatableRepository $repository */
        $repository = $this->_em->getRepository(self::ARTICLE);
        $category = $this->_em->getRepository(self::CATEGORY)->findOneBy(array('title' => self::CATEGORY_2));

        $this->setExpectedException(
            '\FSi\DoctrineExtensions\Exception\ConditionException',
            'field categories cannot be used since its ManyToMany association field'
        );

        $repository->findTranslatedOneBy(array(
            'categories' => $category,
        ));
    }

}
