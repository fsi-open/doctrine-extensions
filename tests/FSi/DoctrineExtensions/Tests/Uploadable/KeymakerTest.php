<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use PHPUnit\Framework\TestCase;
use function implode;

class KeymakerTest extends TestCase
{
    public const PROPERTY = 'property';
    public const ID = 1;
    public const ORIGINAL_NAME = 'originalName.txt';

    public function testCreation(): void
    {
        $keyMaker = new Entity();
        self::assertInstanceOf(KeymakerInterface::class, $keyMaker);
    }

    /**
     * @dataProvider inputs
     */
    public function testKeyGeneration(?string $pattern, string $expected): void
    {
        $keyMaker = new Entity();
        $user = new User();

        self::assertEquals(
            $expected,
            $keyMaker->createKey($user, self::PROPERTY, self::ID, self::ORIGINAL_NAME, $pattern)
        );
    }

    public static function inputs(): array
    {
        return [
            [
                null,
                implode(
                    '/',
                    ['/FSiDoctrineExtensionsTestsUploadableFixtureUser', self::PROPERTY, self::ID, self::ORIGINAL_NAME]
                ),
            ],
            ['{fqcn}/{id}/constant', 'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::ID . '/constant'],
            [
                '{fqcn}/{property}/{wrong_tag}/{id}/{original_name}',
                implode(
                    '/',
                    [
                        'FSiDoctrineExtensionsTestsUploadableFixtureUser',
                        self::PROPERTY,
                        '{wrong_tag}',
                        self::ID,
                        self::ORIGINAL_NAME,
                    ]
                )
            ],
        ];
    }
}
