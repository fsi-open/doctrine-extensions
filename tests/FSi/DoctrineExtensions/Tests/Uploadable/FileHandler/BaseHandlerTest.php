<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Tests\Uploadable\Utils;
use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

abstract class BaseHandlerTest extends TestCase
{
    public const CONTENT = 'sampleContent';

    /**
     * @var FileHandlerInterface
     */
    protected $handler;

    public function testImplementation()
    {
        $this->assertTrue($this->handler instanceof FileHandlerInterface);
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetContentForWrongInputs($input)
    {
        $this->expectException(RuntimeException::class);
        $this->assertNull($this->handler->getContent($input));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetNameForWrongInput($input)
    {
        $this->expectException(RuntimeException::class);
        $this->assertNull($this->handler->getName($input));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testNotSupports($input)
    {
        $this->assertFalse($this->handler->supports($input));
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    public static function wrongInputs()
    {
        return [
            ['not file'],
            [[]],
            [new stdClass()],
            [42],
        ];
    }
}
