<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use FSi\DoctrineExtensions\Uploadable\File;
use SplFileInfo;
use Throwable;

abstract class GeneralTest extends BaseORMTest
{
    public const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    public const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';
    public const TEST_FILE3 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lh_01.jpg';

    /**
     * Return instance of entity to use in test.
     */
    abstract protected function getUser();

    public function testInsertSplFileInfo(): void
    {
        $user = $this->getUser();
        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $originalFilename = $file->getFilename();

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $path = FILESYSTEM1 . $user->getFileKey();
        $this->assertNotEquals($path, FILESYSTEM1);
        $this->assertTrue(file_exists($path));
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertEquals(basename($user->getFile()->getKey()), $originalFilename);
    }

    public function testInsertFileWithNumericSuffix()
    {
        $user = $this->getUser();
        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE3);
        $originalFilename = $file->getFilename();

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $path = FILESYSTEM1 . $user->getFileKey();
        $this->assertNotEquals($path, FILESYSTEM1);
        $this->assertTrue(file_exists($path));
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertEquals(basename($user->getFile()->getKey()), $originalFilename);
    }

    public function testInsertFileWithDuplicatedNumericSuffix()
    {
        $user = $this->getUser();
        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE3);

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE3);
        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
        $this->assertEquals(basename($user->getFile()->getKey()), 'lh_2.jpg');
    }

    public function testInsertFile()
    {
        $key = 'some/key';

        $user = $this->getUser();
        $file = new File($key, $this->filesystem1);
        $file->setContent('');

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
        $this->assertNotSame($file, $user->getFile());
    }

    public function testUpdate()
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $file2 = new SplFileInfo(TESTS_PATH . self::TEST_FILE2);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->setFile($file2);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        // Old file must be deleted.
        $this->assertFalse(file_exists(FILESYSTEM1 . $key1));

        $key2 = $user->getFileKey();
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
        $this->assertTrue(file_exists(FILESYSTEM1 . $key2));
    }

    public function testDelete(): void
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->deleteFile();
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        // Old file must be deleted.
        $this->assertFalse(file_exists(FILESYSTEM1 . $key1));
        // Entity fields also need to be cleared
        $this->assertNull($user->getFile());
        $this->assertNull($user->getFileKey());
    }

    public function testDeleteWithFailure()
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->deleteFile();

        $exceptionThrown = false;
        try {
            // Setting name to null while it is not nullable should raise exception.
            $user->name = null;
            $this->entityManager->flush();
        } catch (Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
        // Entity fields should be empty, but the file should still exist.
        // The fields will be rehydrated post-load
        $this->assertNull($user->getFile());
        $this->assertNull($user->getFileKey());
    }

    public function testUpdateWithFailure()
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $file2 = new SplFileInfo(TESTS_PATH . self::TEST_FILE2);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->setFile($file2);

        $this->expectException(Throwable::class);
        // Setting name to null while it is not nullable should raise exception.
        $user->name = null;
        $this->entityManager->flush();

        // Old file must be preserved.
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
    }

    public function testDeleteEntity()
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->assertFalse(file_exists(FILESYSTEM1 . $key1));
    }

    public function testUpdateWithSameBaseName()
    {
        $content = 'some content';

        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
        $user->getFile()->setContent($content);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $key2 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key2));
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
        $this->assertNotEquals($content, $user->getFile()->getContent());
    }

    public function testUpdateWithTheSameBaseNameWithFailure()
    {
        $content = 'some content';

        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $oldFile = $user->getFile();
        $this->assertTrue($oldFile->exists());
        $oldFile->setContent($content);

        $exceptionThrown = false;
        try {
            // Setting name to null while it is not nullable should raise exception.
            $user->name = null;
            $user->setFile($file1);
            $this->entityManager->flush();
        } catch (Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Old file must be preserved.
        $this->assertTrue($oldFile->exists());
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
        $this->assertNotEquals($content, $user->getFile()->getContent());
    }

    public function testExceptionWhenKeyIsToLong()
    {
        $key = 'some/key' . str_repeat('aaaaa', 50);

        $user = $this->getUser();
        $file = new File($key, $this->filesystem1);
        $file->setContent('');

        $this->expectException(RuntimeException::class);
        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function testFileHandlerReturnBasename()
    {
        $key = 'some/key/blabla';

        $file = new File($key, $this->filesystem1);
        $file->setContent('');
        $this->assertEquals('blabla', $this->uploadableListener->getFileHandler()->getName($file));

        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $this->assertEquals('penguins.jpg', $this->uploadableListener->getFileHandler()->getName($file));
    }

    public function testLoadingFiles()
    {
        $user = $this->getUser();
        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $all = $this->entityManager->getRepository(get_class($user))->findAll();
        $user = array_shift($all);

        $path = FILESYSTEM1 . $user->getFileKey();
        $this->assertNotEquals($path, FILESYSTEM1);
        $this->assertTrue(file_exists($path));
        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotNull($user->getFileKey());
    }

    protected function tearDown(): void
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }
}
