<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use FSi\DoctrineExtensions\Uploadable\UploadableListener;
use Gaufrette\Filesystem;
use Gaufrette\FilesystemMap;
use PHPUnit\Framework\TestCase;

final class UploadableTest extends TestCase
{
    public const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    public const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    public function testIsInitializableWithoutFilesystems1(): void
    {
        $listener = new UploadableListener([], $this->getFileHandlerMock());
        self::assertInstanceof(UploadableListener::class, $listener);
    }

    public function testIsNotInitializableWithoutFilesystems4(): void
    {
        $this->expectException(RuntimeException::class);
        $listener = new UploadableListener('definitely not an array', $this->getFileHandlerMock());
        self::assertInstanceof(UploadableListener::class, $listener);
    }

    public function testIsInitializableWithFilesystems(): void
    {
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $this->getFileHandlerMock());
        self::assertInstanceof(UploadableListener::class, $listener);
    }

    public function testAllowsGetFilesystems(): void
    {
        $listener = new UploadableListener(
            ['one' => $this->getFilesystemMock()],
            $this->getFileHandlerMock()
        );
        self::assertCount(1, $listener->getFilesystems());
    }

    public function testIsInitializableWithFilesystemMap(): void
    {
        $map = new FilesystemMap();
        $map->set('one', $this->getFilesystemMock());
        $listener = new UploadableListener($map, $this->getFileHandlerMock());
        self::assertSame($map->all(), $listener->getFilesystems());
    }

    public function testIsInitializableWithEmptyFilesystemMap(): void
    {
        $map = new FilesystemMap();
        $listener = new UploadableListener($map, $this->getFileHandlerMock());
        self::assertCount(0, $listener->getFilesystems());
    }

    public function testIsInstanceOfMappedSubscriber(): void
    {
        $listener = new UploadableListener(
            ['one' => $this->getFilesystemMock()],
            $this->getFileHandlerMock()
        );
        self::assertInstanceOf(MappedEventSubscriber::class, $listener);
    }

    public function testAllowsToCheckAndGetFilesystem(): void
    {
        $filesystems = ['one' => $this->getFilesystemMock(), 'two' => $this->getFilesystemMock(),];

        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        self::assertTrue($listener->hasFilesystem('one'));
        self::assertTrue($listener->hasFilesystem('two'));
        self::assertFalse($listener->hasFilesystem('three'));

        $listener->getFilesystem('one');
        $listener->getFilesystem('two');
        $this->expectException(RuntimeException::class);
        $listener->getFilesystem('three');
    }

    public function testSetZeroKeyLength(): void
    {
        $filesystems = ['one' => $this->getFilesystemMock()];
        $this->expectException(RuntimeException::class);
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeyLength(0);
    }

    public function testSetNegativeKeyLength(): void
    {
        $filesystems = ['one' => $this->getFilesystemMock()];
        $this->expectException(RuntimeException::class);
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeyLength(-1);
    }

    public function testGetDefaultKeymakerWhenNotSet(): void
    {
        $filesystems = ['one' => $this->getFilesystemMock()];
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        self::assertFalse($listener->hasDefaultKeymaker());
        $this->expectException(RuntimeException::class);
        $listener->getDefaultKeymaker();
    }

    public function testSetDefaultKeymaker(): void
    {
        $keymaker = $this->getKeymakerMock();
        $filesystems = ['one' => $this->getFilesystemMock()];
        $listener = new UploadableListener($filesystems, $this->getFileHandlerMock());
        $listener->setDefaultKeymaker($keymaker);
        self::assertSame($keymaker, $listener->getDefaultKeymaker());
        self::assertTrue($listener->hasDefaultKeymaker());
    }

    public function testSetFileHandlerInConstructor(): void
    {
        $fileHandler = $this->getFileHandlerMock();
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $fileHandler);
        self::assertSame($fileHandler, $listener->getFileHandler());
    }

    public function testSetFileHandler(): void
    {
        $fileHandler1 = $this->getFileHandlerMock();
        $fileHandler2 = $this->getFileHandlerMock();
        $listener = new UploadableListener(['one' => $this->getFilesystemMock()], $fileHandler1);
        $listener->setFileHandler($fileHandler2);
        self::assertSame($fileHandler2, $listener->getFileHandler());
    }

    public function testSettingDefaultFilesystem(): void
    {
        $f1 = $this->getFilesystemMock();
        $f2 = $this->getFilesystemMock();

        $listener = new UploadableListener(
            ['one' => $this->getFilesystemMock()],
            $this->getFileHandlerMock()
        );
        self::assertFalse($listener->hasDefaultFilesystem());

        $listener->setDefaultFilesystem($f1);
        self::assertSame($f1, $listener->getDefaultFilesystem());

        $listener->setDefaultFilesystem($f2);
        self::assertSame($f2, $listener->getDefaultFilesystem());
    }

    public function testGettingFilesystemWhenNotSet(): void
    {
        $listener = new UploadableListener(
            ['one' => $this->getFilesystemMock()],
            $this->getFileHandlerMock()
        );
        self::assertFalse($listener->hasDefaultFilesystem());

        $this->expectException(RuntimeException::class);
        $listener->getDefaultFilesystem();
    }

    public function testSettingFilesystems(): void
    {
        $filesystems = ['one' => $this->getFilesystemMock(), 'two' => $this->getFilesystemMock()];
        $listener = new UploadableListener(
            ['three' => $this->getFilesystemMock()],
            $this->getFileHandlerMock()
        );
        self::assertCount(1, $listener->getFilesystems());
        $listener->setFilesystems($filesystems);
        self::assertCount(2, $listener->getFilesystems());

        self::assertSame($filesystems, $listener->getFilesystems());

        self::assertTrue($listener->hasFilesystem('one'));
        self::assertTrue($listener->hasFilesystem('two'));
        self::assertFalse($listener->hasFilesystem('three'));
    }

    public function testSettingAndRemovingSingleFilesystem(): void
    {
        $f1 = $this->getFilesystemMock();
        $f2 = $this->getFilesystemMock();

        $listener = new UploadableListener([], $this->getFileHandlerMock());

        $listener->setFilesystem('one', $f1);
        self::assertTrue($listener->hasFilesystem('one'));
        self::assertSame($f1, $listener->getFilesystem('one'));

        $listener->setFilesystem('two', $f2);
        self::assertTrue($listener->hasFilesystem('two'));
        self::assertSame($f2, $listener->getFilesystem('two'));

        $listener->removeFilesystem('one');
        self::assertFalse($listener->hasFilesystem('one'));

        $listener->removeFilesystem('two');
        self::assertFalse($listener->hasFilesystem('two'));

        self::assertCount(0, $listener->getFilesystems());
    }

    protected function tearDown(): void
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    private function getKeymakerMock(): KeymakerInterface
    {
        return $this->createMock(KeymakerInterface::class);
    }

    private function getFilesystemMock(): Filesystem
    {
        return $this->createMock(Filesystem::class);
    }

    private function getFileHandlerMock(): FileHandlerInterface
    {
        return $this->createMock(FileHandlerInterface::class);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [User::class];
    }
}
