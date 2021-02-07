<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\File as FSiFile;
use FSi\DoctrineExtensions\Uploadable\FileHandler\GaufretteHandler;
use Gaufrette\File;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

class GaufretteHandlerTest extends BaseHandlerTest
{
    protected function setUp(): void
    {
        $this->handler = new GaufretteHandler();
    }

    /**
     * @dataProvider goodInputs
     */
    public function testSupports($input): void
    {
        self::assertTrue($this->handler->supports($input));
    }

    /**
     * @dataProvider goodInputs
     */
    public function testGetContent($input): void
    {
        $content = $this->handler->getContent($input);
        self::assertEquals(self::CONTENT, $content);
    }

    /**
     * @dataProvider goodInputs
     */
    public function testGetName($input): void
    {
        $name = $this->handler->getName($input);
        self::assertEquals($input->getName(), $name);
    }

    public static function goodInputs(): array
    {
        $filesystem1 = new Filesystem(new Local(FILESYSTEM1));

        $gaufrette = new File('key1', $filesystem1);
        $gaufrette->setContent(self::CONTENT);

        $file = new FSiFile('key2', $filesystem1);
        $file->setContent(self::CONTENT);

        return [[$gaufrette], [$file]];
    }
}
