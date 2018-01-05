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
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testIsInstanceOfGaufretteFile()
    {
        $file = new File('key', $this->getFilesystemMock());
        $this->assertTrue($file instanceof GaufretteFile);
    }

    public function testFileIsAbleToReturnItsFilesystem()
    {
        $filesystem = $this->getFilesystemMock();
        $file = new File('key', $filesystem);

        $this->assertSame($filesystem, $file->getFilesystem());
    }

    protected function getFilesystemMock()
    {
        return $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
    }
}
