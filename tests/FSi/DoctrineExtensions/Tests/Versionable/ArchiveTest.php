<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Versionable;

use FSi\DoctrineExtensions\Versionable\Strategy\ArchiveStrategy;
use FSi\DoctrineExtensions\Tests\Versionable\Fixture\ArchivableArticle;
use FSi\DoctrineExtensions\Tests\Versionable\Fixture\ArchivableArticleVersion;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

/**
 * These are tests for large object storage
 *
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class ArchiveTest extends BaseORMTest
{
    const ARTICLE = "FSi\\DoctrineExtensions\\Tests\\Versionable\\Fixture\\ArchivableArticle";
    const ARTICLE_VERSION = "FSi\\DoctrineExtensions\\Tests\\Versionable\\Fixture\\ArchivableArticleVersion";

    const TITLE_1 = 'Title 1';
    const TITLE_2 = 'Title 2';
    const CONTENTS_1 = 'Contents of article 1';
    const CONTENTS_2 = 'Contents of article 2';

    /**
     * Test simple entity creation without any versionable property set and verify object's state after $em->flush()
     */
    public function testInsertWithoutVersions()
    {
        $article = new ArchivableArticle();
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

        $this->assertAttributeEmpty(
            'status',
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
        $article = new ArchivableArticle();
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
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
        $article = new ArchivableArticle();
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
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
        $article = new ArchivableArticle();
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_DRAFT,
            'status',
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
            ArchiveStrategy::STATUS_DRAFT,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
            $article->getVersions()->get($article->getPublishedVersion())
        );

    }

    /**
     * Test entity creation with version the adding second version and verify object's state after $em->flush()
     */
    public function testPublishNewVersion()
    {
        $article = new ArchivableArticle();
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
            6,
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
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
            ArchiveStrategy::STATUS_ARCHIVE,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
            $article->getVersions()->get($article->getPublishedVersion())
        );
    }

    /**
     * Test entity creation with version the adding second version, clear and verify object's state after reload
     */
    public function testPublishNewVersionClearAndRealod()
    {
        $article = new ArchivableArticle();
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
            'Reloading executed wrong number of queries'
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
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
            ArchiveStrategy::STATUS_ARCHIVE,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
            $article->getVersions()->get($article->getPublishedVersion())
        );

        $this->assertEquals(
            2,
            $article->getVersions()->count(),
            'Versions count is incorrect'
        );
    }

    /**
    * Test changing published version to old version and verify object's state
     */
    public function testPublishOldVersion()
    {
        $article = new ArchivableArticle();
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
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            self::CONTENTS_2,
            'contents',
            $article->getVersions()->get($oldVersion)
        );

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_ARCHIVE,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
        $article = new ArchivableArticle();
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
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
            ArchiveStrategy::STATUS_ARCHIVE,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
        $article = new ArchivableArticle();
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
            ArchiveStrategy::STATUS_ARCHIVE,
            'status',
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
            ArchiveStrategy::STATUS_ARCHIVE,
            'status',
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

        $this->assertAttributeEquals(
            ArchiveStrategy::STATUS_PUBLISHED,
            'status',
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
        $article = new ArchivableArticle();
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

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::ARTICLE_VERSION
        );
    }

}
