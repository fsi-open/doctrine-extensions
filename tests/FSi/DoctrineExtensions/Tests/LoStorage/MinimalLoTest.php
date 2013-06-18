<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage;

use FSi\DoctrineExtensions\Tests\LoStorage\Fixture\News;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

/**
 * These are tests for large object storage
 *
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class MinimalLoStorageTest extends BaseORMTest
{
    const NEWS = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\News";
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_1,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_1,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_1,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_1,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
	    );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
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
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_1,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_1,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_1,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_1,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
	    );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news);
        $this->_em->flush();

        unlink($news->getThumbnailFilepath());
        unlink($news->getPhotoFilepath());
        $this->_logger->enabled = true;
        $this->_em->clear();
        $news = $this->_em->find(self::NEWS, $news->getId());

        $this->assertEquals(
            3,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_1,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_1,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_1,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_1,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFilepath();
        $cachedTestPhoto1 = $news->getPhotoFilepath();

        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            $cachedTestThumb1,
            'thumbnail_filepath',
            $news,
            'The cached file after change has different filepath than before change'
        );

        $this->assertAttributeEquals(
            $cachedTestPhoto1,
            'photo_filepath',
            $news,
            'The cached file after change has different filepath than before change'
        );

        $this->assertFileEquals(
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $this->_em->persist($news);
        $this->_em->flush();

        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFilepath();
        $cachedTestPhoto1 = $news->getPhotoFilepath();

        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($news);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertAttributeEquals(
            $cachedTestThumb1,
            'thumbnail_filepath',
            $news,
            'The cached file after change has different filepath than before change'
        );

        $this->assertAttributeEquals(
            $cachedTestPhoto1,
            'photo_filepath',
            $news,
            'The cached file after change has different filepath than before change'
        );

        $this->assertFileEquals(
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
            $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $this->_em->persist($news);
        $this->_em->flush();

        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($news);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $news->getThumbnailFilepath(),
            TESTS_PATH . self::TEST_THUMB_2,
            'The contents of source file and cached file are different'
        );

        $this->assertFileEquals(
            $news->getPhotoFilepath(),
            TESTS_PATH . self::TEST_PHOTO_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_THUMB_2,
            'thumbnail_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_PHOTO_2,
            'photo_filepath',
            $news,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($news->getThumbnailFilepath()),
            'thumb.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertEquals(
            basename($news->getPhotoFilepath()),
            'photo.jpg',
            'Cached filename is different than configured filename'
        );

        $this->assertAttributeEquals(
            basename($news->getThumbnailFilepath()),
            'thumbnail_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $this->assertAttributeEquals(
            basename($news->getPhotoFilepath()),
            'photo_filename',
            $news,
            'Filename extracted from cached filepath is different filename of cached file'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getThumbnailFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'thumbnail_timestamp',
            $news,
            'Timestamp of cached file is older than timestamp of large object in database'
        );

        $dt = \DateTime::createFromFormat('U', filemtime($news->getPhotoFilepath()));
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFilepath();
        $cachedTestPhoto1 = $news->getPhotoFilepath();

        $news->setThumbnailFilepath(null);
        $news->setPhotoFilepath(null);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            5,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
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
            'thumbnail_filepath',
            $news,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'photo_filepath',
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_1);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_1);
        $this->_em->persist($news);
        $this->_em->flush();
        $cachedTestThumb1 = $news->getThumbnailFilepath();
        $cachedTestPhoto1 = $news->getPhotoFilepath();

        $news->setThumbnailFilepath(null);
        $news->setPhotoFilepath(null);
        $this->_em->flush();
        $this->_em->clear();
        $this->_logger->enabled = true;
        $news = $this->_em->find(self::NEWS, $news->getId());

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
            'thumbnail_filepath',
            $news,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'photo_filepath',
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
        $news = new News();
        $news->setThumbnailFilepath(TESTS_PATH . self::TEST_THUMB_2);
        $news->setPhotoFilepath(TESTS_PATH . self::TEST_PHOTO_2);
        $this->_em->persist($news);
        $this->_em->flush();

        $cachedThumbPath = $news->getThumbnailFilepath();
        $cachedPhotoPath = $news->getPhotoFilepath();
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

    /**
     * {@iheritDoc}
     */
    protected function getUsedEntityFixtures()
    {
        return array(
            self::NEWS,
            self::PHOTO
        );
    }

}
