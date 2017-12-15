<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\UploadableListener;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use Gaufrette\FilesystemMap;

class UploadableTest extends \PHPUnit_Framework_TestCase
{
    const USER = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\User';
    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    public function testIsInitializableWithoutFilesystems1()
    {
        new UploadableListener([], $this->getFileHandlerMock());
    }

    public function testIsNotInitializableWithoutFilesystems4()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener('definitely not an array', $this->getFileHandlerMock());
    }

    public function testIsInitializableWithFilesystems()
    {
        new UploadableListener(['one' => $this->getFilesystemMock()], $this->getFileHandlerMock());
    }

    public function testAllowsGetFilesystems()
    {
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $this->getFileHandlerMock());
        $listener->getFilesystems();
    }

    public function testIsInitializableWithFilesystemMap()
    {
        $map = new FilesystemMap();
        $map->set('one', $this->getFilesystemMock(), $this->getFileHandlerMock());
        $listener = new UploadableListener($map, $this->getFileHandlerMock());
        $this->assertSame($map->all(), $listener->getFilesystems());
    }

    public function testIsInitializableWithEmptyFilesystemMap()
    {
        $map = new FilesystemMap();
        new UploadableListener($map, $this->getFileHandlerMock());
    }

    public function testIsInstanceOfMappedSubscriber()
    {
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $this->getFileHandlerMock());
        $this->assertTrue($listener instanceof MappedEventSubscriber);
    }

    public function testAllowsToCheckAndGetFilesystem()
    {
        $filesystems = ['one' => $this->getFilesystemMock(), 'two' => $this->getFilesystemMock(),];

        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $this->assertTrue($listener->hasFilesystem('one'));
        $this->assertTrue($listener->hasFilesystem('two'));
        $this->assertFalse($listener->hasFilesystem('three'));

        $listener->getFilesystem('one');
        $listener->getFilesystem('two');
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener->getFilesystem('three');
    }

    public function testSetZeroKeyLength()
    {
        $filesystems = ['one' => $this->getFilesystemMock()];
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeyLength(0);
    }

    public function testSetNegativeKeyLength()
    {
        $filesystems = ['one' => $this->getFilesystemMock()];
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeyLength(-1);
    }

    public function testGetDefaultKeymakerWhenNotSet()
    {
        $filesystems = ['one' => $this->getFilesystemMock()];
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $this->assertFalse($listener->hasDefaultKeymaker());
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener->getDefaultKeymaker();
    }

    public function testSetDefaultKeymaker()
    {
        $keymaker = $this->getKeymakerMock();
        $filesystems = ['one' => $this->getFilesystemMock()];
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeymaker($keymaker);
        $this->assertSame($keymaker, $listener->getDefaultKeymaker());
        $this->assertTrue($listener->hasDefaultKeymaker());
    }

    public function testSetFileHandlerInConstructor()
    {
        $fileHandler = $this->getFileHandlerMock();
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $fileHandler);
        $this->assertSame($fileHandler, $listener->getFileHandler());
    }

    public function testSetFileHandler()
    {
        $fileHandler1 = $this->getFileHandlerMock();
        $fileHandler2 = $this->getFileHandlerMock();
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $fileHandler1);
        $listener->setFileHandler($fileHandler2);
        $this->assertSame($fileHandler2, $listener->getFileHandler());
    }

    public function testSettingDefaultFilesystem()
    {
        $f1 = $this->getFilesystemMock();
        $f2 = $this->getFilesystemMock();

        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $this->getFileHandlerMock());
        $this->assertFalse($listener->hasDefaultFilesystem());

        $listener->setDefaultFilesystem($f1);
        $this->assertSame($f1, $listener->getDefaultFilesystem());

        $listener->setDefaultFilesystem($f2);
        $this->assertSame($f2, $listener->getDefaultFilesystem());
    }

    public function testGettingFilesystemWhenNotSet()
    {
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $this->getFileHandlerMock());
        $this->assertFalse($listener->hasDefaultFilesystem());

        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener->getDefaultFilesystem();
    }

    public function testSettingFilesystems()
    {
        $filesystems = ['one' => $this->getFilesystemMock(), 'two' => $this->getFilesystemMock()];

        $listener = new UploadableListener(['three' => $this->getFilesystemMock()], $this->getFileHandlerMock());
        $this->assertEquals(1, count($listener->getFilesystems()));
        $listener->setFilesystems($filesystems);
        $this->assertEquals(2, count($listener->getFilesystems()));

        $this->assertSame($filesystems, $listener->getFilesystems());

        $this->assertTrue($listener->hasFilesystem('one'));
        $this->assertTrue($listener->hasFilesystem('two'));
        $this->assertFalse($listener->hasFilesystem('three'));
    }

    public function testSettingAndRemovingSingleFilesystem()
    {
        $f1 = $this->getFilesystemMock();
        $f2 = $this->getFilesystemMock();

        $listener = new UploadableListener([], $this->getFileHandlerMock());

        $listener->setFilesystem('one', $f1);
        $this->assertTrue($listener->hasFilesystem('one'));
        $this->assertSame($f1, $listener->getFilesystem('one'));

        $listener->setFilesystem('two', $f2);
        $this->assertTrue($listener->hasFilesystem('two'));
        $this->assertSame($f2, $listener->getFilesystem('two'));

        $listener->removeFilesystem('one');
        $this->assertFalse($listener->hasFilesystem('one'));

        $listener->removeFilesystem('two');
        $this->assertFalse($listener->hasFilesystem('two'));

        $this->assertEquals(0, count($listener->getFilesystems()));
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    /**
     * @return \FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface
     */
    private function getKeymakerMock()
    {
        return $this->createMock('FSi\\DoctrineExtensions\\Uploadable\\Keymaker\\KeymakerInterface');
    }

    /**
     * @return \Gaufrette\Filesystem
     */
    private function getFilesystemMock()
    {
        return $this->getMockBuilder('Gaufrette\\Filesystem')->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \FSi\DoctrineExtensios\Uploadable\FileHandler\FileHandlerInterface
     */
    private function getFileHandlerMock()
    {
        return $this->createMock('FSi\\DoctrineExtensions\\Uploadable\\FileHandler\\FileHandlerInterface');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUsedEntityFixtures()
    {
        return [
            self::USER,
        ];
    }
}
