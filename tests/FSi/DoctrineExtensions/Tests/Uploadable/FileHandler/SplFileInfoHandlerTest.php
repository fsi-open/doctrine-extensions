<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\File as FSiFile;
use FSi\DoctrineExtensions\Uploadable\FileHandler\SplFileInfoHandler;
use FSi\DoctrineExtensions\Tests\Uploadable\Utils;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

class SplFileInfoHandlerTest extends BaseHandlerTest
{
    protected function setUp()
    {
        $this->handler = new SplFileInfoHandler();
    }

    public function testHandle()
    {
        $filesystem = new Filesystem(new Local(FILESYSTEM1));

        $input = new \SplFileInfo(FILESYSTEM1  . self::KEY);
        $fileObj = $input->openFile('a');
        $fileObj->fwrite(self::CONTENT);

        $key = self::KEY;

        $file = $this->handler->handle($input, $key, $filesystem);
        $this->assertTrue($file instanceof FSiFile);
        $this->assertSame($filesystem, $file->getFilesystem());
        $this->assertEquals($key, $file->getKey());
        $this->assertTrue(file_exists(FILESYSTEM1 . $file->getKey()));
        $this->assertEquals(self::CONTENT, $file->getContent());
    }

    public function testGetName()
    {
        $input = new \SplFileInfo(FILESYSTEM1  . self::KEY);
        $fileObj = $input->openFile('a');
        $fileObj->fwrite(self::CONTENT);

        $name = $this->handler->getName($input);
        $this->assertEquals(basename(FILESYSTEM1 . self::KEY), $name);
    }

    public function testException()
    {
        $filesystem = new Filesystem(new Local(FILESYSTEM1));
        $input = new \SplFileInfo(FILESYSTEM1  . self::KEY);
        $key = self::KEY;

        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $this->handler->handle($input, $key, $filesystem);
    }
}
