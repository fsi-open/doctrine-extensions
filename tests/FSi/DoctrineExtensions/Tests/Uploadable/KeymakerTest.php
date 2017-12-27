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

class KeymakerTest extends TestCase
{
    public const PROPERTY = 'property';
    public const ID = 1;
    public const ORIGINAL_NAME = 'originalName.txt';

    public function testCreation()
    {
        $keyMaker = new Entity();
        $this->assertTrue($keyMaker instanceof KeymakerInterface);
    }

    /**
     * @dataProvider inputs
     */
    public function testKeyGeneration($pattern, $expected)
    {
        $keyMaker = new Entity();
        $user = new User();

        $this->assertEquals(
            $expected,
            $keyMaker->createKey($user, self::PROPERTY, self::ID, self::ORIGINAL_NAME, $pattern)
        );
    }

    /**
     * @return array
     */
    public static function inputs()
    {
        return [
            [null, '/FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::PROPERTY . '/' . self::ID . '/' . self::ORIGINAL_NAME],
            ['{fqcn}/{id}/constant', 'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::ID . '/constant'],
            [
                '{fqcn}/{property}/{wrong_tag}/{id}/{original_name}',
                'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::PROPERTY . '/{wrong_tag}/' . self::ID . '/' . self::ORIGINAL_NAME
            ],
        ];
    }
}
