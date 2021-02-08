<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\File;
use Gaufrette\File as GaufretteFile;
use Gaufrette\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testIsInstanceOfGaufretteFile(): void
    {
        $file = new File('key', $this->getFilesystemMock());
        self::assertInstanceOf(GaufretteFile::class, $file);
    }

    public function testFileIsAbleToReturnItsFilesystem(): void
    {
        $filesystem = $this->getFilesystemMock();
        $file = new File('key', $filesystem);

        self::assertSame($filesystem, $file->getFilesystem());
    }

    /**
     * @return Filesystem&MockObject
     */
    protected function getFilesystemMock(): Filesystem
    {
        return $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
    }
}
