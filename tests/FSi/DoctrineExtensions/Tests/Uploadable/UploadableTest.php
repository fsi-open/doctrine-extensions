<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use FSi\DoctrineExtensions\Uploadable\UploadableListener;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use Gaufrette\FilesystemMap;

class UploadableTest extends \PHPUnit_Framework_TestCase
{
    const USER = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\User';
    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    public function testIsNotInitializableWithoutFilesystems1()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener(array(), $this->getFileHandlerMock());
    }

    public function testIsNotInitializableWithoutFilesystems2()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener(array('one' => 'not a filesystem'), $this->getFileHandlerMock());
    }

    public function testIsNotInitializableWithoutFilesystems3()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener(array('one' => new User()), $this->getFileHandlerMock());
    }

    public function testIsNotInitializableWithoutFilesystems4()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener('definitely not an array', $this->getFileHandlerMock());
    }

    public function testIsInitializableWithFilesystems()
    {
        new UploadableListener(array('one' => $this->getFilesystemMock()), $this->getFileHandlerMock());
    }

    public function testAllowsGetFilesystems()
    {
        $listener = new UploadableListener(array('one' => $this->getFilesystemMock()), $this->getFileHandlerMock());
        $listener->getFilesystems();
    }

    public function testIsInitializableWithFilesystemMap()
    {
        $map = new FilesystemMap();
        $map->set('one', $this->getFilesystemMock(), $this->getFileHandlerMock());
        $listener = new UploadableListener($map, $this->getFileHandlerMock());
        $this->assertSame($map->all(), $listener->getFilesystems());
    }

    public function testIsNotInitializableWithEmptyFilesystemMap()
    {
        $map = new FilesystemMap();
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener($map, $this->getFileHandlerMock());
    }

    public function testIsInstanceOfMappedSubscriber()
    {
        $listener = new UploadableListener(array('one' => $this->getFilesystemMock()), $this->getFileHandlerMock());
        $this->assertTrue($listener instanceof MappedEventSubscriber);
    }

    public function testAllowsToSetDefaultFilesystem()
    {
        $filesystems = array(
            'one' => $this->getFilesystemMock(),
            'two' => $this->getFilesystemMock(),
        );

        $default = 'two';
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock(), array('default' => $default));
        $this->assertEquals($default, $listener->getDefaultDomain());

        // If no default domain specified, listener should set first one as default.
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $this->assertEquals(key($filesystems), $listener->getDefaultDomain());

        // When setting wrong filesystem.
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener($filesystems, $this->getFileHandlerMock(), array('default' => 'three'));
    }

    public function testAllowsToSetDefaultFilesystemWithFilesystemMap()
    {
        $map = new FilesystemMap();
        $map->set('one', $this->getFilesystemMock());
        $map->set('two', $this->getFilesystemMock());

        $default = 'one';
        $listener = new UploadableListener($map, $this->getFileHandlerMock(), array('default' => $default));
        $this->assertEquals($default, $listener->getDefaultDomain());

        // If no default domain specified, listener should set first one as default.
        $listener = new UploadableListener($map, $this->getFileHandlerMock());
        $this->assertEquals('one', $listener->getDefaultDomain());

        // When setting wrong filesystem.
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener($map, $this->getFileHandlerMock(), array('default' => 'three'));
    }

    public function testAllowsToCheckAndGetFilesystem()
    {
        $filesystems = array(
            'one' => $this->getFilesystemMock(),
            'two' => $this->getFilesystemMock(),
        );

        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $this->assertTrue($listener->has('one'));
        $this->assertTrue($listener->has('two'));
        $this->assertFalse($listener->has('three'));

        $listener->getFilesystem('one');
        $listener->getFilesystem('two');
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener->getFilesystem('three');
    }

    public function testAllowsToSetDefaultKeylength()
    {
        $filesystems = array('one' => $this->getFilesystemMock());
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());

        // Check if default keyLength is greater than 0.
        $this->assertGreaterThan(0, $listener->getDefaultKeyLength());

        $length = 100;
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock(), array('keyLength' => $length));
        $this->assertEquals($length, $listener->getDefaultKeyLength());

        $length2 = 50;
        $listener->setDefaultKeyLength($length2);
        $this->assertEquals($length2, $listener->getDefaultKeyLength());
    }

    public function testCreationWithZeroKeyLength()
    {
        $filesystems = array('one' => $this->getFilesystemMock());
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener($filesystems, $this->getFileHandlerMock(), array('keyLength' => 0));
    }

    public function testSetZeroKeyLength()
    {
        $filesystems = array('one' => $this->getFilesystemMock());
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeyLength(0);
    }

    public function testCreationWithNegativeKeyLength()
    {
        $filesystems = array('one' => $this->getFilesystemMock());
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new UploadableListener($filesystems, $this->getFileHandlerMock(), array('keyLength' => -1));
    }

    public function testSetNegativeKeyLength()
    {
        $filesystems = array('one' => $this->getFilesystemMock());
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeyLength(-1);
    }

    public function testDefaultKeymaker()
    {
        $filesystems = array('one' => $this->getFilesystemMock());
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $this->assertTrue($listener->getDefaultKeymaker() instanceof KeymakerInterface);
    }

    public function testSetDefaultKeymaker()
    {
        $keymaker = $this->getKeymakerMock();
        $filesystems = array('one' => $this->getFilesystemMock());
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeymaker($keymaker);
        $this->assertSame($keymaker, $listener->getDefaultKeymaker());
    }

    public function testSetDefaultKeymakerThroughConstructor()
    {
        $keymaker = $this->getKeymakerMock();
        $filesystems = array('one' => $this->getFilesystemMock());
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock(), array('keymaker' => $keymaker));
        $this->assertSame($keymaker, $listener->getDefaultKeymaker());
    }

    public function testSetWrongKeymakerThroughConstructor()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $filesystems = array('one' => $this->getFilesystemMock());
        new UploadableListener($filesystems, $this->getFileHandlerMock(), array('keymaker' => 'wrong'));
    }

    public function testSetWrongKeymaker()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $filesystems = array('one' => $this->getFilesystemMock());
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeymaker('wrong');
    }

    public function testSetFileHandlerInConstructor()
    {
        $fileHandler = $this->getFileHandlerMock();
        $listener = new UploadableListener(array('one' => $this->getFilesystemMock()), $fileHandler);
        $this->assertSame($fileHandler, $listener->getFileHandler());
    }

    public function testSetFileHandler()
    {
        $fileHandler1 = $this->getFileHandlerMock();
        $fileHandler2 = $this->getFileHandlerMock();
        $listener = new UploadableListener(array('one' => $this->getFilesystemMock()), $fileHandler1);
        $listener->setFileHandler($fileHandler2);
        $this->assertSame($fileHandler2, $listener->getFileHandler());
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    private function getKeymakerMock()
    {
        return $this->getMock('FSi\\DoctrineExtensions\\Uploadable\\Keymaker\\KeymakerInterface');
    }

    private function getFilesystemMock()
    {
        return $this->getMockBuilder('Gaufrette\\Filesystem')->disableOriginalConstructor()->getMock();
    }

    private function getFileHandlerMock()
    {
        return $this->getMock('FSi\\DoctrineExtensions\\Uploadable\\FileHandler\\FileHandlerInterface');
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
