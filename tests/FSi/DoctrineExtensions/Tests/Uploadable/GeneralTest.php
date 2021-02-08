<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
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
    abstract protected function getUser(): User;

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
        self::assertNotEquals($path, FILESYSTEM1);
        self::assertFileExists($path);
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertEquals(basename($user->getFile()->getKey()), $originalFilename);
    }

    public function testInsertFileWithNumericSuffix(): void
    {
        $user = $this->getUser();
        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE3);
        $originalFilename = $file->getFilename();

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $path = FILESYSTEM1 . $user->getFileKey();
        self::assertNotEquals($path, FILESYSTEM1);
        self::assertFileExists($path);
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertEquals(basename($user->getFile()->getKey()), $originalFilename);
    }

    public function testInsertFileWithDuplicatedNumericSuffix(): void
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

        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
        self::assertEquals(basename($user->getFile()->getKey()), 'lh_2.jpg');
    }

    public function testInsertFile(): void
    {
        $key = 'some/key';

        $user = $this->getUser();
        $file = new File($key, $this->filesystem1);
        $file->setContent('');

        $user->setFile($file);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
        self::assertNotSame($file, $user->getFile());
    }

    public function testUpdate(): void
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $file2 = new SplFileInfo(TESTS_PATH . self::TEST_FILE2);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        self::assertFileExists(FILESYSTEM1 . $key1);

        $user->setFile($file2);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        // Old file must be deleted.
        self::assertFileNotExists(FILESYSTEM1 . $key1);

        $key2 = $user->getFileKey();
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
        self::assertFileExists(FILESYSTEM1 . $key2);
    }

    public function testDelete(): void
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        self::assertFileExists(FILESYSTEM1 . $key1);

        $user->deleteFile();
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        // Old file must be deleted.
        self::assertFileNotExists(FILESYSTEM1 . $key1);
        // Entity fields also need to be cleared
        self::assertNull($user->getFile());
        self::assertNull($user->getFileKey());
    }

    public function testDeleteWithFailure(): void
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        self::assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->deleteFile();

        $exceptionThrown = false;
        try {
            // Setting name to null while it is not nullable should raise exception.
            $user->name = null;
            $this->entityManager->flush();
        } catch (Throwable $e) {
            $exceptionThrown = true;
        }

        self::assertTrue($exceptionThrown);
        self::assertFileExists(FILESYSTEM1 . $key1);
        // Entity fields should be empty, but the file should still exist.
        // The fields will be rehydrated post-load
        self::assertNull($user->getFile());
        self::assertNull($user->getFileKey());
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
        self::assertFileExists(FILESYSTEM1 . $key1);

        $user->setFile($file2);

        $this->expectException(Throwable::class);
        // Setting name to null while it is not nullable should raise exception.
        $user->name = null;
        $this->entityManager->flush();

        // Old file must be preserved.
        self::assertFileExists(FILESYSTEM1 . $key1);
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
    }

    public function testDeleteEntity(): void
    {
        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        self::assertFileExists(FILESYSTEM1 . $key1);

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        self::assertFileNotExists(FILESYSTEM1 . $key1);
    }

    public function testUpdateWithSameBaseName(): void
    {
        $content = 'some content';

        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $key1 = $user->getFileKey();
        self::assertFileExists(FILESYSTEM1 . $key1);
        $user->getFile()->setContent($content);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->refresh($user);

        $key2 = $user->getFileKey();
        self::assertFileExists(FILESYSTEM1 . $key2);
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
        self::assertNotEquals($content, $user->getFile()->getContent());
    }

    public function testUpdateWithTheSameBaseNameWithFailure(): void
    {
        $content = 'some content';

        $user = $this->getUser();
        $file1 = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $oldFile = $user->getFile();
        self::assertTrue($oldFile->exists());
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

        self::assertTrue($exceptionThrown);

        // Old file must be preserved.
        self::assertTrue($oldFile->exists());
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
        self::assertNotEquals($content, $user->getFile()->getContent());
    }

    public function testExceptionWhenKeyIsToLong(): void
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

    public function testFileHandlerReturnBasename(): void
    {
        $key = 'some/key/blabla';

        $file = new File($key, $this->filesystem1);
        $file->setContent('');
        self::assertEquals('blabla', $this->uploadableListener->getFileHandler()->getName($file));

        $file = new SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        self::assertEquals('penguins.jpg', $this->uploadableListener->getFileHandler()->getName($file));
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
        self::assertNotEquals($path, FILESYSTEM1);
        self::assertFileExists($path);
        self::assertInstanceOf(File::class, $user->getFile());
        self::assertNotNull($user->getFileKey());
    }

    protected function tearDown(): void
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }
}
