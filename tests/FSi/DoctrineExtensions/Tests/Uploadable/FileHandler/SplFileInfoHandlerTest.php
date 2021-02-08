<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\SplFileInfoHandler;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;
use SplFileInfo;
use SplTempFileObject;

class SplFileInfoHandlerTest extends BaseHandlerTest
{
    public const KEY = '/someKey';
    public const TEMP_FILENAME = 'tempfile';

    protected function setUp(): void
    {
        $this->handler = new SplFileInfoHandler(self::TEMP_FILENAME);
    }

    public function testSupports(): void
    {
        self::assertTrue($this->handler->supports($this->getInput()));
    }

    public function testGetContent(): void
    {
        $input = $this->getInput();

        $content = $this->handler->getContent($input);
        self::assertEquals(self::CONTENT, $content);
    }

    public function testGetContentOnEmptyFile(): void
    {
        $emptyFile = new SplTempFileObject();

        $content = $this->handler->getContent($emptyFile);
        self::assertEquals('', $content);
    }

    public function testGetContentOnTempFile(): void
    {
        $input = new SplTempFileObject();
        $input->fwrite(self::CONTENT);
        $position = $input->ftell();

        $content = $this->handler->getContent($input);
        self::assertEquals(self::CONTENT, $content);
        self::assertEquals($position, $input->ftell());
    }

    public function testGetContentOnOpenFile(): void
    {
        $input = $this->getInput()->openFile();

        $content = $this->handler->getContent($input);
        self::assertEquals(self::CONTENT, $content);
    }

    public function testGetName(): void
    {
        $input = $this->getInput();

        $name = $this->handler->getName($input);
        self::assertEquals(basename(FILESYSTEM1 . self::KEY), $name);
    }

    public function testGetNameOnTempFile(): void
    {
        $input = new SplTempFileObject();
        $input->fwrite(self::CONTENT);

        $name = $this->handler->getName($input);
        self::assertEquals(self::TEMP_FILENAME, $name);
    }

    public function testException(): void
    {
        $filesystem = new Filesystem(new Local(FILESYSTEM1));
        $input = new SplFileInfo(FILESYSTEM1 . self::KEY);
        $key = self::KEY;

        $this->expectException(RuntimeException::class);
        $this->handler->getContent($input, $key, $filesystem);
    }

    protected function getInput(): SplFileInfo
    {
        $input = new SplFileInfo(FILESYSTEM1 . self::KEY);
        $fileObj = $input->openFile('a');
        $fileObj->fwrite(self::CONTENT);

        return $input;
    }
}
