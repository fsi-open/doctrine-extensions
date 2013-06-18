<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
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
     * Test if translatable entities loaded using TrasnlatableTreeWalker have already loaded translations
     */
    public function testQueryWalker()
    {
        $category = new Category();
        $category->setTitle(self::CATEGORY_1);
        $this->_em->persist($category);

        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setLocale($this->_languagePl);
        $article->setTitle(self::POLISH_TITLE_1);
        $article->setContents(self::POLISH_CONTENTS_1);
        $article->addCategory($category);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setLocale($this->_languageEn);
        $article->setTitle(self::ENGLISH_TITLE_1);
        $article->setContents(self::ENGLISH_CONTENTS_1);
        $this->_em->flush();

        $article2 = new Article();
        $article2->setDate(new \DateTime());
        $article2->setLocale($this->_languageEn);
        $article2->setTitle(self::ENGLISH_TITLE_2);
        $article2->setContents(self::ENGLISH_CONTENTS_2);
        $article2->addCategory($category);
        $this->_em->persist($article2);
        $this->_em->flush();

        $this->_em->clear();
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $query = $this->_em->createQuery("SELECT c, a FROM ".self::CATEGORY." AS c JOIN c.articles AS a ORDER BY a.id");
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtensions\Translatable\Query\TranslatableTreeWalker'));
        $query->setHydrationMode(\FSi\DoctrineExtensions\ORM\Query::HYDRATE_OBJECT);
        $this->_logger->enabled = true;
        $categories = $query->execute();
        $articles = $categories[0]->getArticles();

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Loading with tree walker hint executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            $this->_languagePl,
            'locale',
            $articles[0]
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $articles[0]
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $articles[0]
        );

        $this->assertAttributeEquals(
            $this->_languageEn,
            'locale',
            $articles[1]
        );

        $this->assertAttributeEquals(
            self::ENGLISH_TITLE_2,
            'title',
            $articles[1]
        );

        $this->assertAttributeEquals(
            self::ENGLISH_CONTENTS_2,
            'contents',
            $articles[1]
        );

        $this->_em->clear();
        $this->_translatableListener->setDefaultLocale(null);
        $query = $this->_em->createQuery("SELECT a FROM ".self::ARTICLE." AS a ORDER BY a.id");
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtensions\Translatable\Query\TranslatableTreeWalker'));
        $query->setHydrationMode(\FSi\DoctrineExtensions\ORM\Query::HYDRATE_OBJECT);
        $articles = $query->execute();

        $this->assertCount(
            1,
            $articles
        );

        $this->assertAttributeEquals(
            $this->_languagePl,
            'locale',
            $articles[0]
        );

        $this->assertAttributeEquals(
            self::POLISH_TITLE_1,
            'title',
            $articles[0]
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'contents',
            $articles[0]
        );

        $query = $this->_em->createQuery("SELECT a FROM ".self::ARTICLE." AS a ORDER BY a.id");
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtensions\Translatable\Query\TranslatableTreeWalker'));
        $this->setExpectedException('FSi\DoctrineExtensions\Translatable\Exception\RuntimeException');
        $query->execute();
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
