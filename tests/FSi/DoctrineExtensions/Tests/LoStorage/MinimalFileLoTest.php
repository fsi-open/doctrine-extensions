<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage;

use SplFileInfo;
use FSi\DoctrineExtensions\Tests\LoStorage\Fixture\NewsWithFile;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

/**
 * These are tests for large object storage
 *
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class MinimalFileLoStorageTest extends BaseORMTest
{
    const NEWS_WITH_FILE = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\NewsWithFile";
    const PHOTO = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\Photo";

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
     * Test simple entity creation and its state after $em->flush()
     */
    public function testInsert()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_1,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_1,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_1,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_1,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', $news->getThumbnailFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', $news->getPhotoFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test simple entity creation and its state after $em->flush() and $em->refresh()
     */
    public function testInsertAndRefresh()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_em->flush();

        $this->_logger->enabled = true;
        $this->_em->refresh($news);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_1,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_1,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_1,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_1,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
	    );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFile()->getPathname()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFile()->getPathname()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test simple entity creation then clear the entity manager and load the entity once again
     */
    public function testInsertClearAndLoad()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_em->flush();

        unlink($news->getThumbnailFile()->getPathname());
        unlink($news->getPhotoFile()->getPathname());
        $this->_logger->enabled = true;
        $this->_em->clear();
        $news = $this->_em->find(self::NEWS_WITH_FILE, $news->getId());

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_1,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_1,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_1,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_1,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', $news->getThumbnailFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', $news->getPhotoFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object and its state after $em->flush()
     */
    public function testUpdate()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFile()->getPathname();
        $cachedTestPhoto1 = $news->getPhotoFile()->getPathname();

        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_2));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_2));
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertEquals(
            $cachedTestThumb1,
            $news->getThumbnailFile()->getPathname(),
            'The cached file after change has different filepath than before change'
        );

        $this->assertEquals(
            $cachedTestPhoto1,
            $news->getPhotoFile()->getPathname(),
            'The cached file after change has different filepath than before change'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', $news->getThumbnailFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', $news->getPhotoFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object from null value and its state after $em->flush()
     */
    public function testUpdateFromNull()
    {
        $news = new NewsWithFile();
        $this->_em->persist($news);
        $this->_em->flush();

        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_2));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_2));
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', $news->getThumbnailFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', $news->getPhotoFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object and its state after $em->flush() and $em->refresh()
     */
    public function testUpdateAndRefresh()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFile()->getPathname();
        $cachedTestPhoto1 = $news->getPhotoFile()->getPathname();

        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_2));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_2));
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($news);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertEquals(
            $cachedTestThumb1,
            $news->getThumbnailFile()->getPathname(),
            'The cached file after change has different filepath than before change'
        );

        $this->assertEquals(
            $cachedTestPhoto1,
            $news->getPhotoFile()->getPathname(),
            'The cached file after change has different filepath than before change'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', $news->getThumbnailFile()->getMTime());
            $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', $news->getPhotoFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object from null value and its state after $em->flush() and $em->refresh()
     */
    public function testUpdateFromNullAndRefresh()
    {
        $news = new NewsWithFile();
        $this->_em->persist($news);
        $this->_em->flush();

        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_2));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_2));
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($news);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFile()->getPathname(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFile()->getPathname(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            $news->getThumbnailFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            $news->getPhotoFile()->getPathname(),
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            $news->getPhotoFile()->getBasename(),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            $news->getThumbnailFile()->getBasename(),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            $news->getPhotoFile()->getBasename(),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', $news->getThumbnailFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', $news->getPhotoFile()->getMTime());
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'photo_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test clearing the large object and its state after $em->flush()
     */
    public function testClear()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFile()->getPathname();
        $cachedTestPhoto1 = $news->getPhotoFile()->getPathname();

        $news->setThumbnailFile(null);
        $news->setPhotoFile(null);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedTestThumb1,
            'The cached file was not removed after update to null'
        );

        $this->assertFileNotExists(
            $cachedTestPhoto1,
            'The cached file was not removed after update to null'
        );

        $this->assertAttributeEmpty(
            'thumbnail_file',
            $news,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'photo_file',
            $news,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'thumbnail_filename',
            $news,
            'Cached filename is not null'
        );

        $this->assertAttributeEmpty(
            'photo_filename',
            $news,
            'Cached filename is not null'
        );

        $this->assertAttributeEmpty(
            'thumbnail_timestamp',
            $news,
            'Cached timestamp is not null'
        );

        $this->assertAttributeEmpty(
            'photo_timestamp',
            $news,
            'Cached timestamp is not null'
        );
    }

    /**
     * Test clearing the large object and its state after $em->flush() and $em->refresh()
     */
    public function testClearAndRefresh()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_1));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_1));
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFile()->getPathname();
        $cachedTestPhoto1 = $news->getPhotoFile()->getPathname();

        $news->setThumbnailFile(null);
        $news->setPhotoFile(null);
        $this->_em->flush();
        $this->_em->clear();
        $this->_logger->enabled = true;
        $news = $this->_em->find(self::NEWS_WITH_FILE, $news->getId());

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedTestThumb1,
            'The first cached file was not removed after update'
        );

        $this->assertFileNotExists(
            $cachedTestPhoto1,
            'The first cached file was not removed after update'
        );

        $this->assertAttributeEmpty(
            'thumbnail_file',
            $news,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'photo_file',
            $news,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'thumbnail_filename',
            $news,
            'Cached filename is not null'
        );

        $this->assertAttributeEmpty(
            'photo_filename',
            $news,
            'Cached filename is not null'
        );

        $this->assertAttributeEmpty(
            'thumbnail_timestamp',
            $news,
            'Cached timestamp is not null'
        );

        $this->assertAttributeEmpty(
            'photo_timestamp',
            $news,
            'Cached timestamp is not null'
        );
    }

    /**
     * Test deleting an entity with large object
     */
    public function testDelete()
    {
        $news = new NewsWithFile();
        $news->setThumbnailFile(new SplFileInfo(TESTS_PATH . self::TEST_THUMB_2));
        $news->setPhotoFile(new SplFileInfo(TESTS_PATH . self::TEST_PHOTO_2));
        $this->_em->persist($news);
        $this->_em->flush();

        $cachedThumbPath = $news->getThumbnailFile()->getPathname();
        $cachedPhotoPath = $news->getPhotoFile()->getPathname();
        $this->_em->remove($news);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedThumbPath,
            'Cached file has not been removed properly'
        );

        $this->assertFileNotExists(
            $cachedPhotoPath,
            'Cached file has not been removed properly'
        );
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::NEWS_WITH_FILE,
            self::PHOTO
        );
    }

}
