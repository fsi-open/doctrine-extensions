<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;

class ListenerTest extends BaseTranslatableTest
{
    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

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

    public function testNotInsertTranslation()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
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

        $this->assertEquals(
            0,
            $article->getTranslations()->count()
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
            3,
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
            3,
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
            3,
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
            3,
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
            1,
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
     * Test translatable and uploadable properties
     */
    public function testTranslatableUplodableProperties()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $article->setIntroImage(new \SplFileInfo(TESTS_PATH . self::TEST_FILE1));
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $article->setIntroImage(new \SplFileInfo(TESTS_PATH . self::TEST_FILE2));
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
            2,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );
    }

    public function testTranslatableWithoutLocaleProperty()
    {
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Translatable\Exception\MappingException',
            'Entity \'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutLocale\' has translatable properties so it must have property marked with @Translatable\Language annotation'
        );

        $this->_translatableListener->getExtendedMetadata($this->_em, 'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutLocale');
    }

    public function testTranslatableWithoutTranslations()
    {
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Translatable\Exception\MappingException',
            'Field \'translations\' in entity \'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutTranslations\' has to be a OneToMany association'
        );

        $this->_translatableListener->getExtendedMetadata($this->_em, 'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutTranslations');
    }

    public function testTranslatableWithPersistentLocale()
    {
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Translatable\Exception\MappingException',
            'Entity \'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithPersistentLocale\' seems to be a translatable entity so its \'locale\' field must not be persistent'
        );

        $this->_translatableListener->getExtendedMetadata($this->_em, 'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithPersistentLocale');
    }

    public function testTranslationsWithoutPersistentLocale()
    {
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Translatable\Exception\MappingException',
            'Entity \'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithLocalelessTranslationTranslation\' seems to be a translation entity so its \'locale\' field must be persistent'
        );

        $this->_translatableListener->getExtendedMetadata($this->_em, 'FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithLocalelessTranslationTranslation');
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

}
