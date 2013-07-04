<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    public function testIsInstanceOfGaufretteFile()
    {
        $file = new File('key', $this->getFilesystemMock());
        $this->assertTrue($file instanceof \Gaufrette\File);
    }

    public function testFileIsAbleToReturnItsFilesystem()
    {
        $filesystem = $this->getFilesystemMock();
        $file = new File('key', $filesystem);

        $this->assertSame($filesystem, $file->getFilesystem());
    }

    protected function getFilesystemMock()
    {
        return $this->getMockBuilder('Gaufrette\\Filesystem')->disableOriginalConstructor()->getMock();
    }
}
