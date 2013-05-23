<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage;

use FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Article;
use FSi\DoctrineExtensions\Tests\LoStorage\Fixture\News;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

/**
 * These are tests for large object storage
 *
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class CompoundLoTest extends BaseORMTest
{
    const ARTICLE = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\Article";
    const NEWS = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\News";
    const NEWS_WITH_FILE = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\NewsWithFile";
    const PHOTO = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\Photo";

    const TEST_FILE_1 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/favourite.gif';
    const TEST_FILE_2 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/star.png';
    const TEST_THUMB_1 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/lighthouse_s.jpg';
    const TEST_THUMB_2 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/penguins_s.jpg';
    const TEST_PHOTO_1 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/lighthouse_l.jpg';
    const TEST_PHOTO_2 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/penguins_l.jpg';

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }

    /**
     * Test clearing the Large Object cache in different ways
     */
    public function testClearCache()
    {
        $news1 = new News();
        $news1->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news1->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news1);

        $news2 = new News();
        $news2->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news2->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_em->persist($news2);

        $article1 = new Article();
        $article1->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article1);

        $article2 = new Article();
        $article2->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_em->persist($article2);

        $this->_em->flush();

        $this->_loListener->clearCache($this->_em, null, $news1);

        $this->assertFileNotExists(
            $news1->getThumbnailFilepath(),
            'Cached file exists after clearing cache for entity'
        );

        $this->assertFileNotExists(
            $news1->getPhotoFilepath(),
            'Cached file exists after clearing cache for entity'
        );

        $this->assertFileNotExists(
            dirname($news1->getPhotoFilepath()),
            'Cached directory for specific entity exists after clearing cache for this entity'
        );

        $this->assertFileExists(
            $news2->getThumbnailFilepath(),
            'Cached file not exists after clearing cache for other entity'
        );

        $this->assertFileExists(
            $news2->getPhotoFilepath(),
            'Cached file not exists after clearing cache for other entity'
        );

        $this->_loListener->clearCache($this->_em, self::NEWS);

        $this->assertFileNotExists(
            $news2->getThumbnailFilepath(),
            'Cached file exists after clearing cache for its entity class'
        );

        $this->assertFileNotExists(
            $news2->getPhotoFilepath(),
            'Cached file exists after clearing cache for its entity class'
        );

        $this->assertFileNotExists(
            dirname($news2->getPhotoFilepath()),
            'Cached directory for specific entity exists after clearing cache for its class'
        );

        $this->assertFileNotExists(
            dirname(dirname($news2->getPhotoFilepath())),
            'Cached directory for specific class exists after clearing cache for this class'
        );

        $this->_loListener->clearCache($this->_em);

        $this->assertFileNotExists(
            $article1->getBigphotoFilepath(),
            'Cached file exists after clearing the whole cache'
        );

        $this->assertFileNotExists(
            $article2->getBigphotoFilepath(),
            'Cached file exists after clearing the whole cache'
        );

        $this->assertFileNotExists(
            dirname(dirname($article1->getBigphotoFilepath())),
            'Cached directory for specific class exists after clearing the whole cache'
        );
    }

    /**
     * Test filling the Large Object cache in different ways
     */
    public function testFillCache()
    {
        $news1 = new News();
        $news1->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news1->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news1);

        $news2 = new News();
        $news2->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news2->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_em->persist($news2);

        $article1 = new Article();
        $article1->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article1);

        $article2 = new Article();
        $article2->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_loListener->clearCache($this->_em);

        $this->_loListener->fillCache($this->_em, null, $news1);

        $this->assertFileExists(
            $news1->getThumbnailFilepath(),
            'Cached file not exists after filling cache for entity'
        );

        $this->assertFileExists(
            $news1->getPhotoFilepath(),
            'Cached file not exists after filling cache for entity'
        );

        $this->assertFileNotExists(
            $news2->getThumbnailFilepath(),
            'Cached file exists after filling cache for other entity'
        );

        $this->assertFileNotExists(
            $news2->getPhotoFilepath(),
            'Cached file exists after filling cache for other entity'
        );

        $this->_loListener->fillCache($this->_em, self::NEWS);

        $this->assertFileExists(
            $news2->getThumbnailFilepath(),
            'Cached file not exists after filling cache for its entity class'
        );

        $this->assertFileExists(
            $news2->getPhotoFilepath(),
            'Cached file not exists after filling cache for its entity class'
        );

        $this->_loListener->fillCache($this->_em);

        $this->assertFileExists(
            $article1->getBigphotoFilepath(),
            'Cached file not exists after filling the whole cache'
        );

        $this->assertFileExists(
            $article2->getBigphotoFilepath(),
            'Cached file not exists after filling the whole cache'
        );
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::NEWS,
            self::NEWS_WITH_FILE,
            self::PHOTO
        );
    }
}