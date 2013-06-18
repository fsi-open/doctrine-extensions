<?php

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use FSi\DoctrineExtensions\Uploadable\File;

class UploadableTest extends BaseORMTest
{
    const USER = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\User';
    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    public function testInsertSplFileInfo()
    {
        $user = new User();
        $file = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertTrue(file_exists(FILESYSTEM1 . $user->getFileKey()));
    }

    public function testInsertFile()
    {
        $key = 'some/key';

        $user = new User();
        $file = new File($key, $this->_filesystem1);

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertEquals($key, $user->getFileKey());
    }

    public function testInsertUploadedFile()
    {
        $user = new User();
        $file = new UploadedFile(TESTS_PATH . self::TEST_FILE1, 'originalname.jpg');

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertTrue(file_exists(FILESYSTEM1 . $user->getFileKey()));
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

        $key2 = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key2));

        // File1 must be deleted explicitly.
        $this->assertTrue(file_exists(FILESYSTEM1 . $key1));
        $this->assertFalse($key1 === $key2);
    }

    /**
     * During update with the file in the same filesystem only file key should be changed.
     */
    public function testUpdateFileInTheSameFilesystem()
    {
        $user1 = new User();
        $user2 = new User();

        $file1 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);
        $file2 = new \SplFileInfo(TESTS_PATH . self::TEST_FILE2);

        $user1->setFile($file1);
        $user2->setFile($file2);

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();

        $this->_em->clear();
        $repo = $this->_em->getRepository(self::USER);
        $user1 = $repo->findOneById($user1->getId());
        $user2 = $repo->findOneById($user2->getId());

        $key2 = $user2->getFileKey();
        $user1->setFile($user2->getFile());
        $this->_em->flush();
        $this->assertEquals($key2, $user1->getFileKey());
    }

    /**
     * File from other filesystem should be copied to proper one.
     */
    public function testUpdateFileInDifferentFilesystem()
    {
        $user = new User();
        $file = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file);

        $this->_em->persist($user);
        $this->_em->flush();

        $id = $user->getId();
        $this->_em->clear();
        $user = $this->_em->getRepository(self::USER)->findOneById($id);

        $user->setFile2($user->getFile());
        $this->_em->flush();
    }

    public function testDelete()
    {
        $user = new User();
        $file = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();
        $id = $user->getId();
        $this->_em->clear();

        $user = $this->_em->getRepository(self::USER)->findOneById($id);
        $key = $user->getFileKey();
        $this->assertTrue(file_exists(FILESYSTEM1 . $key));

        $this->assertTrue($user->getFile()->delete());
        $user->deleteFile();
        $this->_em->flush();

        $this->assertEquals(null, $user->getFileKey());
        $this->assertFalse(file_exists(FILESYSTEM1 . $key));
    }

    public function testDeleteFromFilesystem()
    {
        $user = new User();
        $file = new \SplFileInfo(TESTS_PATH . self::TEST_FILE1);

        $user->setFile($file);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertTrue(file_exists(FILESYSTEM1 . $user->getFileKey()));

        $user->deleteFile();
        $this->_em->flush();

        $this->assertEquals(null, $user->getFileKey());
    }

    protected function tearDown()
    {
        $this->deleteRecursive(FILESYSTEM1);
        $this->deleteRecursive(FILESYSTEM2);
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

    /**
     * Clears given directory.
     *
     * @param string $path
     */
    private function deleteRecursive($path)
    {
        foreach (new DirectoryIterator($path) as $file) {
            if ($file->isDot()) {
                continue;
            }

            $filename = $path . DIRECTORY_SEPARATOR . $file->getFilename();

            if ($file->isDir()) {
                $this->deleteRecursive($filename);
            } else {
                unlink($filename);
            }
        }
    }
}
