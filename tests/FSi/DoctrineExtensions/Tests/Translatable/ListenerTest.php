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
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithLocalelessTranslationTranslation;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutLocale;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutTranslations;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithPersistentLocale;
use FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository;
use FSi\DoctrineExtensions\Translatable\Exception\MappingException;
use FSi\DoctrineExtensions\Translatable\Exception\RuntimeException;
use SplFileInfo;

class ListenerTest extends BaseTranslatableTest
{
    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    /**
     * Test simple entity creation with translation its state after $em->flush()
     */
    public function testInsert()
    {
        $article = $this->createArticle();
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
            self::POLISH_SUBTITLE,
            'subtitle',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    /**
     * Test simple entity creation without translations and adding translation
     * later its state after $em->flush()
     */
    public function testInsertAndAddFirstTranslation()
    {
        $article = new Article();
        $article->setDate(new DateTime());
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setTitle(self::POLISH_TITLE_1);
        $article->setSubtitle(self::POLISH_SUBTITLE);
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
            self::POLISH_SUBTITLE,
            'subtitle',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $article->getTranslations()->get($this->_languagePl)
        );
    }

    public function testNotInsertTranslation()
    {
        $article = new Article();
        $article->setDate(new DateTime());
        $article->setLocale($this->_languagePl);
        $this->_em->persist($article);
        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(0, $article->getTranslations()->count());
    }

    /**
     * Test simple entity creation with one translation and adding one later
     */
    public function testInsertAndAddTranslation()
    {
        $article = $this->createArticle();
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setSubtitle(self::ENGLISH_SUBTITLE);
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
            self::ENGLISH_SUBTITLE,
            'subtitle',
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
        $article = $this->createArticle();
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setSubtitle(self::ENGLISH_SUBTITLE);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->flush();

        $this->_em->clear();
        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(self::POLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::POLISH_SUBTITLE, 'subtitle', $article);
        $this->assertAttributeEquals(self::POLISH_CONTENTS_1, 'contents', $article);

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $article->getTranslations()->get($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::POLISH_SUBTITLE,
            'subtitle',
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
        $article->setDate(new DateTime());
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
            4,
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
        $article = $this->createArticle();
        $this->persistAndFlush($article);

        $this->_logger->enabled = true;
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(self::POLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::POLISH_CONTENTS_1, 'contents', $article);

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
        $article = $this->createArticle();
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
        $article = $this->createArticle();
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
            5,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertEquals(
            1,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(self::POLISH_TITLE_2, 'title', $article);
        $this->assertAttributeEquals(self::POLISH_CONTENTS_2, 'contents', $article);

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
        $article = $this->createArticle();
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

        $this->assertAttributeEquals(self::POLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::POLISH_CONTENTS_1, 'contents', $article);

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
        $article = $this->createArticle();
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $this->_translatableListener->setLocale($this->_languageEn);
        $this->_translatableListener->setDefaultLocale($this->_languagePl);
        $this->_logger->enabled = true;
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertAttributeEquals($this->_languagePl, 'locale', $article);
        $this->assertAttributeEquals(self::POLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::POLISH_SUBTITLE, 'subtitle', $article);
        $this->assertAttributeEquals(self::POLISH_CONTENTS_1, 'contents', $article);

        $article->setLocale($this->_languageEn);
        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em->find(self::ARTICLE, $article->getId());
        $this->assertAttributeEquals($this->_languageEn, 'locale', $article);
        $this->assertAttributeEquals(self::POLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::POLISH_CONTENTS_1, 'contents', $article);
    }

    /**
     * Assert that an empty string returned in Article::getLocale() will not mask
     * the fact that no locale was set for either the listener or the entity.
     */
    public function testCurrentLocaleSetToNull()
    {
        $this->_translatableListener->setDefaultLocale($this->_languagePl);
        $this->_translatableListener->setLocale(null);

        $article = new Article();
        $article->setDate(new DateTime());
        $article->setLocale(null);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);

        $this->expectException(
            RuntimeException::class,
            "Neither object's locale nor the current locale was set for translatable properties"
        );

        $this->_em->persist($article);
        $this->_em->flush();
    }

    /**
     * Test entity creation with two translation and check its state after $em->clear(), change default locale and load with some
     * specific translation
     */
    public function testInsertWithTwoTranslationsClearAndLoadTranslation()
    {
        $article = $this->createArticle();
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
            2,
            count($article->getTranslations()),
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(self::ENGLISH_TITLE_1, 'title', $article);
        $this->assertAttributeEquals(self::ENGLISH_CONTENTS_1, 'contents', $article);
    }

    /**
     * Test translatable and uploadable properties
     */
    public function testTranslatableUplodableProperties()
    {
        $article = $this->createArticle();
        $article->setIntroImage(new SplFileInfo(TESTS_PATH . self::TEST_FILE1));
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $article->setIntroImage(new SplFileInfo(TESTS_PATH . self::TEST_FILE2));
        $this->_em->flush();

        $this->_em->clear();
        $this->_translatableListener->setLocale($this->_languagePl);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $file1 = $article->getIntroImage()->getKey();
        $this->assertFileExists(FILESYSTEM1 . $file1);

        $this->_em->clear();
        $this->_translatableListener->setLocale($this->_languageEn);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $file2 = $article->getIntroImage()->getKey();
        $this->assertFileExists(FILESYSTEM1 . $file2);

        $this->assertNotSame($file1, $file2);
    }

    public function testPostHydrate()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        /* @var $repository TranslatableRepository */
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

    public function testTranslatableWithoutLocaleProperty()
    {
        $this->expectException(
            MappingException::class,
            sprintf(
                "Entity '%s' has translatable properties so it must have property"
                . " marked with @Translatable\Language annotation",
                TranslatableWithoutLocale::class
            )
        );

        $this->_translatableListener->getExtendedMetadata(
            $this->_em,
            TranslatableWithoutLocale::class
        );
    }

    public function testTranslatableWithoutTranslations()
    {
        $this->expectException(
            MappingException::class,
            sprintf(
                "Field 'translations' in entity '%s' has to be a OneToMany association",
                TranslatableWithoutTranslations::class
            )
        );

        $this->_translatableListener->getExtendedMetadata(
            $this->_em,
            TranslatableWithoutTranslations::class
        );
    }

    public function testTranslatableWithPersistentLocale()
    {
        $this->expectException(
            MappingException::class,
            sprintf(
                "Entity '%s' seems to be a translatable entity so its 'locale' field"
                . " must not be persistent",
                TranslatableWithPersistentLocale::class
            )
        );

        $this->_translatableListener->getExtendedMetadata(
            $this->_em,
            TranslatableWithPersistentLocale::class
        );
    }

    public function testTranslationsWithoutPersistentLocale()
    {
        $this->expectException(
            MappingException::class,
            sprintf(
                "Entity '%s' seems to be a translation entity so its 'locale' field must be persistent",
                TranslatableWithLocalelessTranslationTranslation::class
            )
        );

        $this->_translatableListener->getExtendedMetadata(
            $this->_em,
            TranslatableWithLocalelessTranslationTranslation::class
        );
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
}
