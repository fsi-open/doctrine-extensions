<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\FileHandler\SplFileInfoHandler;
use FSi\DoctrineExtensions\Tests\Uploadable\Utils;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

class SplFileInfoHandlerTest extends BaseHandlerTest
{
    const KEY = '/someKey';

    protected function setUp()
    {
        $this->handler = new SplFileInfoHandler();
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

    public function testGetName()
    {
        $input = $this->getInput();

        $name = $this->handler->getName($input);
        $this->assertEquals(basename(FILESYSTEM1 . self::KEY), $name);
    }

    public function testException()
    {
        $filesystem = new Filesystem(new Local(FILESYSTEM1));
        $input = new \SplFileInfo(FILESYSTEM1  . self::KEY);
        $key = self::KEY;

        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $this->handler->getContent($input, $key, $filesystem);
    }

    protected function getInput()
    {
        $input = new \SplFileInfo(FILESYSTEM1  . self::KEY);
        $fileObj = $input->openFile('a');
        $fileObj->fwrite(self::CONTENT);
        return $input;
    }
}
