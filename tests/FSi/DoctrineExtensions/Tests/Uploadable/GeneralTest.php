<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\File;

class GeneralTest extends BaseORMTest
{
    const USER = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\User';
    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }

    public function testInsertSplFileInfo()
    {
        $user = new User();
        $file = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();

        $path = FILESYSTEM1 . $user->getFileKey();
        $this->assertNotEquals($path, FILESYSTEM1);
        $this->assertTrue(file_exists($path));
        $this->assertTrue($user->getFile() instanceof File);
    }

    public function testInsertFile()
    {
        $key = 'some/key';

        $user = new User();
        $file = new File($key, $this->_filesystem1);
        $file->setContent('');

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertTrue($user->getFile() instanceof File);
        $this->assertNotSame($file, $user->getFile());
    }

    public function testUpdate()
    {
        $user = new User();
        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $file2 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE2);

        $user->setFile($file1);
        $this->_em->persist($user);
        $this->_em->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->setFile($file2);
        $this->_em->flush();

        // Old file must be deleted.
        $this->assertFalse(file_exists(FILESYSTEM1 . $key1));

        $key2 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key2));
    }

    public function testDelete()
    {
        $user = new User();
        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->_em->persist($user);
        $this->_em->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->deleteFile();
        $this->_em->flush();

        // Old file must be deleted.
        $this->assertFalse(file_exists(FILESYSTEM1 . $key1));
    }

    public function testDeleteWithFailure()
    {
        $user = new User();
        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->_em->persist($user);
        $this->_em->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->deleteFile();

        $exceptionThrown = false;
        try {
            // Setting name to null while it is not nullable should raise exception.
            $user->name = null;
            $this->_em->flush();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
    }

    public function testUpdateWithFailure()
    {
        $user = new User();
        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $file2 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE2);

        $user->setFile($file1);
        $this->_em->persist($user);
        $this->_em->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->setFile($file2);

        $exceptionThrown = false;
        try {
            // Setting name to null while it is not nullable should raise exception.
            $user->name = null;
            $this->_em->flush();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Old file must be deleted.
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
    }

    /**
     * Inserting new entity causes internal flush call, so postFlush event is then
     * called at least twice, and since in that event files are deleted, we must
     * ensure that it won't delete files in case of failure.
     */
    public function testNestedFlush()
    {
        $user = new User();
        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->_em->persist($user);
        $this->_em->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $user->deleteFile();

        $exceptionThrown = false;
        try {
            // Setting name to null while it is not nullable should raise exception.
            $user->name = null;

            $user = new User();
            $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

            $user->setFile($file1);
            $this->_em->persist($user);

            $this->_em->flush();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
    }

    public function testDeleteEntity()
    {
        $user = new User();
        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file1);
        $this->_em->persist($user);
        $this->_em->flush();

        $key1 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));

        $this->_em->remove($user);
        $this->_em->flush();

        $this->assertFalse(file_exists(FILESYSTEM1 . $key1));
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    /**
     * {@inheritDoc}
     */
    protected function getUsedEntityFixtures()
    {
        return array(
            self::USER,
        );
    }
}
