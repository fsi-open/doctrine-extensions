<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticleTranslation;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

class SimpleTest extends BaseORMTest
{
    const CATEGORY = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Category";
    const ARTICLE = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Article";
    const ARTICLE_TRANSLATION = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\ArticleTranslation";

    const CATEGORY_1 = 'Category 1';
    const POLISH_TITLE_1 = 'Tytuł polski 1';
    const POLISH_TITLE_2 = 'Tytuł polski 2';
    const POLISH_CONTENTS_1 = 'Treść artukułu po polsku 1';
    const POLISH_CONTENTS_2 = 'Treść artukułu po polsku 2';
    const ENGLISH_TITLE_1 = 'English title 1';
    const ENGLISH_TITLE_2 = 'English title 2';
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
        $repository = $this->_em->getRepository(self::ARTICLE);

        $qb = $repository->createTranslatableQueryBuilder('a', 't');

        $this->assertEquals(
            sprintf('SELECT a FROM %s a LEFT JOIN a.translations t WITH t.locale = :locale', self::ARTICLE),
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

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::ARTICLE,
            self::ARTICLE_TRANSLATION
        );
    }

}
