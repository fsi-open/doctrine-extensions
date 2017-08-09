<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\FileHandler\SplFileInfoHandler;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

class SplFileInfoHandlerTest extends BaseHandlerTest
{
    const KEY = '/someKey';
    const TEMP_FILENAME = 'tempfile';

    protected function setUp()
    {
        $this->handler = new SplFileInfoHandler(self::TEMP_FILENAME);
    }

    public function testSupports()
    {
        $this->assertTrue($this->handler->supports($this->getInput()));
    }

    public function testGetContent()
    {
        $input = $this->getInput();

        $content = $this->handler->getContent($input);
        $this->assertEquals(self::CONTENT, $content);
    }

    public function testGetContentOnTempFile()
    {
        $input = new \SplTempFileObject();
        $input->fwrite(self::CONTENT);
        $position = $input->ftell();

        $content = $this->handler->getContent($input);
        $this->assertEquals(self::CONTENT, $content);
        $this->assertEquals($position, $input->ftell());
    }

    public function testGetContentOnOpenFile()
    {
        $input = $this->getInput()->openFile();

        $content = $this->handler->getContent($input);
        $this->assertEquals(self::CONTENT, $content);
    }

    public function testGetName()
    {
        $input = $this->getInput();

        $name = $this->handler->getName($input);
        $this->assertEquals(basename(FILESYSTEM1 . self::KEY), $name);
    }

    public function testGetNameOnTempFile()
    {
        $input = new \SplTempFileObject();
        $input->fwrite(self::CONTENT);

        $name = $this->handler->getName($input);
        $this->assertEquals(self::TEMP_FILENAME, $name);
    }

    public function testException()
    {
        $filesystem = new Filesystem(new Local(FILESYSTEM1));
        $input = new \SplFileInfo(FILESYSTEM1 . self::KEY);
        $key = self::KEY;

        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $this->handler->getContent($input, $key, $filesystem);
    }

    protected function getInput()
    {
        $input = new \SplFileInfo(FILESYSTEM1 . self::KEY);
        $fileObj = $input->openFile('a');
        $fileObj->fwrite(self::CONTENT);
        return $input;
    }
}
