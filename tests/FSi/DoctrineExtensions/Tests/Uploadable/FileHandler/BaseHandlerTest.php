<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Tests\Uploadable\Utils;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

abstract class BaseHandlerTest extends \PHPUnit_Framework_TestCase
{
    const KEY = '/sampleKey';
    const CONTENT = 'sampleContent';

    /**
     * @var \FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface
     */
    protected $handler;

    public function testImplementation()
    {
        $this->assertTrue($this->handler instanceof FileHandlerInterface);
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testNotHandle($input, $key, $filesystem)
    {
        $this->assertNull($this->handler->handle($input, $key, $filesystem));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetNameForWrongInput($input)
    {
        $this->assertNull($this->handler->getName($input));
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    public static function wrongInputs()
    {
        $filesystem = new Filesystem(new Local(FILESYSTEM1));
        return array(
            array('not file', self::KEY, $filesystem),
            array(array(), self::KEY, $filesystem),
            array(new \stdClass(), self::KEY, $filesystem),
            array(42, self::KEY, $filesystem),
        );
    }
}
