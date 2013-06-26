<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\File as FSiFile;
use FSi\DoctrineExtensions\Uploadable\FileHandler\GaufretteHandler;
use Gaufrette\File;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

class GaufretteHandlerTest extends BaseHandlerTest
{
    protected function setUp()
    {
        $this->handler = new GaufretteHandler();
    }

    /**
     * @dataProvider goodInputs
     */
    public function testHandle($input, $key, $filesystem)
    {
        $file = $this->handler->handle($input, $key, $filesystem);
        $this->assertTrue($file instanceof FSiFile);
        $this->assertSame($filesystem, $file->getFilesystem());
        $this->assertEquals($key, $file->getKey());
        $this->assertTrue(file_exists(FILESYSTEM2 . $file->getKey()));
        $this->assertEquals(self::CONTENT, $file->getContent());
    }

    /**
     * @dataProvider goodInputs
     */
    public function testGetName($input)
    {
        $name = $this->handler->getName($input);
        $this->assertEquals($input->getName(), $name);
    }

    public static function goodInputs()
    {
        $filesystem1 = new Filesystem(new Local(FILESYSTEM1));
        $filesystem2 = new Filesystem(new Local(FILESYSTEM2));

        $gaufrette = new File('key1', $filesystem1);
        $gaufrette->setContent(self::CONTENT);

        $file = new FSiFile('key2', $filesystem1);
        $file->setContent(self::CONTENT);

        return array(
            array($gaufrette, self::KEY, $filesystem2),
            array($file, self::KEY, $filesystem2),
        );
    }
}
