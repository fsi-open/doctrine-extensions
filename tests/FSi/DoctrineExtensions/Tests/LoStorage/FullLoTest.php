<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage;

use FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

/**
 * These are tests for large object storage
 *
 * @author Lukasz Cybula <lukasz.cybula@fsi.pl>
 */
class FullLoTest extends BaseORMTest
{
    const ARTICLE = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\Article";
    const PHOTO = "FSi\\DoctrineExtensions\\Tests\\LoStorage\\Fixture\\Photo";

    const TEST_FILE_1 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/favourite.gif';
    const TEST_FILE_2 = '/FSi/DoctrineExtensions/Tests/LoStorage/Fixture/star.png';

    /**
     * Test simple entity creation and its state after $em->flush()
     */
    public function testInsert()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_1,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_1,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_1),
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_1),
            'bigphoto_filename',
            $article,
            'Filename extracted from cached filepath is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_1),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
            finfo_file($fi, TESTS_PATH . self::TEST_FILE_1, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test simple entity creation and its state after $em->flush() and $em->refresh()
     */
    public function testInsertAndRefresh()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_em->flush();

        $this->_logger->enabled = true;
        $this->_em->refresh($article);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_1,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_1,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_1),
        	'Filename extracted from cached filepath is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_1),
            'bigphoto_filename',
            $article,
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_1),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
            finfo_file($fi, TESTS_PATH . self::TEST_FILE_1, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test simple entity creation then clear the entity manager and load the entity once again
     */
    public function testInsertClearAndLoad()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_em->flush();

        unlink($article->getBigphotoFilepath());
        $this->_logger->enabled = true;
        $this->_em->clear();
        $article = $this->_em->find(self::ARTICLE, $article->getId());

        $this->assertEquals(
            2,
            count($this->_logger->queries),
        	'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_1,
        	'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_1,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_1),
            'Filename extracted from cached filepath is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_1),
            'bigphoto_filename',
            $article,
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_1),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
            finfo_file($fi, TESTS_PATH . self::TEST_FILE_1, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object and its state after $em->flush()
     */
    public function testUpdate()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $cachedTestFile1 = $article->getBigphotoFilepath();

        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedTestFile1,
            'The first cached file was not removed after update'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_2,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_2),
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_filename',
            $article,
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
            finfo_file($fi, TESTS_PATH . self::TEST_FILE_2, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object from null value and its state after $em->flush()
     */
    public function testUpdateFromNull()
    {
        $article = new Article();
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_2,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_2),
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_filename',
            $article,
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
            finfo_file($fi, TESTS_PATH . self::TEST_FILE_2, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test updating the large object and its state after $em->flush() and $em->refresh()
     */
    public function testUpdateAndRefresh()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $cachedTestFile1 = $article->getBigphotoFilepath();

        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($article);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedTestFile1,
            'The first cached file was not removed after update'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_2,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_2),
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_filename',
            $article,
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
            finfo_file($fi, TESTS_PATH . self::TEST_FILE_2, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
        	'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
    * Test updating the large object from null value and its state after $em->flush()
     */
    public function testUpdateFromNullAndRefresh()
    {
        $article = new Article();
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($article);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileEquals(
            $article->getBigphotoFilepath(),
            TESTS_PATH . self::TEST_FILE_2,
            'The contents of source file and cached file are different'
        );

        $this->assertAttributeNotEquals(
            TESTS_PATH . self::TEST_FILE_2,
            'bigphoto_filepath',
            $article,
            'Cached filepath is the same as source filepath'
        );

        $this->assertEquals(
            basename($article->getBigphotoFilepath()),
            basename(TESTS_PATH . self::TEST_FILE_2),
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            basename(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_filename',
            $article,
            'Cached filename is different than source filename'
        );

        $this->assertAttributeEquals(
            filesize(TESTS_PATH . self::TEST_FILE_2),
            'bigphoto_size',
            $article,
            'Cached file size is different than size of the source file'
        );

        $fi = finfo_open();
        $this->assertAttributeEquals(
             finfo_file($fi, TESTS_PATH . self::TEST_FILE_2, FILEINFO_MIME_TYPE),
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is different than MimeType of source file'
        );
        finfo_close($fi);

        $dt = \DateTime::createFromFormat('U', filemtime($article->getBigphotoFilepath()));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->assertAttributeLessThanOrEqual(
            $dt,
            'bigphoto_timestamp',
            $article,
            'Timestamp of cached file is older than timestamp of large object in database'
        );
    }

    /**
     * Test clearing the large object and its state after $em->flush()
     */
    public function testClear()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $cachedTestFile1 = $article->getBigphotoFilepath();

        $article->setBigphotoFilepath(null);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Flushing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedTestFile1,
            'The first cached file was not removed after update'
        );

        $this->assertAttributeEmpty(
            'bigphoto_filepath',
            $article,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_filename',
            $article,
            'Cached filename is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_size',
            $article,
            'Cached file size is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_timestamp',
            $article,
            'Cached timestamp is not null'
        );
    }

    /**
     * Test clearing the large object and its state after $em->flush() and $em->refresh()
     */
    public function testClearAndRefresh()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_1);
        $this->_em->persist($article);
        $this->_em->flush();
        $cachedTestFile1 = $article->getBigphotoFilepath();

        $article->setBigphotoFilepath(null);
        $this->_em->flush();
        $this->_logger->enabled = true;
        $this->_em->refresh($article);

        $this->assertEquals(
            1,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedTestFile1,
            'The first cached file was not removed after update'
        );

        $this->assertAttributeEmpty(
            'bigphoto_filepath',
            $article,
            'Cached filepath is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_filename',
            $article,
            'Cached filename is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_size',
            $article,
            'Cached file size is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_mimetype',
            $article,
            'Cached MimeType is not null'
        );

        $this->assertAttributeEmpty(
            'bigphoto_timestamp',
            $article,
            'Cached timestamp is not null'
        );
    }

    /**
     * Test deleting an entity with large object
     */
    public function testDelete()
    {
        $article = new Article();
        $article->setBigphotoFilepath(TESTS_PATH . self::TEST_FILE_2);
        $this->_em->persist($article);
        $this->_em->flush();

        $cachedFilePath = $article->getBigphotoFilepath();
        $this->_em->remove($article);
        $this->_logger->enabled = true;
        $this->_em->flush();

        $this->assertEquals(
            4,
            count($this->_logger->queries),
            'Refreshing executed wrong number of queries'
        );

        $this->assertFileNotExists(
            $cachedFilePath,
            'Cached file has not been removed properly'
        );
    }

    /**
     * {@iheritDoc}
     */
    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::PHOTO
        );
    }
}
