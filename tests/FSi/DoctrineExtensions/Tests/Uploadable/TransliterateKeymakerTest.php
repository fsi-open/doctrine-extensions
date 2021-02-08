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
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use FSi\DoctrineExtensions\Uploadable\Keymaker\TransliterateEntity;
use PHPUnit\Framework\TestCase;

class TransliterateKeymakerTest extends TestCase
{
    public const PROPERTY = 'property';
    public const ID = 1;

    public const ORIGINAL_NAME_SPECIAL = '"[[<###$$or~ i+ginal,?;: \\\   /Na%*&^m!e#$!|>]}.txt"';
    public const PARSED_NAME_SPECIAL = 'or-iginal-name.txt';

    public const ORIGINAL_NAME_PL = '"ołrigiźćnaąślNaóęńme.txt"';
    public const PARSED_NAME_PL = 'olrigizcnaaslnaoenme.txt';

    public const ORIGINAL_NAME_GER = 'örigiünälNÄÜméÖß.txt';
    public const PARSED_NAME_GER = 'origiunalnaumeoss.txt';

    public function testCreation(): void
    {
        $keyMaker = new TransliterateEntity();
        self::assertInstanceOf(KeymakerInterface::class, $keyMaker);
    }

    /**
     * @dataProvider inputsSpecial
     */
    public function testSpecialSignKeyGeneration(?string $pattern, string $expected): void
    {
        $this->assertKeyGeneration($pattern, $expected, self::ORIGINAL_NAME_SPECIAL);
    }

    /**
     * @dataProvider inputsPolish
     */
    public function testPolishKeyGeneration(?string $pattern, string $expected): void
    {
        $this->assertKeyGeneration($pattern, $expected, self::ORIGINAL_NAME_PL);
    }

    /**
     * @dataProvider inputsGerman
     */
    public function testGermanKeyGeneration(?string $pattern, string $expected): void
    {
        $this->assertKeyGeneration($pattern, $expected, self::ORIGINAL_NAME_GER);
    }

    public static function inputsSpecial(): array
    {
        return self::getInputs(self::PARSED_NAME_SPECIAL);
    }

    public static function inputsPolish(): array
    {
        return self::getInputs(self::PARSED_NAME_PL);
    }

    public static function inputsGerman(): array
    {
        return self::getInputs(self::PARSED_NAME_GER);
    }

    private function assertKeyGeneration(?string $pattern, string $expected, string $original): void
    {
        $keyMaker = new TransliterateEntity();
        $user = new User();

        self::assertEquals(
            $expected,
            $keyMaker->createKey(
                $user,
                self::PROPERTY,
                self::ID,
                $original,
                $pattern
            )
        );
    }

    public static function getInputs(string $name): array
    {
        $testSets = [
            [
                '{fqcn}/{id}/constant',
                'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::ID . '/constant'
            ],
            [
                null,
                '/FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::PROPERTY . '/' . self::ID . '/%s'
            ],
            [
                '{fqcn}/{property}/{wrong_tag}/{id}/{original_name}',
                'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::PROPERTY . '/{wrong_tag}/' . self::ID . '/%s'
            ]
        ];
        $inputs = [];
        foreach ($testSets as $set) {
            $set[1] = sprintf($set[1], $name);
            $inputs[] = $set;
        }
        return $inputs;
    }
}
