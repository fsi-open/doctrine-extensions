<?php

namespace FSi\DoctrineExtension\Tests\Versionable;

use FSi\DoctrineExtension\Tests\Versionable\Fixture\Category;
use FSi\DoctrineExtension\Tests\Versionable\Fixture\Article;
use FSi\DoctrineExtension\Tests\Versionable\Fixture\ArticleVersion;
use FSi\DoctrineExtension\Tests\Tool\BaseORMTest;

/**
 * These are tests for large object storage
 *
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class SimpleTest extends BaseORMTest
{
    const CATEGORY = "FSi\\DoctrineExtension\\Tests\\Versionable\\Fixture\\Category";
    const ARTICLE = "FSi\\DoctrineExtension\\Tests\\Versionable\\Fixture\\Article";
    const ARTICLE_VERSION = "FSi\\DoctrineExtension\\Tests\\Versionable\\Fixture\\ArticleVersion";

    const TITLE_1 = 'Title 1';
    const TITLE_2 = 'Title 2';
    const CONTENTS_1 = 'Contents of article 1';
    const CONTENTS_2 = 'Contents of article 2';
    const CATEGORY_1 = 'Category 1';

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }

    /**
     * Test simple entity creation without any versionable property set and verify object's state after $em->flush()
     */
    public function testInsertWithoutVersions()
    {
        $article = new Article();
        $this->_em->persist($article);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEmpty(
            'title',
            $article
        );

        $this->assertAttributeEmpty(
            'contents',
            $article
        );

        $this->assertAttributeEmpty(
            'publishedVersion',
            $article
        );

        $this->assertEquals(
            0,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
     * Test simple entity creation without any versionable property set and verify object's state after $em->flush()
     */
    public function testInsertWithVersion()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
        	'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            1,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
     * Test simple entity creation without any versionable property set, clear, reload and verify object's state after $em->flush()
     */
    public function testInsertWithVersionClearAndReload()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $this->_logger->enabled = true;
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Reloading executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            1,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
     * Test entity creation with version the adding second version and verify object's state after $em->flush()
     */
    public function testAddVersion()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();

        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );
    }

    /**
     * Test entity creation with version the adding second version and verify object's state after $em->flush()
     */
    public function testPublishNewVersion()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $oldVersion = $article->getPublishedVersion();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();

        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );
    }

    /**
     * Test entity creation with version the adding second version, clear and verify object's state after reload
     */
    public function testPublishNewVersionClearAndRealod()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $oldVersion = $article->getPublishedVersion();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();
        $this->_em->clear();

        $this->_logger->enabled = true;
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
     * Test changing published version to old version, clear and verify object's state after reload
     */
    public function testPublishOldVersionClearAndReload()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();

        $oldVersion = $article->getPublishedVersion();
        $article->setPublishedVersion(1);
        $this->_em->flush();

        $this->_em->clear();
        $this->_logger->enabled = true;
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
     * Test loading old version and verify object's state
     */
    public function testLoadOldVersion()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();

        $this->_logger->enabled = true;
        $this->_versionableListener->loadVersion($this->_em, $article, 1);

        $this->assertEquals(
            0,
            count($this->_logger->queries),
            'Loading version executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
     * Test loading old version through overriding
     */
    public function testLoadOverridedVersion()
    {
        $article = new Article();
        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();

        $this->_em->clear();
        $this->_versionableListener->setVersionForId($this->_em, self::ARTICLE, $article->getId(), 1);
        $this->_logger->enabled = true;
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
            'Loading version executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );

        $this->_em->clear();
        $this->_versionableListener->setVersionForId($this->_em, self::ARTICLE, $article->getId(), null);
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );
    }

    public function testQueryWalker()
    {
        $category = new Category();
        $category->setTitle(self::CATEGORY_1);
        $this->_em->persist($category);

        $article = new Article();
        $article->setDate(new \DateTime());
        $article->addCategory($category);
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();

        $this->_em->clear();

        $query = $this->_em->createQuery("SELECT a FROM ".self::ARTICLE." AS a ORDER BY a.id");
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtension\Versionable\Query\VersionableTreeWalker'));
        $this->_logger->enabled = true;
        $articles = $query->execute();

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Loading with tree walker hint executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->_em->clear();
        $this->_logger->queries = array();

        $query = $this->_em->createQuery("SELECT c, a FROM ".self::CATEGORY." AS c JOIN c.articles AS a ORDER BY a.id");
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtension\Versionable\Query\VersionableTreeWalker'));
        $query->setHydrationMode(\FSi\DoctrineExtension\ORM\Query::HYDRATE_OBJECT);
        $this->_logger->enabled = true;
        $categories = $query->execute();
        $articles = $categories[0]->getArticles();
        $article = $articles[0];

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Loading with tree walker hint executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::TITLE_2,
            'title',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($article->getPublishedVersion())
        );
    }

    public function testQueryWalkerWithVersions()
    {
        $category = new Category();
        $category->setTitle(self::CATEGORY_1);
        $this->_em->persist($category);

        $article = new Article();
        $article->setDate(new \DateTime());
        $article->addCategory($category);
        $article->setTitle(self::TITLE_1);
        $article->setContents(self::CONTENTS_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $article_id = $article->getId();
        $version_id = $article->getPublishedVersion();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2);
        $article->setContents(self::CONTENTS_2);
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();

        $article = new Article();
        $article->setDate(new \DateTime());
        $article->addCategory($category);
        $article->setTitle(self::TITLE_1.' next');
        $article->setContents(self::CONTENTS_1.' next');
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setDate(new \DateTime());
        $article->setTitle(self::TITLE_2.' next');
        $article->setContents(self::CONTENTS_2.' next');
        $article->setVersion();
        $article->setPublishedVersion();
        $this->_em->flush();

        $this->_em->clear();

        $query = $this->_em->createQuery("SELECT c, a FROM ".self::CATEGORY." AS c JOIN c.articles AS a ORDER BY a.id");
        $this->_versionableListener->setVersionForId($this->_em, self::ARTICLE, $article_id, $version_id);
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtension\Versionable\Query\VersionableTreeWalker'));
        $query->setHydrationMode(\FSi\DoctrineExtension\ORM\Query::HYDRATE_OBJECT);
        $this->_logger->enabled = true;
        $categories = $query->execute();
        $articles = $categories[0]->getArticles();
        $article = $articles[0];
        $article2 = $articles[1];

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_1,
            'title',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_1,
            'contents',
            $article->getVersions()->get($article->getVersion())
        );

        $this->assertAttributeNotEquals(
            $version_id,
            'publishedVersion',
            $article
        );

        $this->assertAttributeEquals(
            self::TITLE_2.' next',
            'title',
            $article2
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2.' next',
            'contents',
            $article2
        );

        $this->assertAttributeEquals(
            self::TITLE_2.' next',
            'title',
            $article2->getVersions()->get($article2->getVersion())
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2.' next',
            'contents',
            $article2->getVersions()->get($article2->getVersion())
        );

        $this->assertAttributeEquals(
            $article2->getVersion(),
            'publishedVersion',
            $article2
        );

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Loading with tree walker hint executed wrong number of queries'
        );
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::ARTICLE,
            self::ARTICLE_VERSION
        );
    }

}
