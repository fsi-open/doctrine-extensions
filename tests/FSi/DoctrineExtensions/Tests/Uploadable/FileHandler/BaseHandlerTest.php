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

    public function testImplementation(): void
    {
        self::assertInstanceOf(FileHandlerInterface::class, $this->handler);
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetContentForWrongInputs($input): void
    {
        $this->expectException(RuntimeException::class);
        self::assertNull($this->handler->getContent($input));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetNameForWrongInput($input): void
    {
        $this->expectException(RuntimeException::class);
        self::assertNull($this->handler->getName($input));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testNotSupports($input)
    {
        self::assertFalse($this->handler->supports($input));
    }

    protected function tearDown(): void
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    public static function wrongInputs(): array
    {
        return [
            ['not file'],
            [[]],
            [new stdClass()],
            [42],
        ];
    }
}
